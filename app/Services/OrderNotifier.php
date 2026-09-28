<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Notifications\NewPreOrderReceived;
use App\Notifications\OrderAccepted;
use App\Notifications\OrderDeclined;
use App\Notifications\OrderPlaced;
use App\Notifications\OrderReadyForPickup;
use App\Notifications\UrgentOrderMarkedReady;
use App\Notifications\UrgentOrderPlaced;
use App\Notifications\UrgentOrderReceived;
use App\Services\Agent\AgentProactiveMessenger;

// Order ke har event pe sahi notification bhejta hai, taake OrderStatusUpdater ko mail ka pata na ho
class OrderNotifier
{
    public function __construct(private readonly AgentProactiveMessenger $proactiveMessenger)
    {
    }

    // naya order - farmer ko review ke liye, customer ko receipt
    public function notifyOfNewOrder(Order $order): void
    {
        $order->farmer->user->notify(new NewPreOrderReceived($order));
        $order->customer->notify(new OrderPlaced($order));

        // normal pre-order kabhi auto-confirm nahi hota, farmer ko hamesha batao
        $this->notifyFarmerAgentOfNewOrder($order);
    }

    // urgent order ke apne do messages - dono ko pickup time aur auto-confirm ka pata hona chahiye
    public function notifyOfNewUrgentOrder(Order $order, bool $wasAutoConfirmed): void
    {
        $order->farmer->user->notify(new UrgentOrderReceived($order, $wasAutoConfirmed));
        $order->customer->notify(new UrgentOrderPlaced($order, $wasAutoConfirmed));

        // auto-confirm ho gaya to farmer ko action lena hi nahi - agent message ki zaroorat nahi
        if (! $wasAutoConfirmed) {
            $this->notifyFarmerAgentOfNewOrder($order);
        }
    }

    private function notifyFarmerAgentOfNewOrder(Order $order): void
    {
        $this->proactiveMessenger->send(
            recipient: $order->farmer->user,
            userType: UserRole::Farmer,
            content: "New pre-order {$order->order_number} from {$order->customer->firstName()} - "
                .$order->itemsSummaryText().', pickup '.$order->pickupSummary().'. Accept or decline?',
            contextType: 'order',
            contextId: $order->id,
            contextLabel: $order->order_number,
            actions: ['Accept', 'Decline', 'Details'],
        );
    }

    // AI ne ready kiya - customer ko normal "ready" message, farmer ko bataya jata hai ke AI ne kiya
    public function notifyOfUrgentOrderMarkedReady(Order $order): void
    {
        $order->customer->notify(new OrderReadyForPickup($order));
        $order->farmer->user->notify(new UrgentOrderMarkedReady($order));
    }

    public function notifyCustomerOfStatusChange(Order $order, OrderStatus $newStatus): void
    {
        $notification = match ($newStatus) {
            OrderStatus::Accepted => new OrderAccepted($order),
            OrderStatus::Declined => new OrderDeclined($order),
            OrderStatus::ReadyForPickup => new OrderReadyForPickup($order),
            default => null,
        };

        if ($notification) {
            $order->customer->notify($notification);
        }
    }
}
