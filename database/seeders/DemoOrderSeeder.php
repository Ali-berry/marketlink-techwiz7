<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\FarmerProfile;
use App\Models\Order;
use App\Models\PickupWindow;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        $sara = User::where('email', 'sara@marketlink.test')->firstOrFail();
        $bilal = User::where('email', 'bilal@marketlink.test')->firstOrFail();
        $ayesha = User::where('email', 'ayesha@marketlink.test')->firstOrFail();

        $greenValley = FarmerProfile::where('slug', 'green-valley-farm')->firstOrFail();
        $sunriseOrchard = FarmerProfile::where('slug', 'sunrise-orchard')->firstOrFail();
        $desiDairy = FarmerProfile::where('slug', 'desi-dairy-corner')->firstOrFail();
        $crustAndCrumb = FarmerProfile::where('slug', 'crust-and-crumb')->firstOrFail();
        $mesillaValley = FarmerProfile::where('slug', 'mesilla-valley-growers')->firstOrFail();

        // purane picked-up orders, taake insights aur reviews mein data ho
        $pastOrder = $this->placeOrder($sara, $greenValley, 3, OrderStatus::Completed, [
            'Vine tomatoes' => 2, 'Spinach' => 3, 'Fresh mint' => 2,
        ]);
        $this->placeOrder($sara, $desiDairy, 2, OrderStatus::Completed, ['Free-range eggs' => 2, 'Fresh cow milk' => 3]);
        $this->placeOrder($bilal, $greenValley, 2, OrderStatus::Completed, ['Vine tomatoes' => 3, 'Carrots' => 2]);
        $this->placeOrder($bilal, $crustAndCrumb, 1, OrderStatus::Completed, ['Sourdough loaf' => 1, 'Banana walnut cake' => 1]);
        $this->placeOrder($bilal, $sunriseOrchard, 1, OrderStatus::Cancelled, ['Guava' => 2]);

        // live demo ke liye alag alag stage ke upcoming orders
        $this->placeOrder($sara, $sunriseOrchard, 0, OrderStatus::ReadyForPickup, ['Valley mangoes' => 3, 'Bananas' => 1]);
        $this->placeOrder($sara, $crustAndCrumb, 0, OrderStatus::Accepted, ['Sourdough loaf' => 2]);
        $this->placeOrder($bilal, $greenValley, 0, OrderStatus::Placed, ['Spinach' => 2, 'Cucumbers' => 1]);
        // proactive inbox demo: Green Valley ke paas 2 alag pending orders, reply se sirf ek accept karke dikhaya ja sake
        $this->placeOrder($sara, $greenValley, 0, OrderStatus::Placed, ['Vine tomatoes' => 2, 'Fresh mint' => 1]);
        $this->placeOrder($sara, $desiDairy, 0, OrderStatus::Placed, ['Homemade yogurt' => 1, 'Desi butter' => 1]);
        // El Paso Mountain time pe hai - "MT" label dikhane ke liye
        $this->placeOrder($ayesha, $mesillaValley, 0, OrderStatus::Accepted, ['Heirloom tomatoes' => 2, 'Roasted green chile salsa' => 1]);

        Review::create([
            'customer_id' => $sara->id,
            'farmer_profile_id' => $greenValley->id,
            'order_id' => $pastOrder->id,
            'rating' => 5,
            'comment' => 'Everything was ready on time and the tomatoes tasted like proper tomatoes.',
            'farmer_reply' => 'Thank you Sara, see you next Sunday!',
            'farmer_replied_at' => now()->subDays(2),
        ]);

        $sara->favouriteFarmers()->syncWithoutDetaching([$greenValley->id, $desiDairy->id]);
    }

    // status history ke saath order banata hai. $weeksAgo = 0 matlab agla pickup din
    private function placeOrder(User $customer, FarmerProfile $farmer, int $weeksAgo, OrderStatus $finalStatus, array $quantitiesByProductName): Order
    {
        $pickupWindow = PickupWindow::where('farmer_profile_id', $farmer->id)->with('market')->firstOrFail();
        $pickupDate = $this->pickupDateFor($pickupWindow, $weeksAgo);
        $placedAt = $pickupDate->copy()->subDays(3)->setTime(19, 30);

        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'customer_id' => $customer->id,
            'farmer_profile_id' => $farmer->id,
            'market_id' => $pickupWindow->market_id,
            'pickup_window_id' => $pickupWindow->id,
            'pickup_date' => $pickupDate,
            'status' => $finalStatus,
            'total_amount' => 0,
        ]);

        $orderTotal = 0;

        foreach ($quantitiesByProductName as $productName => $quantity) {
            $product = $farmer->products()->where('name', $productName)->firstOrFail();
            $lineTotal = $product->price * $quantity;
            $orderTotal += $lineTotal;

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit' => $product->unit,
                'unit_price' => $product->price,
                'quantity' => $quantity,
                'line_total' => $lineTotal,
            ]);
        }

        $order->forceFill([
            'total_amount' => $orderTotal,
            'created_at' => $placedAt,
            'updated_at' => $placedAt,
        ])->save();

        $this->writeStatusHistory($order, $finalStatus, $placedAt, $farmer->user_id, $customer->id);

        return $order;
    }

    // market ka "aaj", asal checkout page jaisa
    private function pickupDateFor(PickupWindow $pickupWindow, int $weeksAgo): Carbon
    {
        $marketToday = $pickupWindow->market->localNow()->startOfDay();

        $upcomingPickupDate = $marketToday->dayOfWeek === $pickupWindow->day_of_week
            ? $marketToday
            : $marketToday->copy()->next($pickupWindow->day_of_week);

        return $upcomingPickupDate->subWeeks($weeksAgo);
    }

    // order ko final status tak har step se guzaar ke har step log karta hai
    private function writeStatusHistory(Order $order, OrderStatus $finalStatus, Carbon $placedAt, int $farmerUserId, int $customerUserId): void
    {
        $order->statusChanges()->create([
            'changed_by_user_id' => $customerUserId,
            'from_status' => null,
            'to_status' => OrderStatus::Placed->value,
            'created_at' => $placedAt,
        ]);

        $stepsAfterPlacing = match ($finalStatus) {
            OrderStatus::Accepted => [OrderStatus::Accepted],
            OrderStatus::ReadyForPickup => [OrderStatus::Accepted, OrderStatus::ReadyForPickup],
            OrderStatus::Completed => [OrderStatus::Accepted, OrderStatus::ReadyForPickup, OrderStatus::Completed],
            OrderStatus::Cancelled => [OrderStatus::Cancelled],
            default => [],
        };

        $previousStatus = OrderStatus::Placed;
        $stepTime = $placedAt->copy();

        foreach ($stepsAfterPlacing as $nextStatus) {
            // completed = stall pe pick up aur paid, is liye pickup wale din - OrderStatusUpdater wala rule
            $stepTime = $nextStatus === OrderStatus::Completed
                ? $order->pickup_date->copy()->setTime(11, 30)
                : $stepTime->addHours(10);

            $order->statusChanges()->create([
                'changed_by_user_id' => $nextStatus === OrderStatus::Cancelled ? $customerUserId : $farmerUserId,
                'from_status' => $previousStatus->value,
                'to_status' => $nextStatus->value,
                'created_at' => $stepTime->copy(),
            ]);

            $timestampColumn = match ($nextStatus) {
                OrderStatus::Accepted => 'accepted_at',
                OrderStatus::ReadyForPickup => 'ready_at',
                OrderStatus::Completed => 'completed_at',
                OrderStatus::Cancelled => 'cancelled_at',
                default => null,
            };

            if ($timestampColumn) {
                $order->forceFill([$timestampColumn => $stepTime->copy()])->save();
            }

            $previousStatus = $nextStatus;
        }
    }
}
