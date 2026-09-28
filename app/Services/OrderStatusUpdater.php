<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\OrderStatusChange;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Order ka status sirf yahan se badalta hai - state machine, decline/cancel pe stock wapas
// aur audit trail (order_status_changes) sab ek jagah
class OrderStatusUpdater
{
    // AI jab urgent order accept kare to timeline pe ye note aata hai
    public const URGENT_AUTO_CONFIRM_NOTE = "Auto-confirmed by MarketLink AI - urgent order, farmer's setting";

    // AI jab prep time ke baad ready kare to timeline pe ye note aata hai
    public const URGENT_AUTO_READY_NOTE = "Marked ready by MarketLink AI - farmer's prep time";

    public function __construct(private readonly OrderNotifier $notifier)
    {
    }

    // kaunsa status kis mein ja sakta hai, aur kaun ye kar sakta hai
    private const ALLOWED_MOVES = [
        'placed' => [
            'accepted' => 'farmer',
            'declined' => 'farmer',
            'cancelled' => 'customer',
        ],
        'accepted' => [
            'ready_for_pickup' => 'farmer',
            'cancelled' => 'customer',
        ],
        'ready_for_pickup' => [
            'completed' => 'farmer',
        ],
    ];

    public function transition(Order $order, OrderStatus $newStatus, User $actor, ?string $note = null): Order
    {
        $currentStatus = $order->status;
        $requiredActorType = self::ALLOWED_MOVES[$currentStatus->value][$newStatus->value] ?? null;

        if ($requiredActorType === null) {
            throw new InvalidOrderTransitionException(
                "An order can't move from \"{$currentStatus->label()}\" to \"{$newStatus->label()}\"."
            );
        }

        $this->assertActorIsAllowed($order, $actor, $requiredActorType);

        // yahan bhi check hai taake AI ka "mark completed" tool bhi isi rule mein aaye
        if ($newStatus === OrderStatus::Completed && ! $order->pickupDayHasArrived()) {
            throw new InvalidOrderTransitionException(
                'You can mark this order completed on or after its pickup day ('.$order->pickup_date->format('D j M').').'
            );
        }

        $this->saveStatusChange($order, $newStatus, $actor, $note);

        $this->notifier->notifyCustomerOfStatusChange($order->fresh(), $newStatus);

        return $order;
    }

    // AI farmer ki setting se urgent order accept karta hai - koi user nahi, is liye changed_by null.
    // Notification yahan nahi, UrgentOrderService khud urgent wala notice bhejta hai
    public function autoConfirmUrgentOrder(Order $order): Order
    {
        $farmerAllowsAutoConfirm = $order->farmer->accepts_urgent_orders && $order->farmer->ai_auto_confirms_urgent;

        if (! $order->is_urgent || $order->status !== OrderStatus::Placed || ! $farmerAllowsAutoConfirm) {
            throw new InvalidOrderTransitionException('Only a new urgent order for a farmer with auto-confirm on can be confirmed by MarketLink AI.');
        }

        $this->saveStatusChange($order, OrderStatus::Accepted, null, self::URGENT_AUTO_CONFIRM_NOTE);

        return $order;
    }

    // prep time guzarne pe AI order ready karta hai. Scheduler aur page load ek saath aa sakte hain,
    // is liye lock ke saath dobara parhte hain - jo baad mein aaye usay order ready mila
    public function autoMarkUrgentOrderReady(Order $order): Order
    {
        $wasMarkedReady = DB::transaction(function () use ($order) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->with('farmer')->first();

            if (! $lockedOrder?->isDueForAutoReady()) {
                return false;
            }

            $this->saveStatusChange($lockedOrder, OrderStatus::ReadyForPickup, null, self::URGENT_AUTO_READY_NOTE);

            return true;
        });

        if (! $wasMarkedReady) {
            throw new InvalidOrderTransitionException('Only an accepted urgent order whose prep time has passed can be marked ready by MarketLink AI.');
        }

        $this->notifier->notifyOfUrgentOrderMarkedReady($order->fresh());

        return $order->fresh();
    }

    // transition() wala hi check, bas throw ki jagah true/false - view mein button dikhane ke liye
    public function canTransition(Order $order, OrderStatus $newStatus, User $actor): bool
    {
        $requiredActorType = self::ALLOWED_MOVES[$order->status->value][$newStatus->value] ?? null;

        if ($requiredActorType === null) {
            return false;
        }

        try {
            $this->assertActorIsAllowed($order, $actor, $requiredActorType);

            return true;
        } catch (InvalidOrderTransitionException) {
            return false;
        }
    }

    // $actor sirf AI wale dono moves mein null hota hai
    private function saveStatusChange(Order $order, OrderStatus $newStatus, ?User $actor, ?string $note): void
    {
        $currentStatus = $order->status;

        DB::transaction(function () use ($order, $currentStatus, $newStatus, $actor, $note) {
            $order->forceFill(array_merge(
                ['status' => $newStatus->value],
                $this->timestampColumnFor($newStatus),
                $newStatus === OrderStatus::Declined ? ['decline_reason' => $note] : [],
            ))->save();

            OrderStatusChange::create([
                'order_id' => $order->id,
                'changed_by_user_id' => $actor?->id,
                'from_status' => $currentStatus->value,
                'to_status' => $newStatus->value,
                'note' => $note,
            ]);

            if (in_array($newStatus, [OrderStatus::Declined, OrderStatus::Cancelled], true)) {
                $this->restockOrderItems($order);
            }
        });
    }

    private function assertActorIsAllowed(Order $order, User $actor, string $requiredActorType): void
    {
        if ($requiredActorType === 'farmer') {
            $actorIsOrdersFarmer = $actor->farmerProfile && $actor->farmerProfile->id === $order->farmer_profile_id;

            if (! $actorIsOrdersFarmer) {
                throw new InvalidOrderTransitionException('Only the farmer who received this order can do that.');
            }

            return;
        }

        // customer abhi sirf cancel kar sakta hai
        if ($actor->id !== $order->customer_id) {
            throw new InvalidOrderTransitionException('Only the customer who placed this order can cancel it.');
        }

        if (! $order->customerCanStillChange()) {
            throw new InvalidOrderTransitionException($order->is_urgent
                ? 'An urgent order can only be cancelled in the first '.Order::URGENT_CANCEL_GRACE_MINUTES.' minutes - the farmer is already getting it ready.'
                : 'This order is too close to pickup time to cancel.');
        }
    }

    private function timestampColumnFor(OrderStatus $status): array
    {
        return match ($status) {
            OrderStatus::Accepted => ['accepted_at' => now()],
            OrderStatus::ReadyForPickup => ['ready_at' => now()],
            OrderStatus::Completed => ['completed_at' => now()],
            OrderStatus::Cancelled => ['cancelled_at' => now()],
            default => [],
        };
    }

    private function restockOrderItems(Order $order): void
    {
        foreach ($order->items as $item) {
            $item->product?->increment('stock_quantity', $item->quantity);
        }
    }
}
