<?php

namespace App\Models;

use App\Enums\CommunityPostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class CommunityPost extends Model
{
    // post edit nahi hoti, sirf moderate hoti hai
    const UPDATED_AT = null;

    protected $fillable = [
        'author_id',
        'body',
        'image_path',
        'status',
        'is_pinned',
        'pinned_at',
        'rejection_reason',
        'moderated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CommunityPostStatus::class,
            'is_pinned' => 'boolean',
            'pinned_at' => 'datetime',
            'moderated_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommunityPostLike::class, 'post_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CommunityPostComment::class, 'post_id');
    }

    // public feed mein sirf approved posts
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', CommunityPostStatus::Approved->value);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', CommunityPostStatus::Pending->value);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', CommunityPostStatus::Rejected->value);
    }

    // pinned pehle (naya pin upar), phir baqi newest first
    public function scopePinnedFirst(Builder $query): Builder
    {
        return $query->orderByDesc('is_pinned')->orderByDesc('pinned_at')->latest('created_at');
    }

    public function isApproved(): bool
    {
        return $this->status === CommunityPostStatus::Approved;
    }

    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->likes->contains('user_id', $user->id);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
