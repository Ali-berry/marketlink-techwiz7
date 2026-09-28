<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = [
        'customer_id',
        'farmer_profile_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(FarmerProfile::class, 'farmer_profile_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    // inbox list pe preview ke liye, saare messages load na karne paren
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    // doosri taraf ke woh messages jo is user ne abhi nahi khole
    public function unreadCountFor(User $viewer): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $viewer->id)
            ->whereNull('read_at')
            ->count();
    }

    // thread khulne pe us taraf ka badge clear
    public function markReadFor(User $viewer): void
    {
        $this->messages()
            ->where('sender_id', '!=', $viewer->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
