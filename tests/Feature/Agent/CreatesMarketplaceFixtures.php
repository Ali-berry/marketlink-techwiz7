<?php

namespace Tests\Feature\Agent;

use App\Enums\FarmerApprovalStatus;
use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\Market;
use App\Models\PickupWindow;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Support\Str;

// proactive-inbox tests ko asli Market / FarmerProfile / Product banane hain, factories nahi hain -
// is liye ye chhote helpers, DemoAccountSeeder / DemoOrderSeeder jaisa pattern
trait CreatesMarketplaceFixtures
{
    private function createMarket(): Market
    {
        return Market::create([
            'name' => 'Test Farmers Market',
            'slug' => Str::random(10),
            'address' => '1 Market Street',
            'city' => 'Dallas',
            'latitude' => 32.7767,
            'longitude' => -96.7970,
            'operating_days' => ['saturday'],
            'opens_at' => '08:00',
            'closes_at' => '13:00',
            'timezone' => 'America/Chicago',
            'is_active' => true,
        ]);
    }

    private function createApprovedFarmer(Market $market, array $overrides = []): FarmerProfile
    {
        $farmerUser = User::factory()->create(['role' => UserRole::Farmer->value]);

        $farmer = FarmerProfile::create([
            'user_id' => $farmerUser->id,
            'stall_name' => $overrides['stall_name'] ?? 'Test Stall',
            'slug' => Str::random(10),
            'contact_person' => $farmerUser->name,
            'address' => $market->address,
            'latitude' => $market->latitude,
            'longitude' => $market->longitude,
            'order_cutoff_hours' => 24,
            'approval_status' => FarmerApprovalStatus::Approved->value,
            'approved_at' => now(),
            // urgent hours poore din khule - test mein "abhi" hamesha andar aaye
            'accepts_urgent_orders' => $overrides['accepts_urgent_orders'] ?? true,
            'ai_auto_confirms_urgent' => $overrides['ai_auto_confirms_urgent'] ?? false,
            'urgent_pickup_starts_at' => '00:00:00',
            'urgent_pickup_ends_at' => '23:59:00',
            'max_urgent_orders_per_hour' => 20,
        ]);

        $farmer->markets()->attach($market->id);

        PickupWindow::create([
            'farmer_profile_id' => $farmer->id,
            'market_id' => $market->id,
            'day_of_week' => now()->dayOfWeek,
            'starts_at' => '08:00',
            'ends_at' => '13:00',
            'max_orders' => 20,
        ]);

        return $farmer->fresh();
    }

    private function createProduct(FarmerProfile $farmer, array $overrides = []): Product
    {
        $category = ProductCategory::firstOrCreate(
            ['slug' => 'test-category'],
            ['name' => 'Test Category', 'sort_order' => 1],
        );

        return Product::create([
            'farmer_profile_id' => $farmer->id,
            'product_category_id' => $category->id,
            'name' => $overrides['name'] ?? 'Carrots',
            'slug' => Str::random(10),
            'price' => $overrides['price'] ?? 2.50,
            'unit' => $overrides['unit'] ?? 'kg',
            'stock_quantity' => $overrides['stock_quantity'] ?? 20,
            'weekly_default_quantity' => 20,
            'availability' => 'available',
        ]);
    }

    private function createCustomer(): User
    {
        return User::factory()->create(['role' => UserRole::Customer->value]);
    }

    private function createAdminWithRole(string $roleName): User
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);
        $admin->assignRole($roleName);

        return $admin;
    }
}
