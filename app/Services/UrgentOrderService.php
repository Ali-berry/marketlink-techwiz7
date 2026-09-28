<?php

namespace App\Services;

use App\Enums\OrderPlacedVia;
use App\Enums\OrderStatus;
use App\Enums\ProductAvailability;
use App\Exceptions\BasketCheckoutException;
use App\Exceptions\InvalidOrderTransitionException;
use App\Helpers\DistanceHelper;
use App\Models\FarmerProfile;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// "mujhe 30 min mein tomatoes chahiye" - aise farmers dhoondta hai jo abhi apne stall pe de saken,
// aur order lagata hai. Urgent rules: hours, hourly limit, customer ke max 2 open urgent orders.
// Farmer chahe to AI order confirm aur prep time ke baad ready bhi kar deta hai
class UrgentOrderService
{
    public const MIN_PICKUP_MINUTES = 15;

    public const MAX_PICKUP_MINUTES = 120;

    public const MAX_OPEN_URGENT_ORDERS_PER_CUSTOMER = 2;

    private const MAX_RESULTS = 8;

    public function __construct(
        private readonly PlaceOrderFromBasket $placeOrderFromBasket,
        private readonly OrderStatusUpdater $orderStatusUpdater,
        private readonly OrderNotifier $notifier,
    ) {
    }

    // jo farmer abhi urgent le sakta hai: urgent hours ke andar, hourly limit baqi, product stock mein.
    // customer ki location ho to qareeb wala pehle
    public function findAvailableNow(string $productSearch, ?User $customer = null): Collection
    {
        $matchingProducts = Product::visibleToCustomers()
            ->where('name', 'like', '%'.trim($productSearch).'%')
            ->where('availability', ProductAvailability::Available->value)
            ->where('stock_quantity', '>', 0)
            ->whereHas('farmer', fn ($farmerQuery) => $farmerQuery->acceptingUrgentOrders())
            ->with('farmer')
            ->get();

        $availableOptions = $matchingProducts
            ->filter(fn (Product $product) => $this->farmerCanTakeUrgentOrderNow($product->farmer))
            ->map(fn (Product $product) => [
                'farmer' => $product->farmer,
                'product' => $product,
                'price' => (float) $product->price,
                'stock' => $product->stock_quantity,
                'pickup_address' => $product->farmer->address,
                'distance_in_miles' => $this->distanceFromCustomer($customer, $product->farmer),
                'auto_confirms' => $product->farmer->ai_auto_confirms_urgent,
            ]);

        // distance pata na ho to aakhir mein
        return $availableOptions
            ->sortBy(fn (array $option) => $option['distance_in_miles'] ?? PHP_FLOAT_MAX)
            ->take(self::MAX_RESULTS)
            ->values();
    }

    /**
     * @throws BasketCheckoutException jab koi urgent rule, stock ya product mana kare
     */
    public function placeUrgentOrder(User $customer, Product $product, int $quantity, int $pickupInMinutes, ?OrderPlacedVia $placedVia = null): Order
    {
        if ($pickupInMinutes < self::MIN_PICKUP_MINUTES || $pickupInMinutes > self::MAX_PICKUP_MINUTES) {
            throw new BasketCheckoutException('Urgent pickups have to be between '.self::MIN_PICKUP_MINUTES.' and '.self::MAX_PICKUP_MINUTES.' minutes from now.');
        }

        $farmer = $product->farmer;
        $homeMarket = $farmer->homeMarket();
        $urgentPickupAt = now()->addMinutes($pickupInMinutes);

        if (! $farmer->isApproved() || ! $farmer->accepts_urgent_orders || ! $homeMarket) {
            throw new BasketCheckoutException("{$farmer->stall_name} isn't taking urgent orders.");
        }

        if (! $farmer->urgentHoursInclude(now()) || ! $farmer->urgentHoursInclude($urgentPickupAt)) {
            throw new BasketCheckoutException("{$farmer->stall_name} only does urgent pickups between {$farmer->urgentHoursText()}.");
        }

        // farmer aur customer rows lock karke limits count karte hain, taake do urgent orders ek saath
        // aakhri jagah na le lein
        $order = DB::transaction(function () use ($customer, $product, $quantity, $farmer, $homeMarket, $urgentPickupAt, $placedVia) {
            $lockedFarmer = FarmerProfile::whereKey($farmer->id)->lockForUpdate()->first();
            User::whereKey($customer->id)->lockForUpdate()->first();

            if ($this->urgentOrdersInLastHour($lockedFarmer) >= $lockedFarmer->max_urgent_orders_per_hour) {
                throw new BasketCheckoutException("{$farmer->stall_name} is fully booked for urgent orders this hour. Try another farmer or a normal pre-order.");
            }

            $customersOpenUrgentOrders = $customer->ordersAsCustomer()->urgent()->open()->count();

            if ($customersOpenUrgentOrders >= self::MAX_OPEN_URGENT_ORDERS_PER_CUSTOMER) {
                throw new BasketCheckoutException('You already have '.self::MAX_OPEN_URGENT_ORDERS_PER_CUSTOMER.' urgent orders on the way. Pick those up first, then you can place another.');
            }

            return $this->placeOrderFromBasket->placeUrgentProduct($customer, $product, $quantity, $homeMarket, $urgentPickupAt, $placedVia);
        });

        $wasAutoConfirmed = $farmer->ai_auto_confirms_urgent;

        if ($wasAutoConfirmed) {
            $this->orderStatusUpdater->autoConfirmUrgentOrder($order);
        }

        $this->notifier->notifyOfNewUrgentOrder($order->fresh(), $wasAutoConfirmed);

        // prep time 0 = pehle se packed, scheduler ka wait nahi
        if ($wasAutoConfirmed && $order->fresh()->isDueForAutoReady()) {
            $this->orderStatusUpdater->autoMarkUrgentOrderReady($order);
        }

        return $order->fresh();
    }

    // jin urgent orders ka prep time poora ho gaya unhe ready karta hai. Scheduler har minute sab ke liye,
    // order pages sirf dekhne wale user ke liye - scheduler na chale tab bhi demo chal jaye
    public function markDueOrdersReady(?User $onlyForUser = null): int
    {
        $ordersWaitingForPrepTime = Order::urgent()
            ->where('status', OrderStatus::Accepted->value)
            ->whereHas('farmer', fn ($farmerQuery) => $farmerQuery->where('ai_marks_urgent_ready', true))
            ->when($onlyForUser?->isFarmer(), fn ($query) => $query->where('farmer_profile_id', $onlyForUser->farmerProfile?->id))
            ->when($onlyForUser?->isCustomer(), fn ($query) => $query->where('customer_id', $onlyForUser->id))
            ->with('farmer')
            ->get();

        $markedReadyCount = 0;

        foreach ($ordersWaitingForPrepTime as $order) {
            if (! $order->isDueForAutoReady()) {
                continue;
            }

            try {
                $this->orderStatusUpdater->autoMarkUrgentOrderReady($order);
                $markedReadyCount++;
            } catch (InvalidOrderTransitionException) {
                // farmer ya koi aur request pehle kar chuki
            }
        }

        return $markedReadyCount;
    }

    // sasti checks findAvailableNow() ki query mein ho chuki, ye wali timezone aur order count maangti hain
    private function farmerCanTakeUrgentOrderNow(FarmerProfile $farmer): bool
    {
        return $farmer->homeMarket() !== null
            && $farmer->urgentHoursInclude(now())
            && $this->urgentOrdersInLastHour($farmer) < $farmer->max_urgent_orders_per_hour;
    }

    // rolling ghanta, clock wala nahi. Declined / cancelled count nahi hote
    private function urgentOrdersInLastHour(FarmerProfile $farmer): int
    {
        return $farmer->orders()
            ->urgent()
            ->where('created_at', '>=', now()->subHour())
            ->whereNotIn('status', [OrderStatus::Declined->value, OrderStatus::Cancelled->value])
            ->count();
    }

    private function distanceFromCustomer(?User $customer, FarmerProfile $farmer): ?float
    {
        if (! $customer?->hasSavedLocation() || ! $farmer->hasMapLocation()) {
            return null;
        }

        $distanceInMiles = DistanceHelper::distanceInMiles($customer->latitude, $customer->longitude, $farmer->latitude, $farmer->longitude);

        return round($distanceInMiles, 1);
    }
}
