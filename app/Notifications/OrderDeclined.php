<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

class OrderDeclined extends Notification
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
            ->subject('Order '.$this->order->order_number.' declined')
            ->greeting('Sorry about that')
            ->line($this->order->farmer->stall_name.' couldn\'t take your order '.$this->order->order_number.'.')
            ->when($this->order->decline_reason, fn (MailMessage $mail) => $mail->line('Reason: '.$this->order->decline_reason))
            ->line('Any reserved stock has been released, so you\'re free to order elsewhere.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'message' => $this->order->farmer->stall_name.' declined your order ('.$this->order->order_number.').',
            'url' => $this->url(),
        ];
    }

    private function url(): ?string
    {
        return Route::has('customer.orders.show') ? route('customer.orders.show', $this->order) : null;
    }
}
