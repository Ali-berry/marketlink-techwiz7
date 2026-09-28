<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreCommentRequest;
use App\Models\CommunityPost;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, CommunityPost $post): RedirectResponse
    {
        abort_unless($post->isApproved(), 404);

        $post->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated()['body'],
        ]);

        return back()->with('success', 'Comment added.');
    }
}
