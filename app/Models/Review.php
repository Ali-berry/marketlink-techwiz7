<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'customer_id',
        'farmer_profile_id',
        'product_id',
        'order_id',
        'rating',
        'comment',
        'farmer_reply',
        'farmer_replied_at',
        'is_hidden_by_admin',
        'hidden_reason',
    ];

    protected function casts(): array
    {
        return [
            'farmer_replied_at' => 'datetime',
            'is_hidden_by_admin' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(FarmerProfile::class, 'farmer_profile_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isAboutProduct(): bool
    {
        return $this->product_id !== null;
    }
}
