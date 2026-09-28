<?php

namespace App\Notifications;

use App\Models\CommunityPost;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

// bell notification - post reject hui, moderator ke reason ke saath
class CommunityPostRejected extends Notification
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
            'message' => 'Your community post "'.Str::limit($this->post->body, 40).'" wasn\'t approved. Reason: '.$this->post->rejection_reason,
            'url' => route('community.index'),
        ];
    }
}
