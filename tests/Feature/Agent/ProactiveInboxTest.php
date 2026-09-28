<?php

namespace Tests\Feature\Agent;

use App\Enums\AgentMessageKind;
use App\Enums\UserRole;
use App\Models\AgentConversation;
use App\Models\User;
use App\Services\Agent\AgentProactiveMessenger;
use App\Services\PlaceOrderFromBasket;
use App\Services\UrgentOrderService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// har event pe sahi role/permission wale ko hi proactive message bane, aur asal action kabhi na ruke
class ProactiveInboxTest extends TestCase
{
    use CreatesMarketplaceFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_normal_order_notifies_the_farmer_agent_inbox(): void
    {
        $market = $this->createMarket();
        $farmer = $this->createApprovedFarmer($market);
        $product = $this->createProduct($farmer);
        $customer = $this->createCustomer();
        $pickupWindow = $farmer->pickupWindows()->first();

        app(PlaceOrderFromBasket::class)->placeSingleProduct($customer, $product, 2, $pickupWindow, now()->addDay());

        $inboxMessage = AgentConversation::where('user_id', $farmer->user_id)
            ->where('user_type', UserRole::Farmer->value)
            ->proactive()
            ->first();

        $this->assertNotNull($inboxMessage);
        $this->assertSame('order', $inboxMessage->context['type']);
        $this->assertSame(['Accept', 'Decline', 'Details'], $inboxMessage->actions);
        $this->assertStringContainsString('carrots', strtolower($inboxMessage->content));
    }

    public function test_urgent_order_not_auto_confirmed_notifies_the_farmer_agent_inbox(): void
    {
        $market = $this->createMarket();
        $farmer = $this->createApprovedFarmer($market, ['ai_auto_confirms_urgent' => false]);
        $product = $this->createProduct($farmer);
        $customer = $this->createCustomer();

        app(UrgentOrderService::class)->placeUrgentOrder($customer, $product, 1, 30);

        $this->assertEquals(
            1,
            AgentConversation::where('user_id', $farmer->user_id)->proactive()->count(),
        );
    }

    public function test_auto_confirmed_urgent_order_does_not_need_farmer_attention(): void
    {
        $market = $this->createMarket();
        $farmer = $this->createApprovedFarmer($market, ['ai_auto_confirms_urgent' => true]);
        $product = $this->createProduct($farmer);
        $customer = $this->createCustomer();

        app(UrgentOrderService::class)->placeUrgentOrder($customer, $product, 1, 30);

        $this->assertEquals(
            0,
            AgentConversation::where('user_id', $farmer->user_id)->proactive()->count(),
        );
    }

    public function test_new_farmer_signup_notifies_only_admins_with_manage_farmers_permission(): void
    {
        $supportAdmin = $this->createAdminWithRole('support-admin');
        $communityModerator = $this->createAdminWithRole('community-moderator');
        $market = $this->createMarket();

        $response = $this->post(route('register'), [
            'account_type' => 'farmer',
            'stall_name' => 'New Farm Co.',
            'contact_person' => 'Naya Farmer',
            'market_id' => $market->id,
            'email' => 'newfarmer@example.test',
            'phone' => '555-1000',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertRedirect();

        $this->assertEquals(1, AgentConversation::where('user_id', $supportAdmin->id)->proactive()->count());
        $this->assertEquals(0, AgentConversation::where('user_id', $communityModerator->id)->proactive()->count());
    }

    public function test_new_product_notifies_only_admins_with_moderate_content_permission(): void
    {
        $supportAdmin = $this->createAdminWithRole('support-admin');
        $communityModerator = $this->createAdminWithRole('community-moderator');
        $market = $this->createMarket();
        $farmer = $this->createApprovedFarmer($market);

        $response = $this->actingAs($farmer->user)->post(route('farmer.products.store'), [
            'product_category_id' => $this->createProduct($farmer)->product_category_id,
            'name' => 'Fresh Spinach',
            'price' => 3.25,
            'unit' => 'bunch',
            'stock_quantity' => 10,
            'weekly_default_quantity' => 10,
            'availability' => 'available',
        ]);

        $response->assertRedirect();

        $this->assertEquals(1, AgentConversation::where('user_id', $supportAdmin->id)->proactive()->count());
        $this->assertEquals(0, AgentConversation::where('user_id', $communityModerator->id)->proactive()->count());
    }

    public function test_pending_community_post_notifies_only_admins_with_moderate_community_posts_permission(): void
    {
        $supportAdmin = $this->createAdminWithRole('support-admin');
        $communityModerator = $this->createAdminWithRole('community-moderator');
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer)->post(route('community.posts.store'), [
            'body' => 'Anyone know a good pumpkin stall this weekend?',
        ]);

        $response->assertRedirect();

        $this->assertEquals(1, AgentConversation::where('user_id', $communityModerator->id)->proactive()->count());
        $this->assertEquals(0, AgentConversation::where('user_id', $supportAdmin->id)->proactive()->count());
    }

    public function test_proactive_message_failure_never_throws(): void
    {
        // agent_conversations.user_id foreign key todne ke liye jaan boojh ke ghalat id - insert fail hoga
        $ghostRecipient = new User(['name' => 'Ghost', 'email' => 'ghost@example.test']);
        $ghostRecipient->id = 999999;
        $ghostRecipient->exists = true;

        app(AgentProactiveMessenger::class)->send(
            recipient: $ghostRecipient,
            userType: UserRole::Farmer,
            content: 'This should never reach the database.',
            contextType: 'order',
            contextId: 1,
            contextLabel: 'ML-0001',
            actions: ['Accept', 'Decline'],
        );

        $this->assertEquals(0, AgentConversation::where('kind', AgentMessageKind::Proactive->value)->count());
    }
}
