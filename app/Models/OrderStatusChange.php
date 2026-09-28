<?php

namespace App\Models;

use App\Services\OrderStatusUpdater;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusChange extends Model
{
    // sirf created_at hai, rows edit nahi hoti
    const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'changed_by_user_id',
        'from_status',
        'to_status',
        'note',
        'created_at',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    // user null ho aur AI wala note ho - autoConfirmUrgentOrder() / autoMarkUrgentOrderReady() dekho
    public function wasMadeByMarketLinkAi(): bool
    {
        $marketLinkAiNotes = [OrderStatusUpdater::URGENT_AUTO_CONFIRM_NOTE, OrderStatusUpdater::URGENT_AUTO_READY_NOTE];

        return $this->changed_by_user_id === null && in_array($this->note, $marketLinkAiNotes, true);
    }
}
