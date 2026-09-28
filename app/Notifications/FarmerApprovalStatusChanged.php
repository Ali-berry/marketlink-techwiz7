<?php

namespace App\Notifications;

use App\Enums\FarmerApprovalStatus;
use App\Models\FarmerProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

// admin ne stall approve, suspend ya dobara approve kiya
class FarmerApprovalStatusChanged extends Notification
{
    use Queueable;

    public function __construct(private readonly FarmerProfile $farmer)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject('Your MarketLink stall is now '.$this->farmer->approval_status->label());

        return match ($this->farmer->approval_status) {
            FarmerApprovalStatus::Approved => $mail->greeting('Good news!')
                ->line('Your stall "'.$this->farmer->stall_name.'" has been approved.')
                ->line('Customers can now see your products and place pre-orders.'),
            FarmerApprovalStatus::Suspended => $mail->greeting('Your stall has been suspended')
                ->line('Your stall "'.$this->farmer->stall_name.'" has been suspended and is hidden from customers.')
                ->when($this->farmer->suspension_reason, fn (MailMessage $m) => $m->line('Reason: '.$this->farmer->suspension_reason))
                ->line('Contact the MarketLink team if you think this is a mistake.'),
            FarmerApprovalStatus::Pending => $mail->line('Your stall is waiting for admin approval.'),
        };
    }

    public function toArray(object $notifiable): array
    {
        return [
            'farmer_id' => $this->farmer->id,
            'message' => 'Your stall is now "'.$this->farmer->approval_status->label().'".',
            'url' => Route::has('farmer.stall.edit') ? route('farmer.stall.edit') : null,
        ];
    }
}
