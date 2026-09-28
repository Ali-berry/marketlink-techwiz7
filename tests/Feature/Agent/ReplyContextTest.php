<?php

namespace Tests\Feature\Agent;

use App\Enums\UserRole;
use App\Models\AgentConversation;
use App\Services\Agent\FarmerAgentToolkit;
use App\Services\PlaceOrderFromBasket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// reply_to_message_id se AI ko context milta hai, magar ownership hamesha wahi purane rules decide karte hain
class ReplyContextTest extends TestCase
{
    use CreatesMarketplaceFixtures, RefreshDatabase;

    public function test_replying_to_a_proactive_order_message_tells_groq_which_order_it_is(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'Got it - should I go ahead and accept it?']]],
            ]),
        ]);

        $market = $this->createMarket();
        $farmer = $this->createApprovedFarmer($market);
        $product = $this->createProduct($farmer);
        $customer = $this->createCustomer();
        $pickupWindow = $farmer->pickupWindows()->first();

        app(PlaceOrderFromBasket::class)->placeSingleProduct($customer, $product, 2, $pickupWindow, now()->addDay());

        $proactiveMessage = AgentConversation::where('user_id', $farmer->user_id)->proactive()->firstOrFail();

        $response = $this->actingAs($farmer->user)->postJson(route('farmer.agent.chat'), [
            'message' => 'Accept',
            'reply_to_message_id' => $proactiveMessage->id,
        ]);

        $response->assertOk();

        Http::assertSent(function ($request) use ($proactiveMessage) {
            $systemNotes = collect($request->data()['messages'])
                ->where('role', 'system')
                ->pluck('content');

            return $systemNotes->contains(fn (string $note) => str_contains($note, "type=order, id={$proactiveMessage->context['id']}")
                && str_contains($note, "Call it by: {$proactiveMessage->context['label']}"));
        });
    }

    // widget ke quick-action buttons (Accept/Decline/Details) history() se mila hua id use karte hain -
    // ye poora loop wahi simulate karta hai: history se id nikalo, phir button jaisa POST bhejo
    public function test_quick_action_button_click_sends_the_history_message_id_as_reply_context(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'Got it - should I go ahead and accept it?']]],
            ]),
        ]);

        $market = $this->createMarket();
        $farmer = $this->createApprovedFarmer($market);
        $product = $this->createProduct($farmer);
        $customer = $this->createCustomer();
        $pickupWindow = $farmer->pickupWindows()->first();

        app(PlaceOrderFromBasket::class)->placeSingleProduct($customer, $product, 2, $pickupWindow, now()->addDay());

        // widget ka loadHistory() yahi endpoint call karta hai - id yahin se milti hai, model se seedha nahi
        $historyMessages = $this->actingAs($farmer->user)->getJson(route('farmer.agent.history'))->json('messages');
        $proactiveMessage = collect($historyMessages)->firstWhere('kind', 'proactive');

        $this->assertNotNull($proactiveMessage, 'history() should return the proactive order message.');
        $this->assertIsInt($proactiveMessage['id'], 'each history message must carry its own numeric id.');
        $this->assertSame(['Accept', 'Decline', 'Details'], $proactiveMessage['actions']);

        // "Accept" button jaisa - sendMessage(actionLabel, messageId) isi id ko reply_to_message_id bhejta hai
        $this->postJson(route('farmer.agent.chat'), [
            'message' => 'Accept',
            'reply_to_message_id' => $proactiveMessage['id'],
        ])->assertOk();

        $savedUserMessage = AgentConversation::where('user_id', $farmer->user_id)
            ->where('content', 'Accept')
            ->firstOrFail();

        // "backend tak nahi ja raha" wala bug - yahi column null reh jata to fail hota
        $this->assertSame($proactiveMessage['id'], $savedUserMessage->reply_to_message_id);

        // history() sirf id/actions dikhata hai, order ka context AgentConversation model se hi milta hai
        $orderContext = AgentConversation::find($proactiveMessage['id'])->context;

        Http::assertSent(fn ($request) => collect($request->data()['messages'])
            ->where('role', 'system')
            ->pluck('content')
            ->contains(fn (string $note) => str_contains($note, "type=order, id={$orderContext['id']}")
                && str_contains($note, "Call it by: {$orderContext['label']}")));
    }

    public function test_replying_to_another_users_message_is_ignored(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'Sure, how can I help?']]],
            ]),
        ]);

        $market = $this->createMarket();
        $farmerA = $this->createApprovedFarmer($market, ['stall_name' => 'Farmer A Stall']);
        $farmerB = $this->createApprovedFarmer($market, ['stall_name' => 'Farmer B Stall']);

        $farmerAsMessage = AgentConversation::create([
            'user_id' => $farmerA->user_id,
            'user_type' => UserRole::Farmer->value,
            'role' => 'assistant',
            'kind' => 'proactive',
            'content' => 'New pre-order for farmer A.',
            'context' => ['type' => 'order', 'id' => 1, 'label' => 'ML-0001'],
            'actions' => ['Accept', 'Decline'],
        ]);

        // Farmer B, Farmer A ke message id se reply bhejne ki koshish kar raha hai
        $this->actingAs($farmerB->user)->postJson(route('farmer.agent.chat'), [
            'message' => 'Accept',
            'reply_to_message_id' => $farmerAsMessage->id,
        ])->assertOk();

        $farmerBsSavedMessage = AgentConversation::where('user_id', $farmerB->user_id)
            ->where('role', 'user')
            ->firstOrFail();

        $this->assertNull($farmerBsSavedMessage->reply_to_message_id);
    }

    public function test_farmer_toolkit_still_blocks_actions_on_another_farmers_order(): void
    {
        $market = $this->createMarket();
        $farmerA = $this->createApprovedFarmer($market, ['stall_name' => 'Farmer A Stall']);
        $farmerB = $this->createApprovedFarmer($market, ['stall_name' => 'Farmer B Stall']);
        $productA = $this->createProduct($farmerA);
        $customer = $this->createCustomer();
        $pickupWindow = $farmerA->pickupWindows()->first();

        $orderForFarmerA = app(PlaceOrderFromBasket::class)
            ->placeSingleProduct($customer, $productA, 1, $pickupWindow, now()->addDay());

        // farmer B ka actor, farmer A ke order id se AI tool call karwane ki koshish - system note kuch bhi kahe,
        // toolkit apni relation se hi check karta hai
        $result = app(FarmerAgentToolkit::class)->execute($farmerB->user, 'update_order_status', [
            'order_id' => $orderForFarmerA->id,
            'new_status' => 'accept',
        ]);

        $this->assertSame("That order doesn't belong to this stall.", $result['error']);
    }
}
