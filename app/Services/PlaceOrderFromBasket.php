<?php

namespace App\Services;

use App\Enums\OrderPlacedVia;
use App\Enums\OrderStatus;
use App\Enums\ProductAvailability;
use App\Exceptions\BasketCheckoutException;
use App\Models\FarmerProfile;
use App\Models\Market;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusChange;
use App\Models\PickupWindow;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Order sirf yahan banta hai. Stock aur pickup slot ek transaction mein rows lock karke dobara check
// hote hain, taake do log ek saath checkout karen to gadbad na ho.
// place() = checkout page, placeSingleProduct() = AI assistant, placeUrgentProduct() = urgent order
class PlaceOrderFromBasket
{
    public function __construct(private readonly OrderNotifier $notifier)
    {
    }

    /**
     * @throws BasketCheckoutException jab stock ya slot beech mein khatam ho jaye
     */
    public function place(
        User $customer,
        FarmerProfile $farmer,
        Market $market,
        PickupWindow $pickupWindow,
        Carbon $pickupDate,
        ?string $customerNote,
        Cart $cart,
    ): Order {
        $basketLines = $cart->groupedByFarmer()->get($farmer->id)['items'] ?? collect();

        $this->removeProductsNoLongerListed($cart, $farmer, $basketLines);

        if ($basketLines->isEmpty()) {
            throw new BasketCheckoutException('Your basket for this farmer is empty.');
        }

        $order = $this->createOrder($customer, $farmer, $market, $pickupWindow, $pickupDate, null, $customerNote, $basketLines, ' Please update your basket.', null);

        // pehle basket khali, phir notification - mail fail ho aur basket bhari rahe to retry pe order do baar lagega
        $cart->clearFarmerGroup($farmer->id);
        $this->notifier->notifyOfNewOrder($order->fresh());

        return $order;
    }

    // sirf ye ek product aur quantity - basket mein jo hai wo shamil nahi hota
    /**
     * @throws BasketCheckoutException jab product, stock ya slot available na ho
     */
    public function placeSingleProduct(
        User $customer,
        Product $product,
        int $quantity,
        PickupWindow $pickupWindow,
        Carbon $pickupDate,
        ?string $customerNote = null,
        ?OrderPlacedVia $placedVia = null,
    ): Order {
        $orderLines = $this->singleProductOrderLines($product, $quantity);

        $order = $this->createOrder($customer, $product->farmer, $pickupWindow->market, $pickupWindow, $pickupDate, null, $customerNote, $orderLines, '', $placedVia);

        $this->notifier->notifyOfNewOrder($order->fresh());

        return $order;
    }

    // placeSingleProduct() jaisa, bas pickup farm pe ghante ke andar. Notification UrgentOrderService bhejta hai
    /**
     * @throws BasketCheckoutException jab product ya stock available na ho
     */
    public function placeUrgentProduct(
        User $customer,
        Product $product,
        int $quantity,
        Market $homeMarket,
        Carbon $urgentPickupAt,
        ?OrderPlacedVia $placedVia = null,
    ): Order {
        $orderLines = $this->singleProductOrderLines($product, $quantity);

        $pickupDate = $urgentPickupAt->copy()->setTimezone($homeMarket->timezone)->startOfDay();

        return $this->createOrder($customer, $product->farmer, $homeMarket, null, $pickupDate, $urgentPickupAt, null, $orderLines, '', $placedVia);
    }

    private function singleProductOrderLines(Product $product, int $quantity): Collection
    {
        if (! Product::visibleToCustomers()->whereKey($product->id)->exists()) {
            throw new BasketCheckoutException("Sorry, {$product->name} isn't available to order right now.");
        }

        if ($quantity < 1) {
            throw new BasketCheckoutException('The quantity has to be at least 1.');
        }

        return collect([[
            'product' => $product,
            'quantity' => $quantity,
            'lineTotal' => $product->price * $quantity,
        ]]);
    }

    // $orderLines = [product, quantity, lineTotal] rows. $fixHint stock error ke saath lagta hai.
    // urgent order mein $pickupWindow null hota hai, $placedVia normal checkout pe null
    private function createOrder(
        User $customer,
        FarmerProfile $farmer,
        Market $market,
        ?PickupWindow $pickupWindow,
        Carbon $pickupDate,
        ?Carbon $urgentPickupAt,
        ?string $customerNote,
        Collection $orderLines,
        string $fixHint,
        ?OrderPlacedVia $placedVia,
    ): Order {
        // yahan bhi check - AI assistant checkout form se nahi guzarta
        if (! $market->is_active) {
            throw new BasketCheckoutException($market->name.' is closed on MarketLink right now. Please choose another market.');
        }

        return DB::transaction(function () use ($customer, $farmer, $market, $pickupWindow, $pickupDate, $urgentPickupAt, $customerNote, $orderLines, $fixHint, $placedVia) {
            // ids sort karke lock karte hain taake do checkouts deadlock na hon
            $productIds = $orderLines->pluck('product.id')->sort()->values();
            $lockedProducts = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            // slot row lock karne se capacity count safe hai, doosra checkout yahan wait karega.
            // urgent order ka hourly limit UrgentOrderService mein check hota hai
            if ($pickupWindow) {
                $lockedPickupWindow = PickupWindow::whereKey($pickupWindow->id)->lockForUpdate()->firstOrFail();
                $this->assertSlotHasRoom($lockedPickupWindow, $pickupDate);
            }

            $this->assertEnoughStock($orderLines, $lockedProducts, $fixHint);

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'customer_id' => $customer->id,
                'farmer_profile_id' => $farmer->id,
                'market_id' => $market->id,
                'pickup_window_id' => $pickupWindow?->id,
                'pickup_date' => $pickupDate->toDateString(),
                'is_urgent' => $urgentPickupAt !== null,
                'urgent_pickup_at' => $urgentPickupAt,
                'status' => OrderStatus::Placed->value,
                'placed_via' => $placedVia?->value,
                'total_amount' => $orderLines->sum('lineTotal'),
                'customer_note' => $customerNote,
            ]);

            foreach ($orderLines as $line) {
                $lockedProduct = $lockedProducts[$line['product']->id];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $lockedProduct->id,
                    'product_name' => $lockedProduct->name,
                    'unit' => $lockedProduct->unit,
                    'unit_price' => $lockedProduct->price,
                    'quantity' => $line['quantity'],
                    'line_total' => $line['lineTotal'],
                ]);

                $lockedProduct->decrement('stock_quantity', $line['quantity']);
            }

            OrderStatusChange::create([
                'order_id' => $order->id,
                'changed_by_user_id' => $customer->id,
                'from_status' => null,
                'to_status' => OrderStatus::Placed->value,
                'note' => null,
            ]);

            return $order;
        });
    }

    // basket ke baad farmer ne product pause kar diya (ya admin ne hide) to wo chup chaap gayab
    // ho jata - is liye yahan nikaal ke customer ko batate hain
    private function removeProductsNoLongerListed(Cart $cart, FarmerProfile $farmer, Collection $basketLines): void
    {
        $unlistedProducts = Product::whereIn('id', $cart->productIds())
            ->where('farmer_profile_id', $farmer->id)
            ->whereNotIn('id', $basketLines->pluck('product.id'))
            ->get();

        if ($unlistedProducts->isEmpty()) {
            return;
        }

        foreach ($unlistedProducts as $unlistedProduct) {
            $cart->remove($unlistedProduct->id);
        }

        throw new BasketCheckoutException(
            'Sorry, '.$unlistedProducts->pluck('name')->join(', ', ' and ')." isn't available right now, so we took it out of your basket. Please check your basket and try again."
        );
    }

    private function availabilityReason(ProductAvailability $availability): string
    {
        return match ($availability) {
            ProductAvailability::SoldOut => 'sold out',
            ProductAvailability::TemporarilyUnavailable => 'not available this week',
            ProductAvailability::Available => 'available',
        };
    }

    private function assertSlotHasRoom(PickupWindow $pickupWindow, Carbon $pickupDate): void
    {
        $ordersAlreadyInSlot = Order::where('pickup_window_id', $pickupWindow->id)
            ->whereDate('pickup_date', $pickupDate)
            ->open()
            ->count();

        if ($ordersAlreadyInSlot >= $pickupWindow->max_orders) {
            throw new BasketCheckoutException('That pickup slot just filled up. Please choose another time.');
        }
    }

    private function assertEnoughStock(Collection $orderLines, Collection $lockedProducts, string $fixHint): void
    {
        foreach ($orderLines as $line) {
            $lockedProduct = $lockedProducts->get($line['product']->id);

            // farmer ne sold out ya pause kar diya ho to stock count abhi bhi 0 se upar ho sakta hai
            if ($lockedProduct && $lockedProduct->availability !== ProductAvailability::Available) {
                throw new BasketCheckoutException(
                    "Sorry, {$lockedProduct->name} is {$this->availabilityReason($lockedProduct->availability)} right now.".$fixHint
                );
            }

            if (! $lockedProduct || $lockedProduct->stock_quantity < $line['quantity']) {
                $availableQuantity = $lockedProduct->stock_quantity ?? 0;

                throw new BasketCheckoutException(
                    "Sorry, {$line['product']->name} only has {$availableQuantity} left.".$fixHint
                );
            }
        }
    }
}
