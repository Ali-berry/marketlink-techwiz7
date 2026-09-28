<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

// farmer ko har urgent order pe - setting ke hisaab se "AI ne confirm kar diya" ya "accept karo"
class UrgentOrderReceived extends Notification
{
    use Queueable;

    public function __construct(private readonly Order $order, private readonly bool $wasAutoConfirmed)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->headline())
            ->line($this->order->customer->name.' ordered '.$this->itemsText().' for pickup at your stall by '.$this->order->urgentPickupTimeText().'.');

        if ($this->wasAutoConfirmed) {
            return $mail->line('MarketLink AI confirmed it for you, so the customer is already on their way.')
                ->action('See the order', $this->url());
        }

        return $mail->line('Please accept or decline it as soon as you can - the customer is waiting to hear back.')
            ->action('Accept or decline', $this->url());
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'message' => $this->headline(),
            'url' => $this->url(),
        ];
    }

    private function headline(): string
    {
        $arrivalText = $this->order->customer->name.' arriving by '.$this->order->urgentPickupTimeText();

        return $this->wasAutoConfirmed
            ? 'Urgent order '.$this->order->order_number.' auto-confirmed - '.$arrivalText
            : 'Urgent order waiting for you - '.$arrivalText;
    }

    private function itemsText(): string
    {
        return $this->order->items
            ->map(fn ($orderItem) => $orderItem->quantity.' '.$orderItem->unit.' '.$orderItem->product_name)
            ->join(', ');
    }

    private function url(): ?string
    {
        return Route::has('farmer.orders.show') ? route('farmer.orders.show', $this->order) : null;
    }
}
