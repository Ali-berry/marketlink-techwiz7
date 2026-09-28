<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

// farmer ko, jaise hi customer pre-order lagaye
class NewPreOrderReceived extends Notification
{
    use Queueable;

    public function __construct(private readonly Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New pre-order '.$this->order->order_number)
            ->greeting('You have a new pre-order!')
            ->line($this->order->customer->name.' just placed order '.$this->order->order_number.' for pickup on '.$this->order->pickup_date->format('D j M').'.')
            ->action('Review the order', $this->url())
            ->line('Accept or decline it from your MarketLink pre-orders page.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'message' => $this->order->customer->name.' placed a new pre-order ('.$this->order->order_number.').',
            'url' => $this->url(),
        ];
    }

    private function url(): ?string
    {
        return Route::has('farmer.orders.show') ? route('farmer.orders.show', $this->order) : null;
    }
}
