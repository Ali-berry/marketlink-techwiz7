<?php

namespace Tests\Feature\Agent;

use App\Enums\UserRole;
use App\Models\AgentConversation;
use App\Models\FarmerProfile;
use App\Models\User;
use Database\Seeders\CommunityPostSeeder;
use Database\Seeders\DemoAccountSeeder;
use Database\Seeders\DemoOrderSeeder;
use Database\Seeders\ProactiveInboxSeeder;
use Database\Seeders\ProductCategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TexasMarketsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// demo seed ke baad Green Valley ke agent chat mein 2 alag naye order wale unread proactive message hon
class ProactiveInboxSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_green_valley_has_two_unread_proactive_order_messages_after_seeding(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ProductCategorySeeder::class);
        $this->seed(TexasMarketsSeeder::class);
        $this->seed(DemoAccountSeeder::class);
        $this->seed(DemoOrderSeeder::class);
        $this->seed(CommunityPostSeeder::class);
        $this->seed(ProactiveInboxSeeder::class);

        $greenValley = FarmerProfile::where('slug', 'green-valley-farm')->firstOrFail();

        $orderMessages = AgentConversation::where('user_id', $greenValley->user_id)
            ->where('user_type', UserRole::Farmer->value)
            ->proactive()
            ->unread()
            ->get();

        $this->assertCount(2, $orderMessages);
        $this->assertEqualsCanonicalizing(
            ['order', 'order'],
            $orderMessages->map(fn ($message) => $message->context['type'])->all(),
        );
        // do alag orders, ek jaisa context id repeat nahi hona chahiye
        $this->assertCount(2, $orderMessages->pluck('context.id')->unique());
    }
}
