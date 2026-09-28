<?php

namespace App\Http\Controllers\Customer;

use App\Enums\UserRole;
use App\Http\Controllers\BaseAgentController;
use App\Models\User;
use App\Services\Agent\CustomerAgentToolkit;
use App\Services\Agent\GroqClient;
use App\Services\Agent\RuleBasedOrderFallback;

class AgentController extends BaseAgentController
{
    public function __construct(
        GroqClient $groq,
        private readonly CustomerAgentToolkit $toolkit,
        private readonly RuleBasedOrderFallback $ruleBasedOrderFallback,
    ) {
        parent::__construct($groq);
    }

    // AI bilkul band hai - sirf saaf saaf pre-order ho to keyword match se laga do
    protected function attemptEmergencyFallback(User $actor, string $userMessage): ?string
    {
        return $this->ruleBasedOrderFallback->tryPlaceOrder($actor, $userMessage);
    }

    protected function userType(): UserRole
    {
        return UserRole::Customer;
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
        return "You are MarketLink's assistant for customers. You help {$actor->firstName()} browse "
            ."fresh produce, place pre-orders, track their orders, favourite farmers/products and message "
            ."stalls - using the tools you're given for anything that reads or changes real data. Never make "
            ."up product names, prices or order details - always call a tool to check first.\n\n"
            .$this->urgentOrderRules()
            .$this->commonAgentRules();
    }

    private function urgentOrderRules(): string
    {
        return <<<'PROMPT'
            Urgent orders:
            - When the customer says they need something "urgently", "right now", "within X minutes", "within the hour",
              "abhi chahiye", "jaldi" or similar, call find_urgent_pickup - not a normal pre-order.
            - List the options with the farmer, price, distance in miles (if known), and whether it "confirms instantly"
              or "farmer will confirm". Nearest first.
            - Ask how many they need if they didn't say. If they gave a time ("within 30 minutes") use it for
              pickup_in_minutes, otherwise use 30. It has to be between 15 and 120.
            - Before place_urgent_order, show a summary (product, quantity, farmer, pickup address, total, and
              "pickup within X minutes of confirming"), mention it can only be cancelled in the first 5 minutes, then
              ask "Should I go ahead?". Never write an exact clock time ("by 4:36 PM") in this summary - you don't
              know when the order will actually be placed.
            - After placing it, give the exact pickup time only from "pickup_by" in the place_urgent_order result,
              say clearly whether it's already confirmed or waiting for the farmer, and where to go. If the result has
              a "ready_update", pass that on too.
            - If nobody can do it right now, say so and offer a normal pre-order instead.

            PROMPT;
    }
}
