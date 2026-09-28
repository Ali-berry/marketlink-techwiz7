<?php

namespace App\Notifications;

use App\Helpers\MoneyFormatter;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

// customer ki receipt - farmer ke accept / decline se pehle
class OrderPlaced extends Notification
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
            ->subject('Order '.$this->order->order_number.' placed')
            ->greeting('Thanks for your order!')
            ->line('Your order '.$this->order->order_number.' with '.$this->order->farmer->stall_name.' was placed.')
            ->line('Pickup is on '.$this->order->pickup_date->format('D j M').' at '.$this->order->market->name.'.')
            ->line('Total to pay at pickup: '.MoneyFormatter::format($this->order->total_amount).'.')
            ->line('We\'ll let you know as soon as the farmer accepts it.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'message' => 'Order placed: '.$this->order->order_number.' with '.$this->order->farmer->stall_name.'.',
            'url' => $this->url(),
        ];
    }

    private function url(): ?string
    {
        return Route::has('customer.orders.show') ? route('customer.orders.show', $this->order) : null;
    }
}
