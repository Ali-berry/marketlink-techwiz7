<?php

namespace Tests\Feature\Agent;

use App\Services\Agent\AdminAgentToolkit;
use App\Services\Agent\FarmerAgentToolkit;
use App\Services\PlaceOrderFromBasket;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// naye "Details" read-only tools - apna hi data dekh sakein, aur galat permission se kuch na mile
class AgentDetailsToolsTest extends TestCase
{
    use CreatesMarketplaceFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_farmer_can_get_details_of_their_own_order(): void
    {
        $market = $this->createMarket();
        $farmer = $this->createApprovedFarmer($market);
        $product = $this->createProduct($farmer, ['name' => 'Carrots']);
        $customer = $this->createCustomer();
        $pickupWindow = $farmer->pickupWindows()->first();

        $order = app(PlaceOrderFromBasket::class)->placeSingleProduct($customer, $product, 3, $pickupWindow, now()->addDay());

        $result = app(FarmerAgentToolkit::class)->execute($farmer->user, 'get_order_details', [
            'order_number' => $order->order_number,
        ]);

        $this->assertSame($order->order_number, $result['order_number']);
        $this->assertSame('Carrots', $result['items'][0]['product_name']);
        $this->assertSame(3, $result['items'][0]['quantity']);
    }

    public function test_farmer_cannot_get_details_of_another_farmers_order(): void
    {
        $market = $this->createMarket();
        $farmerA = $this->createApprovedFarmer($market, ['stall_name' => 'Farmer A Stall']);
        $farmerB = $this->createApprovedFarmer($market, ['stall_name' => 'Farmer B Stall']);
        $productA = $this->createProduct($farmerA);
        $customer = $this->createCustomer();
        $pickupWindow = $farmerA->pickupWindows()->first();

        $orderForFarmerA = app(PlaceOrderFromBasket::class)->placeSingleProduct($customer, $productA, 1, $pickupWindow, now()->addDay());

        $result = app(FarmerAgentToolkit::class)->execute($farmerB->user, 'get_order_details', [
            'order_number' => $orderForFarmerA->order_number,
        ]);

        $this->assertSame("That order doesn't belong to this stall.", $result['error']);
    }

    public function test_admin_with_manage_farmers_can_get_farmer_details(): void
    {
        $market = $this->createMarket();
        $farmer = $this->createApprovedFarmer($market, ['stall_name' => 'Green Acres']);
        $admin = $this->createAdminWithRole('support-admin');

        $result = app(AdminAgentToolkit::class)->execute($admin, 'get_farmer_details', [
            'farmer_name' => 'Green Acres',
        ]);

        $this->assertSame('Green Acres', $result['farmer_name']);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('markets', $result);
    }

    public function test_admin_without_manage_farmers_cannot_get_farmer_details(): void
    {
        $market = $this->createMarket();
        $this->createApprovedFarmer($market, ['stall_name' => 'Green Acres']);
        $communityModerator = $this->createAdminWithRole('community-moderator');

        $result = app(AdminAgentToolkit::class)->execute($communityModerator, 'get_farmer_details', [
            'farmer_name' => 'Green Acres',
        ]);

        $this->assertStringContainsString("don't have permission", $result['error']);
    }

    public function test_admin_with_moderate_content_can_get_product_details(): void
    {
        $market = $this->createMarket();
        $farmer = $this->createApprovedFarmer($market);
        $product = $this->createProduct($farmer, ['name' => 'Spinach']);
        $admin = $this->createAdminWithRole('support-admin');

        $result = app(AdminAgentToolkit::class)->execute($admin, 'get_product_details', [
            'product_id' => $product->id,
        ]);

        $this->assertSame('Spinach', $result['product_name']);
        $this->assertArrayHasKey('stock_quantity', $result);
        $this->assertFalse($result['hidden']);
    }

    public function test_admin_without_moderate_content_cannot_get_product_details(): void
    {
        $market = $this->createMarket();
        $farmer = $this->createApprovedFarmer($market);
        $product = $this->createProduct($farmer);
        $communityModerator = $this->createAdminWithRole('community-moderator');

        $result = app(AdminAgentToolkit::class)->execute($communityModerator, 'get_product_details', [
            'product_id' => $product->id,
        ]);

        $this->assertStringContainsString("don't have permission", $result['error']);
    }
}
