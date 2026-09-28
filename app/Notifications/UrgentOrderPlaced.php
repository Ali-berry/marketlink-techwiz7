<?php

namespace App\Notifications;

use App\Helpers\MoneyFormatter;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

// urgent order ki customer receipt - farmer ko pata hai (auto-confirm) ya abhi accept karna hai
class UrgentOrderPlaced extends Notification
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
        return (new MailMessage)
            ->subject('Urgent order '.$this->order->order_number.($this->wasAutoConfirmed ? ' confirmed' : ' placed'))
            ->line($this->headline().'.')
            ->line('Total to pay at pickup: '.MoneyFormatter::format($this->order->total_amount).'.')
            ->action('See your order', $this->url());
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
        $farmer = $this->order->farmer;
        $pickupText = 'pickup at '.$farmer->address.' by '.$this->order->urgentPickupTimeText();

        return $this->wasAutoConfirmed
            ? "Confirmed - {$farmer->stall_name} knows you're coming, {$pickupText}"
            : "Urgent order sent to {$farmer->stall_name}, waiting for confirmation - {$pickupText}";
    }

    private function url(): ?string
    {
        return Route::has('customer.orders.show') ? route('customer.orders.show', $this->order) : null;
    }
}
