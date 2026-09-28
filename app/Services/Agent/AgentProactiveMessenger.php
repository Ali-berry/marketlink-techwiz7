<?php

namespace App\Services\Agent;

use App\Enums\AgentMessageKind;
use App\Enums\AgentMessageRole;
use App\Enums\UserRole;
use App\Models\AgentConversation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

// agent khud ek message bhejta hai (naya order, farmer sign-up, naya product, pending post).
// Ye kabhi asal action (order, register, product, post) ko fail nahi karega - error yahin ruk jata hai
class AgentProactiveMessenger
{
    public function send(
        User $recipient,
        UserRole $userType,
        string $content,
        string $contextType,
        int $contextId,
        string $contextLabel,
        array $actions,
    ): void {
        try {
            AgentConversation::create([
                'user_id' => $recipient->id,
                'user_type' => $userType->value,
                'role' => AgentMessageRole::Assistant->value,
                'kind' => AgentMessageKind::Proactive->value,
                'content' => $content,
                'context' => ['type' => $contextType, 'id' => $contextId, 'label' => $contextLabel],
                'actions' => $actions,
            ]);
        } catch (Throwable $exception) {
            Log::error('Proactive agent message failed to save', [
                'recipient_id' => $recipient->id,
                'context_type' => $contextType,
                'context_id' => $contextId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
