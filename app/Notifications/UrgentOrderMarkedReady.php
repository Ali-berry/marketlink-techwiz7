<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

// sirf bell - farmer ko pata chale AI ne order ready kar diya aur customer ko bula liya
class UrgentOrderMarkedReady extends Notification
{
    use Queueable;

    public function __construct(private readonly Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'message' => 'MarketLink AI marked '.$this->order->order_number.' ready - '
                .$this->order->customer->firstName().' arriving by '.$this->order->urgentPickupTimeText(),
            'url' => Route::has('farmer.orders.show') ? route('farmer.orders.show', $this->order) : null,
        ];
    }
}
