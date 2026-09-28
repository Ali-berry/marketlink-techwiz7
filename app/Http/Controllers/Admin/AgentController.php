<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\BaseAgentController;
use App\Models\User;
use App\Services\Agent\AdminAgentToolkit;
use App\Services\Agent\GroqClient;

class AgentController extends BaseAgentController
{
    public function __construct(GroqClient $groq, private readonly AdminAgentToolkit $toolkit)
    {
        parent::__construct($groq);
    }

    protected function userType(): UserRole
    {
        return UserRole::Admin;
    }

    // permission filter toolkit ke andar hai - Super Admin, Support Admin aur Community Moderator ko alag list milti hai
    protected function toolSchemasFor(User $actor): array
    {
        return array_column($this->toolkit->schemas($actor), 'schema');
    }

    protected function executeTool(User $actor, string $toolName, array $arguments): array
    {
        return $this->toolkit->execute($actor, $toolName, $arguments);
    }

    protected function systemPrompt(User $actor): string
    {
        return "You are MarketLink's assistant for admin staff. {$actor->firstName()} may only have some of "
            ."the platform's admin permissions - you were only given the tools that match what they're allowed "
            ."to do. If they ask about something you have no tool for, tell them plainly that they don't have "
            ."permission for that instead of guessing or apologising excessively. When asked for Details on a "
            ."farmer, product or community post, call get_farmer_details, get_product_details or get_post_details. "
            ."Never make up farmer, moderation or report data - always call a tool to check first.\n\n"
            .$this->commonAgentRules();
    }
}
