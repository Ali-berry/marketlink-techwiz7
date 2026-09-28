<?php

namespace App\Http\Controllers\Community;

use App\Enums\CommunityPostStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StorePostRequest;
use App\Models\CommunityPost;
use App\Models\CommunityPostLike;
use App\Models\User;
use App\Services\Agent\AgentProactiveMessenger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function __construct(private readonly AgentProactiveMessenger $proactiveMessenger)
    {
    }

    // koi bhi logged in user post kar sakta hai, post pending se shuru hoti hai
    public function store(StorePostRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $post = CommunityPost::create([
            'author_id' => $request->user()->id,
            'body' => $validated['body'],
            'image_path' => $request->hasFile('image') ? $request->file('image')->store('community-posts', 'public') : null,
            'status' => CommunityPostStatus::Pending->value,
        ]);

        $this->notifyAdminsOfPendingPost($post);

        return redirect()->route('community.index')
            ->with('success', 'Thanks for posting. Your post will show in the feed once an admin approves it.');
    }

    // moderate-community-posts wale sab admins ko pending post dikha do
    private function notifyAdminsOfPendingPost(CommunityPost $post): void
    {
        foreach (User::adminsWithPermission('moderate-community-posts') as $admin) {
            $this->proactiveMessenger->send(
                recipient: $admin,
                userType: UserRole::Admin,
                content: "New community post from {$post->author->name} is waiting: '"
                    .Str::limit($post->body, 60)."'. Approve or reject?",
                contextType: 'community_post',
                contextId: $post->id,
                contextLabel: Str::limit($post->body, 40),
                actions: ['Approve', 'Reject', 'Details'],
            );
        }
    }

    // already liked post pe dobara click = unlike
    public function toggleLike(Request $request, CommunityPost $post): RedirectResponse
    {
        abort_unless($post->isApproved(), 404);

        $viewer = $request->user();

        $existingLike = CommunityPostLike::where('post_id', $post->id)
            ->where('user_id', $viewer->id)
            ->first();

        if ($existingLike) {
            $existingLike->delete();
        } else {
            CommunityPostLike::create(['post_id' => $post->id, 'user_id' => $viewer->id]);
        }

        return back();
    }
}
