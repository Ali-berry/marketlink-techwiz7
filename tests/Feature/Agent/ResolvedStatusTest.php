<?php

namespace Tests\Feature\Agent;

use App\Enums\UserRole;
use App\Models\CommunityPost;
use App\Services\Agent\AdminAgentToolkit;
use App\Services\Agent\AgentProactiveMessenger;
use App\Services\Agent\FarmerAgentToolkit;
use App\Services\CommunityPostModerator;
use App\Services\FarmerApprovalService;
use App\Services\PlaceOrderFromBasket;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// kaam ho jaane ke baad history() proactive message ka "resolved" status bhejta hai - is ke sahi hone ka test
class ResolvedStatusTest extends TestCase
{
    use CreatesMarketplaceFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_accepted_order_shows_resolved_label_in_history(): void
    {
        $market = $this->createMarket();
        $farmer = $this->createApprovedFarmer($market);
        $product = $this->createProduct($farmer);
        $customer = $this->createCustomer();
        $pickupWindow = $farmer->pickupWindows()->first();

        $order = app(PlaceOrderFromBasket::class)->placeSingleProduct($customer, $product, 1, $pickupWindow, now()->addDay());

        app(FarmerAgentToolkit::class)->execute($farmer->user, 'update_order_status', [
            'order_id' => $order->id,
            'new_status' => 'accept',
        ]);

        $history = $this->actingAs($farmer->user)->getJson(route('farmer.agent.history'))->json('messages');
        $proactiveMessage = collect($history)->firstWhere('kind', 'proactive');

        $this->assertSame('✓ Accepted', $proactiveMessage['resolved']);
    }

    public function test_still_placed_order_has_no_resolved_label(): void
    {
        $market = $this->createMarket();
        $farmer = $this->createApprovedFarmer($market);
        $product = $this->createProduct($farmer);
        $customer = $this->createCustomer();
        $pickupWindow = $farmer->pickupWindows()->first();

        app(PlaceOrderFromBasket::class)->placeSingleProduct($customer, $product, 1, $pickupWindow, now()->addDay());

        $history = $this->actingAs($farmer->user)->getJson(route('farmer.agent.history'))->json('messages');
        $proactiveMessage = collect($history)->firstWhere('kind', 'proactive');

        $this->assertNull($proactiveMessage['resolved']);
    }

    public function test_approved_farmer_signup_shows_resolved_label(): void
    {
        $market = $this->createMarket();
        $farmer = $this->createApprovedFarmer($market);
        $farmer->update(['approval_status' => 'pending']);
        $admin = $this->createAdminWithRole('super-admin');

        app(FarmerApprovalService::class)->notifyAdminsOfNewSignup($farmer->fresh());
        app(FarmerApprovalService::class)->approve($farmer->fresh());

        $history = $this->actingAs($admin)->getJson(route('admin.agent.history'))->json('messages');
        $proactiveMessage = collect($history)->firstWhere('kind', 'proactive');

        $this->assertSame('✓ Approved', $proactiveMessage['resolved']);
    }

    public function test_hidden_product_shows_resolved_label(): void
    {
        $market = $this->createMarket();
        $farmer = $this->createApprovedFarmer($market);
        $product = $this->createProduct($farmer);
        $admin = $this->createAdminWithRole('super-admin');

        app(AgentProactiveMessenger::class)->send(
            recipient: $admin,
            userType: UserRole::Admin,
            content: 'New product added.',
            contextType: 'product',
            contextId: $product->id,
            contextLabel: $product->name,
            actions: ['Looks okay', 'Hide it', 'Details'],
        );

        app(AdminAgentToolkit::class)->execute($admin, 'moderate_item', [
            'item_type' => 'product',
            'item_id' => $product->id,
            'action' => 'hide',
            'reason' => 'Looks wrong',
        ]);

        $history = $this->actingAs($admin)->getJson(route('admin.agent.history'))->json('messages');
        $proactiveMessage = collect($history)->firstWhere('kind', 'proactive');

        $this->assertSame('✓ Hidden', $proactiveMessage['resolved']);
    }

    public function test_deleted_community_post_shows_no_longer_available(): void
    {
        $author = $this->createCustomer();
        $post = CommunityPost::create(['author_id' => $author->id, 'body' => 'Test post', 'status' => 'pending']);
        $admin = $this->createAdminWithRole('community-moderator');

        app(AgentProactiveMessenger::class)->send(
            recipient: $admin,
            userType: UserRole::Admin,
            content: 'New post waiting.',
            contextType: 'community_post',
            contextId: $post->id,
            contextLabel: 'Test post',
            actions: ['Approve', 'Reject', 'Details'],
        );

        app(CommunityPostModerator::class)->delete($post);

        $history = $this->actingAs($admin)->getJson(route('admin.agent.history'))->json('messages');
        $proactiveMessage = collect($history)->firstWhere('kind', 'proactive');

        $this->assertSame('No longer available', $proactiveMessage['resolved']);
    }
}
