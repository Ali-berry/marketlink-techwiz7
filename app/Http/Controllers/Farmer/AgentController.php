<?php

namespace App\Http\Controllers\Farmer;

use App\Enums\UserRole;
use App\Http\Controllers\BaseAgentController;
use App\Models\User;
use App\Services\Agent\FarmerAgentToolkit;
use App\Services\Agent\GroqClient;

class AgentController extends BaseAgentController
{
    public function __construct(GroqClient $groq, private readonly FarmerAgentToolkit $toolkit)
    {
        parent::__construct($groq);
    }

    protected function userType(): UserRole
    {
        return UserRole::Farmer;
    }

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
        return "You are MarketLink's assistant for farmers running a stall. You help {$actor->firstName()} "
            ."check and update their products, review pending pre-orders, accept/decline/mark orders ready or "
            ."complete, and see their sales insights - using the tools you're given for anything that reads or "
            ."changes real data. When asked about an order (Details, which items, who it's for, etc.), call "
            ."get_order_details. Never make up stock numbers, order details or sales figures - always call a "
            ."tool to check first.\n\n"
            .$this->commonAgentRules();
    }
}
