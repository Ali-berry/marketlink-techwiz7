<?php

namespace App\Notifications;

use App\Models\CommunityPost;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

// bell notification - moderator ne post approve kar di
class CommunityPostApproved extends Notification
{
    use Queueable;

    public function __construct(private readonly CommunityPost $post)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'community_post_id' => $this->post->id,
            'message' => 'Your community post was approved and is now in the feed.',
            'url' => route('community.index'),
        ];
    }
}
