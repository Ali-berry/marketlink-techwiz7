<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = [
        'created_by_user_id',
        'title',
        'body',
        'audience',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // is audience ke published announcements, jaise visibleTo('farmers')
    public function scopeVisibleTo(Builder $query, string $audience): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereIn('audience', ['everyone', $audience]);
    }
}
