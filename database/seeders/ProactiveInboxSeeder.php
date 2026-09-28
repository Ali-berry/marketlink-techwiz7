<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Helpers\MoneyFormatter;
use App\Models\AgentConversation;
use App\Models\CommunityPost;
use App\Models\FarmerProfile;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Agent\AgentProactiveMessenger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

// demo ke liye proactive inbox messages - DemoOrderSeeder aur CommunityPostSeeder ke baad chalta hai
// taake reference ke liye order/post already maujood hon
class ProactiveInboxSeeder extends Seeder
{
    public function run(): void
    {
        $proactiveMessenger = app(AgentProactiveMessenger::class);

        $this->seedNewOrderMessage($proactiveMessenger);
        $this->seedFarmerSignupMessage($proactiveMessenger);
        $this->seedNewProductMessage($proactiveMessenger);
        $this->seedPendingPostMessage($proactiveMessenger);
    }

    // Green Valley ke dono placed orders (DemoOrderSeeder) - alag customers, taake demo mein Reply se
    // sirf ek order accept kiya ja sake
    private function seedNewOrderMessage(AgentProactiveMessenger $proactiveMessenger): void
    {
        $orders = Order::where('farmer_profile_id', FarmerProfile::where('slug', 'green-valley-farm')->value('id'))
            ->where('status', 'placed')
            ->with(['customer', 'items'])
            ->get();

        foreach ($orders as $order) {
            if ($this->alreadySent($order->farmer->user_id, UserRole::Farmer, 'order', $order->id)) {
                continue;
            }

            $proactiveMessenger->send(
                recipient: $order->farmer->user,
                userType: UserRole::Farmer,
                content: "New pre-order {$order->order_number} from {$order->customer->firstName()} - "
                    .$order->itemsSummaryText().', pickup '.$order->pickupSummary().'. Accept or decline?',
                contextType: 'order',
                contextId: $order->id,
                contextLabel: $order->order_number,
                actions: ['Accept', 'Decline', 'Details'],
            );
        }
    }

    // Wildflower Honey Co. DemoAccountSeeder mein jaan boojh ke pending banaya gaya hai
    private function seedFarmerSignupMessage(AgentProactiveMessenger $proactiveMessenger): void
    {
        $wildflower = FarmerProfile::where('slug', 'wildflower-honey-co')->first();
        $superAdmin = User::where('email', 'admin@marketlink.test')->first();

        if (! $wildflower || ! $superAdmin || $this->alreadySent($superAdmin->id, UserRole::Admin, 'farmer', $wildflower->id)) {
            return;
        }

        $city = $wildflower->homeMarket()?->city ?? $wildflower->address;

        $proactiveMessenger->send(
            recipient: $superAdmin,
            userType: UserRole::Admin,
            content: "New farmer {$wildflower->stall_name} signed up ({$city}). Review and approve?",
            contextType: 'farmer',
            contextId: $wildflower->id,
            contextLabel: $wildflower->stall_name,
            actions: ['Approve', 'Decline', 'Details'],
        );
    }

    // Green Valley ka koi maujooda product - support admin ke paas moderate-content hai
    private function seedNewProductMessage(AgentProactiveMessenger $proactiveMessenger): void
    {
        $product = Product::whereHas('farmer', fn ($query) => $query->where('slug', 'green-valley-farm'))
            ->where('name', 'Fresh mint')
            ->first();
        $supportAdmin = User::where('email', 'support@marketlink.test')->first();

        if (! $product || ! $supportAdmin || $this->alreadySent($supportAdmin->id, UserRole::Admin, 'product', $product->id)) {
            return;
        }

        $proactiveMessenger->send(
            recipient: $supportAdmin,
            userType: UserRole::Admin,
            content: "{$product->farmer->stall_name} added a new product: {$product->name}, "
                .MoneyFormatter::format($product->price)."/{$product->unit}. Looks okay, or should I hide it?",
            contextType: 'product',
            contextId: $product->id,
            contextLabel: $product->name,
            actions: ['Looks okay', 'Hide it', 'Details'],
        );
    }

    // Bilal ka pending post CommunityPostSeeder mein bana hai - community moderator ke intezar mein
    private function seedPendingPostMessage(AgentProactiveMessenger $proactiveMessenger): void
    {
        $pendingPost = CommunityPost::pending()->where('author_id', User::where('email', 'bilal@marketlink.test')->value('id'))->first();
        $communityMod = User::where('email', 'community-mod@marketlink.test')->first();

        if (! $pendingPost || ! $communityMod || $this->alreadySent($communityMod->id, UserRole::Admin, 'community_post', $pendingPost->id)) {
            return;
        }

        $proactiveMessenger->send(
            recipient: $communityMod,
            userType: UserRole::Admin,
            content: "New community post from {$pendingPost->author->name} is waiting: '"
                .Str::limit($pendingPost->body, 60)."'. Approve or reject?",
            contextType: 'community_post',
            contextId: $pendingPost->id,
            contextLabel: Str::limit($pendingPost->body, 40),
            actions: ['Approve', 'Reject', 'Details'],
        );
    }

    // dobara seed karne pe messages double na hon
    private function alreadySent(int $userId, UserRole $userType, string $contextType, int $contextId): bool
    {
        return AgentConversation::where('user_id', $userId)
            ->where('user_type', $userType->value)
            ->whereJsonContains('context->type', $contextType)
            ->whereJsonContains('context->id', $contextId)
            ->exists();
    }
}
