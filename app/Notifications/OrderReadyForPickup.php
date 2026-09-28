<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

// customer ko - order pack ho gaya, aa ke le jao
class OrderReadyForPickup extends Notification
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
            ->subject('Order '.$this->order->order_number.' is ready for pickup')
            ->greeting('It\'s ready!')
            ->line('Your order '.$this->order->order_number.' from '.$this->order->farmer->stall_name.' is packed and waiting.')
            ->line('Collect it '.$this->order->pickupSummary().' and pay the farmer at the stall.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'message' => 'Your order from '.$this->order->farmer->stall_name.' is ready for pickup ('.$this->order->order_number.').',
            'url' => $this->url(),
        ];
    }

    private function url(): ?string
    {
        return Route::has('customer.orders.show') ? route('customer.orders.show', $this->order) : null;
    }
}
