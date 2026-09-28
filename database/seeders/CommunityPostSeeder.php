<?php

namespace Database\Seeders;

use App\Enums\CommunityPostStatus;
use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

// demo feed: har tarah ke account ki approved posts (admin ki ek pinned welcome post), do pending,
// ek rejected reason ke saath, aur kuch likes / comments
class CommunityPostSeeder extends Seeder
{
    public function run(): void
    {
        $adminTeam = User::where('email', 'admin@marketlink.test')->firstOrFail();
        $greenValley = User::where('email', 'greenvalley@marketlink.test')->firstOrFail();
        $sunriseOrchard = User::where('email', 'sunrise@marketlink.test')->firstOrFail();
        $crustAndCrumb = User::where('email', 'crustcrumb@marketlink.test')->firstOrFail();
        $sara = User::where('email', 'sara@marketlink.test')->firstOrFail();
        $bilal = User::where('email', 'bilal@marketlink.test')->firstOrFail();
        $ayesha = User::where('email', 'ayesha@marketlink.test')->firstOrFail();

        $welcomePost = $this->createPost($adminTeam, CommunityPostStatus::Approved, 6,
            "Welcome to the MarketLink community! Share what's fresh at your stall, ask other shoppers for tips, "
            .'and swap recipes. Every post is checked by our team before it shows up here.');
        $welcomePost->update(['is_pinned' => true, 'pinned_at' => now()->subDays(6)]);

        $spinachPost = $this->createPost($greenValley, CommunityPostStatus::Approved, 3,
            'Spinach and fresh mint are back this weekend at Dallas Farmers Market, stall A-12. Reserve ahead so we set some aside for you!',
            'products/spinach.webp');

        $mintQuestion = $this->createPost($sara, CommunityPostStatus::Approved, 2,
            'Anyone know which stall has fresh mint this Saturday at Dallas Farmers Market? Making mint lemonade for a party.');

        $mangoPost = $this->createPost($sunriseOrchard, CommunityPostStatus::Approved, 2,
            'Last Rio Grande Valley mangoes of the season this weekend at Houston Farmers Market. Once they are gone, that is it until next summer.',
            'products/mangoes.webp');

        $salsaPost = $this->createPost($ayesha, CommunityPostStatus::Approved, 1,
            "Made salsa with Mesilla Valley's heirloom tomatoes and their green chile. Worth the drive to El Paso!",
            'products/green-chile-salsa.webp');

        $this->createPost($adminTeam, CommunityPostStatus::Approved, 1,
            'Heads up: El Paso Farmers Market runs on Mountain Time, so pickup times there are shown in MT. Every other market is on Central Time.');

        // community moderator ke intezar mein
        $this->createPost($bilal, CommunityPostStatus::Pending, 0,
            'The sourdough from Crust and Crumb was amazing. What else should I try at Cowtown next Wednesday?');
        $this->createPost($crustAndCrumb, CommunityPostStatus::Pending, 0,
            'New cinnamon raisin loaf this Wednesday at Cowtown Farmers Market, stall A-20. Only a dozen, so reserve early.');

        $rejectedPost = $this->createPost($bilal, CommunityPostStatus::Rejected, 4,
            'Selling my old road bike, great condition. Message me if interested.');
        $rejectedPost->update(['rejection_reason' => "Selling personal items isn't allowed - the feed is for local food and markets."]);

        $this->addComment($mintQuestion, $greenValley, "We'll have plenty at stall A-12 on Saturday - reserve a bunch and it's yours.");
        $this->addComment($mintQuestion, $sara, 'Perfect, just reserved two bunches. Thank you!');
        $this->addComment($salsaPost, $bilal, 'Recipe please?');
        $this->addComment($mangoPost, $ayesha, 'Grabbing a kilo before they run out.');

        $likesPerPost = [
            [$welcomePost, [$sara, $bilal, $ayesha, $greenValley]],
            [$spinachPost, [$sara, $ayesha]],
            [$mintQuestion, [$bilal]],
            [$mangoPost, [$sara, $bilal, $ayesha]],
            [$salsaPost, [$sara, $greenValley]],
        ];

        foreach ($likesPerPost as [$likedPost, $usersWhoLiked]) {
            foreach ($usersWhoLiked as $userWhoLiked) {
                $likedPost->likes()->firstOrCreate(['user_id' => $userWhoLiked->id]);
            }
        }
    }

    // author + text pe updateOrCreate, taake seeder do baar chale to feed double na ho
    private function createPost(User $author, CommunityPostStatus $status, int $daysAgo, string $body, ?string $sourceImage = null): CommunityPost
    {
        $post = CommunityPost::updateOrCreate(
            ['author_id' => $author->id, 'body' => $body],
            [
                'status' => $status->value,
                'image_path' => $sourceImage ? $this->storeCommunityImage($sourceImage) : null,
                'moderated_at' => $status === CommunityPostStatus::Pending ? null : now()->subDays($daysAgo)->addHour(),
            ],
        );

        // created_at fillable nahi (aur updated_at hai hi nahi), is liye alag se set
        $post->forceFill(['created_at' => now()->subDays($daysAgo)])->save();

        return $post;
    }

    // public/images se photo utha ke storage/app/public/community-posts/ mein daal do, fresh install pe bhi chale
    private function storeCommunityImage(string $publicImagePath): string
    {
        $storedPath = 'community-posts/'.basename($publicImagePath);

        if (! Storage::disk('public')->exists($storedPath)) {
            Storage::disk('public')->put($storedPath, file_get_contents(public_path('images/'.$publicImagePath)));
        }

        return $storedPath;
    }

    private function addComment(CommunityPost $post, User $commenter, string $body): void
    {
        $post->comments()->firstOrCreate(['user_id' => $commenter->id, 'body' => $body]);
    }
}
