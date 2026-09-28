<?php

namespace App\Services;

use App\Enums\CommunityPostStatus;
use App\Exceptions\CommunityModerationException;
use App\Models\CommunityPost;
use App\Notifications\CommunityPostApproved;
use App\Notifications\CommunityPostRejected;
use Illuminate\Support\Facades\Storage;

// Community moderation ke saare actions - moderation page aur admin AI dono yahi use karte hain
class CommunityPostModerator
{
    // is se zyada pin hon to pin ka koi matlab nahi rehta
    public const MAX_PINNED_POSTS = 3;

    // rejected post pe "Approve anyway" bhi yahi hai
    public function approve(CommunityPost $post): void
    {
        $post->update([
            'status' => CommunityPostStatus::Approved->value,
            'rejection_reason' => null,
            'moderated_at' => now(),
        ]);

        $post->author->notify(new CommunityPostApproved($post));
    }

    public function reject(CommunityPost $post, string $reason): void
    {
        // rejected post pinned nahi reh sakti
        $post->update([
            'status' => CommunityPostStatus::Rejected->value,
            'rejection_reason' => $reason,
            'is_pinned' => false,
            'pinned_at' => null,
            'moderated_at' => now(),
        ]);

        $post->author->notify(new CommunityPostRejected($post));
    }

    /**
     * @throws CommunityModerationException
     */
    public function pin(CommunityPost $post): void
    {
        if (! $post->isApproved()) {
            throw new CommunityModerationException('Only approved posts can be pinned.');
        }

        if ($post->is_pinned) {
            return;
        }

        if (CommunityPost::where('is_pinned', true)->count() >= self::MAX_PINNED_POSTS) {
            throw new CommunityModerationException('Only '.self::MAX_PINNED_POSTS.' posts can be pinned at once. Unpin one first.');
        }

        $post->update(['is_pinned' => true, 'pinned_at' => now()]);
    }

    public function unpin(CommunityPost $post): void
    {
        $post->update(['is_pinned' => false, 'pinned_at' => null]);
    }

    // comments aur likes cascade se jate hain, photo yahan delete hoti hai
    public function delete(CommunityPost $post): void
    {
        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }

        $post->delete();
    }
}
