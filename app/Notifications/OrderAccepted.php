<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

class OrderAccepted extends Notification
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
            ->subject('Order '.$this->order->order_number.' accepted')
            ->greeting('Good news!')
            ->line($this->order->farmer->stall_name.' accepted your order '.$this->order->order_number.'.')
            ->line('Pickup is '.$this->order->pickupSummary().'.')
            ->line('We\'ll let you know as soon as it\'s ready to collect.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'message' => $this->order->farmer->stall_name.' accepted your order ('.$this->order->order_number.').',
            'url' => $this->url(),
        ];
    }

    private function url(): ?string
    {
        return Route::has('customer.orders.show') ? route('customer.orders.show', $this->order) : null;
    }
}
