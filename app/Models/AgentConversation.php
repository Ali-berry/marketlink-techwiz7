<?php

namespace App\Models;

use App\Enums\AgentMessageKind;
use App\Enums\AgentMessageRole;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentConversation extends Model
{
    // har row ek chat turn hai, baad mein kabhi edit nahi hoti
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'user_type',
        'role',
        'kind',
        'content',
        'context',
        'actions',
        'reply_to_message_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'user_type' => UserRole::class,
            'role' => AgentMessageRole::class,
            'kind' => AgentMessageKind::class,
            'context' => 'array',
            'actions' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // user ne kis message ka jawab diya - dono taraf se zaroorat parti hai
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_message_id');
    }

    // ek user ki ek agent ke saath history - doosre agent ki history mix nahi hoti
    public function scopeForUser(Builder $query, User $user, UserRole $userType): Builder
    {
        return $query->where('user_id', $user->id)->where('user_type', $userType->value);
    }

    public function scopeProactive(Builder $query): Builder
    {
        return $query->where('kind', AgentMessageKind::Proactive->value);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
