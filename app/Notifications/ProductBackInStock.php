<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

// jin customers ne favourite karke restock alert on kiya
class ProductBackInStock extends Notification
{
    use Queueable;

    public function __construct(private readonly Product $product)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->product->name.' is back in stock')
            ->greeting('Good news!')
            ->line($this->product->name.' from '.$this->product->farmer->stall_name.' is available again.')
            ->action('Order it before it sells out', $this->url())
            ->line('You\'re getting this because you asked to be told when this product restocks.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'product_id' => $this->product->id,
            'message' => $this->product->name.' from '.$this->product->farmer->stall_name.' is back in stock.',
            'url' => $this->url(),
        ];
    }

    private function url(): ?string
    {
        return Route::has('customer.products.show') ? route('customer.products.show', $this->product) : null;
    }
}
