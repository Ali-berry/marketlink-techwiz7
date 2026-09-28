<?php

namespace App\Services\Agent;

use App\Enums\OrderStatus;
use App\Exceptions\CommunityModerationException;
use App\Helpers\MoneyFormatter;
use App\Models\CommunityPost;
use App\Models\FarmerProfile;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Services\CommunityPostModerator;
use App\Services\FarmerApprovalService;

// Admin AI ke tools - har admin ko sirf wahi tools milte hain jin ki Spatie permission us ke paas hai.
// Naya field AI tak khud nahi pohanchta, neeche wale result array mein add karna parta hai.
class AdminAgentToolkit
{
    public function __construct(
        private readonly CommunityPostModerator $communityPostModerator,
        private readonly FarmerApprovalService $farmerApprovalService,
    ) {
    }

    public function schemas(User $actor): array
    {
        $allTools = [
            $this->schema('get_pending_farmers', 'List farmer sign-ups waiting for approval.', [], [], 'manage-farmers'),
            $this->schema('get_farmer_details', 'Get full details of one farmer\'s stall - contact info, address, markets, products, signup date and approval status.', [
                'farmer_name' => ['type' => 'string', 'description' => 'Stall name of the farmer'],
            ], ['farmer_name'], 'manage-farmers'),
            $this->schema('approve_or_suspend_farmer', 'Approve or suspend a farmer\'s stall. Suspending needs a reason the farmer will see - ask for one if it wasn\'t given.', [
                'farmer_name' => ['type' => 'string', 'description' => 'Stall name of the farmer'],
                'action' => ['type' => 'string', 'description' => 'Either "approve" or "suspend"'],
                'reason' => ['type' => 'string', 'description' => 'Why the stall is being suspended - required for suspend'],
            ], ['farmer_name', 'action'], 'manage-farmers'),

            $this->schema('get_moderation_queue', 'List the most recent products and reviews, with their hidden status.', [], [], 'moderate-content'),
            $this->schema('get_product_details', 'Get full details of one product - farmer, category, price, stock, description and hidden status.', [
                'product_id' => ['type' => 'integer', 'description' => 'The id of the product'],
            ], ['product_id'], 'moderate-content'),
            $this->schema('moderate_item', 'Hide or unhide a product or a review. Hiding needs a reason the farmer will see - ask for one if it wasn\'t given.', [
                'item_type' => ['type' => 'string', 'description' => 'Either "product" or "review"'],
                'item_id' => ['type' => 'integer', 'description' => 'The id of the product or review'],
                'action' => ['type' => 'string', 'description' => 'Either "hide" or "unhide"'],
                'reason' => ['type' => 'string', 'description' => 'Why it is being hidden - required for hide'],
            ], ['item_type', 'item_id', 'action'], 'moderate-content'),

            $this->schema('get_platform_reports', 'Summarise platform-wide orders and revenue for a period.', [
                'period' => ['type' => 'string', 'description' => 'One of: this_week, this_month'],
            ], [], 'view-reports'),

            $this->schema('get_pending_community_posts', 'List community posts waiting for moderation.', [], [], 'moderate-community-posts'),
            $this->schema('get_post_details', 'Get full details of one community post - author, full text, whether it has a photo, and when it was posted.', [
                'post_id' => ['type' => 'integer', 'description' => 'The id of the post'],
            ], ['post_id'], 'moderate-community-posts'),
            $this->schema('approve_community_post', 'Approve a pending (or previously rejected) community post so it shows in the public feed.', [
                'post_id' => ['type' => 'integer', 'description' => 'The id of the post to approve'],
            ], ['post_id'], 'moderate-community-posts'),
            $this->schema('reject_community_post', 'Reject a community post. The author is notified with the reason, so a reason is required - ask for one if the user did not give it.', [
                'post_id' => ['type' => 'integer', 'description' => 'The id of the post to reject'],
                'reason' => ['type' => 'string', 'description' => 'Why it was rejected - shown to the author'],
            ], ['post_id', 'reason'], 'moderate-community-posts'),
            $this->schema('pin_community_post', 'Pin an approved community post to the top of the feed (at most '.CommunityPostModerator::MAX_PINNED_POSTS.' at once).', [
                'post_id' => ['type' => 'integer', 'description' => 'The id of the post to pin'],
            ], ['post_id'], 'moderate-community-posts'),
            $this->schema('unpin_community_post', 'Unpin a community post.', [
                'post_id' => ['type' => 'integer', 'description' => 'The id of the post to unpin'],
            ], ['post_id'], 'moderate-community-posts'),
            $this->schema('delete_community_post', 'Delete a community post for good, with its comments, likes and photo.', [
                'post_id' => ['type' => 'integer', 'description' => 'The id of the post to delete'],
            ], ['post_id'], 'moderate-community-posts'),
        ];

        // yahi asal permission check hai, route middleware jaisa
        return array_values(array_filter($allTools, fn (array $tool) => $actor->can($tool['permission'])));
    }

    public function execute(User $actor, string $toolName, array $arguments): array
    {
        // purani tool list model tak pohanch bhi jaye to yahan dobara check ho jata hai
        $requiredPermission = match ($toolName) {
            'get_pending_farmers', 'get_farmer_details', 'approve_or_suspend_farmer' => 'manage-farmers',
            'get_moderation_queue', 'get_product_details', 'moderate_item' => 'moderate-content',
            'get_platform_reports' => 'view-reports',
            'get_pending_community_posts', 'get_post_details', 'approve_community_post', 'reject_community_post',
            'pin_community_post', 'unpin_community_post', 'delete_community_post' => 'moderate-community-posts',
            default => null,
        };

        if ($requiredPermission === null) {
            return ['error' => "Unknown tool: {$toolName}"];
        }

        if (! $actor->can($requiredPermission)) {
            return ['error' => "You don't have permission to do that ({$requiredPermission})."];
        }

        return match ($toolName) {
            'get_pending_farmers' => $this->getPendingFarmers(),
            'get_farmer_details' => $this->getFarmerDetails($arguments),
            'approve_or_suspend_farmer' => $this->approveOrSuspendFarmer($arguments),
            'get_moderation_queue' => $this->getModerationQueue(),
            'get_product_details' => $this->getProductDetails($arguments),
            'moderate_item' => $this->moderateItem($arguments),
            'get_platform_reports' => $this->getPlatformReports($arguments),
            'get_pending_community_posts' => $this->getPendingCommunityPosts(),
            'get_post_details' => $this->getPostDetails($arguments),
            'approve_community_post', 'reject_community_post', 'pin_community_post',
            'unpin_community_post', 'delete_community_post' => $this->moderateCommunityPost($toolName, $arguments),
        };
    }

    private function getPendingFarmers(): array
    {
        $farmers = FarmerProfile::pending()->with('user')->latest()->take(10)->get();

        return [
            'count' => $farmers->count(),
            'farmers' => $farmers->map(fn (FarmerProfile $farmer) => [
                'farmer_name' => $farmer->stall_name,
                'contact_person' => $farmer->contact_person,
                'signed_up' => $farmer->created_at->diffForHumans(),
            ])->all(),
        ];
    }

    private function getFarmerDetails(array $arguments): array
    {
        $farmer = AgentNameMatcher::findOne(FarmerProfile::query(), 'stall_name', $arguments['farmer_name'], 'farmer');

        if (is_array($farmer)) {
            return $farmer;
        }

        return [
            'farmer_name' => $farmer->stall_name,
            'contact_person' => $farmer->contact_person,
            'phone' => $farmer->user->phone,
            'email' => $farmer->user->email,
            'address' => $farmer->address,
            'markets' => $farmer->markets->map(fn ($market) => [
                'market_name' => $market->name,
                'stall_number' => $market->pivot->stall_number,
            ])->all(),
            'products' => $farmer->products->map(fn (Product $product) => [
                'product_name' => $product->name,
                'price_text' => MoneyFormatter::format($product->price),
            ])->all(),
            'signed_up' => $farmer->created_at->toFormattedDateString(),
            'approval_status' => $farmer->approval_status->label(),
        ];
    }

    private function approveOrSuspendFarmer(array $arguments): array
    {
        $farmer = AgentNameMatcher::findOne(FarmerProfile::query(), 'stall_name', $arguments['farmer_name'], 'farmer');

        if (is_array($farmer)) {
            return $farmer;
        }

        $action = $arguments['action'] ?? null;

        if (! in_array($action, ['approve', 'suspend'], true)) {
            return ['error' => 'action must be either "approve" or "suspend".'];
        }

        // suspend form jaisa rule - farmer ko reason dikhta hai
        $suspensionReason = trim((string) ($arguments['reason'] ?? ''));

        if ($action === 'suspend' && $suspensionReason === '') {
            return ['error' => 'A reason is required to suspend a stall - the farmer sees it. Ask the admin why.'];
        }

        if ($action === 'approve') {
            $this->farmerApprovalService->approve($farmer);
        } else {
            $this->farmerApprovalService->suspend($farmer, $suspensionReason);
        }

        return ['success' => true, 'farmer_name' => $farmer->stall_name, 'new_status' => $farmer->approval_status->label()];
    }

    private function getModerationQueue(): array
    {
        return [
            'recent_products' => Product::with('farmer')->latest()->take(5)->get()
                ->map(fn (Product $product) => [
                    'item_id' => $product->id,
                    'name' => $product->name,
                    'farmer_name' => $product->farmer->stall_name,
                    'hidden' => $product->is_hidden_by_admin,
                ])->all(),
            'recent_reviews' => Review::with('customer')->latest()->take(5)->get()
                ->map(fn (Review $review) => [
                    'item_id' => $review->id,
                    'customer_name' => $review->customer->name,
                    'comment' => $review->comment,
                    'hidden' => $review->is_hidden_by_admin,
                ])->all(),
        ];
    }

    private function getProductDetails(array $arguments): array
    {
        $product = Product::with(['farmer', 'category'])->find($arguments['product_id'] ?? null);

        if (! $product) {
            return ['error' => 'No product found with that id.'];
        }

        return [
            'product_name' => $product->name,
            'farmer_name' => $product->farmer->stall_name,
            'category' => $product->category->name,
            'price_text' => MoneyFormatter::format($product->price),
            'unit' => $product->unit,
            'stock_quantity' => $product->stock_quantity,
            'description' => $product->description,
            'hidden' => $product->is_hidden_by_admin,
        ];
    }

    private function moderateItem(array $arguments): array
    {
        $itemType = $arguments['item_type'] ?? null;
        $shouldHide = ($arguments['action'] ?? null) === 'hide';

        $model = match ($itemType) {
            'product' => Product::find($arguments['item_id']),
            'review' => Review::find($arguments['item_id']),
            default => null,
        };

        if (! $model) {
            return ['error' => 'item_type must be "product" or "review", and item_id must exist.'];
        }

        // hide form jaisa rule - farmer ko reason dikhta hai
        $hiddenReason = trim((string) ($arguments['reason'] ?? ''));

        if ($shouldHide && $hiddenReason === '') {
            return ['error' => 'A reason is required to hide something - the farmer sees it. Ask the admin why.'];
        }

        $model->update([
            'is_hidden_by_admin' => $shouldHide,
            'hidden_reason' => $shouldHide ? $hiddenReason : null,
        ]);

        return ['success' => true, 'item_type' => $itemType, 'hidden' => $shouldHide];
    }

    private function getPlatformReports(array $arguments): array
    {
        $periodStart = match ($arguments['period'] ?? 'this_week') {
            'this_month' => now()->startOfMonth(),
            default => now()->startOfWeek(),
        };

        $ordersInRange = Order::where('created_at', '>=', $periodStart);
        $completedRevenue = (float) (clone $ordersInRange)->where('status', OrderStatus::Completed->value)->sum('total_amount');

        return [
            'period' => $arguments['period'] ?? 'this_week',
            'total_orders' => (clone $ordersInRange)->count(),
            'total_revenue' => $completedRevenue,
            'total_revenue_text' => MoneyFormatter::format($completedRevenue),
            'top_farmers' => FarmerProfile::withCount(['orders as orders_in_range' => fn ($query) => $query->where('created_at', '>=', $periodStart)])
                ->orderByDesc('orders_in_range')
                ->take(3)
                ->get()
                ->map(fn (FarmerProfile $farmer) => ['farmer_name' => $farmer->stall_name, 'orders' => $farmer->orders_in_range])
                ->all(),
        ];
    }

    private function getPendingCommunityPosts(): array
    {
        $posts = CommunityPost::pending()->with('author')->latest('created_at')->take(10)->get();

        return [
            'count' => $posts->count(),
            'posts' => $posts->map(fn (CommunityPost $post) => [
                'post_id' => $post->id,
                'author_name' => $post->author->name,
                'body' => $post->body,
            ])->all(),
        ];
    }

    private function getPostDetails(array $arguments): array
    {
        $post = CommunityPost::with('author')->find($arguments['post_id'] ?? null);

        if (! $post) {
            return ['error' => 'No community post found with that id.'];
        }

        return [
            'author_name' => $post->author->name,
            'body' => $post->body,
            'has_image' => $post->image_path !== null,
            'posted' => $post->created_at->toFormattedDateString(),
        ];
    }

    private function moderateCommunityPost(string $toolName, array $arguments): array
    {
        $post = CommunityPost::find($arguments['post_id'] ?? null);

        if (! $post) {
            return ['error' => 'No community post found with that id.'];
        }

        if ($toolName === 'reject_community_post' && blank($arguments['reason'] ?? null)) {
            return ['error' => 'A reason is required to reject a post - the author sees it.'];
        }

        try {
            match ($toolName) {
                'approve_community_post' => $this->communityPostModerator->approve($post),
                'reject_community_post' => $this->communityPostModerator->reject($post, $arguments['reason']),
                'pin_community_post' => $this->communityPostModerator->pin($post),
                'unpin_community_post' => $this->communityPostModerator->unpin($post),
                'delete_community_post' => $this->communityPostModerator->delete($post),
            };
        } catch (CommunityModerationException $exception) {
            return ['error' => $exception->getMessage()];
        }

        return ['success' => true, 'post_id' => $post->id, 'action' => str_replace('_community_post', '', $toolName)];
    }

    private function schema(string $name, string $description, array $properties, array $required, string $permission): array
    {
        return [
            'permission' => $permission,
            'schema' => [
                'type' => 'function',
                'function' => [
                    'name' => $name,
                    'description' => $description,
                    'parameters' => [
                        'type' => 'object',
                        // empty array json mein [] banta hai {} nahi, Groq ye reject karta hai
                        'properties' => $this->allowNullOnOptionalProperties($properties, $required) ?: (object) [],
                        'required' => $required,
                    ],
                ],
            ],
        ];
    }

    // model skip kiye hue param pe null bhej deta hai aur Groq strict schema pe fail kar deta hai,
    // is liye har optional param ka type null bhi allow karta hai
    private function allowNullOnOptionalProperties(array $properties, array $required): array
    {
        foreach ($properties as $propertyName => &$propertySchema) {
            if (! in_array($propertyName, $required, true) && isset($propertySchema['type'])) {
                $propertySchema['type'] = [$propertySchema['type'], 'null'];
            }
        }

        return $properties;
    }
}
