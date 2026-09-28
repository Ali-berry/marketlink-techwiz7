<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\CommunityPost;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedController extends Controller
{
    // public feed - guests aur har role ko same approved posts, sirf "already liked" user ke hisaab se
    public function index(Request $request): View
    {
        $viewer = $request->user();

        $posts = CommunityPost::approved()
            ->with(['author', 'comments.user'])
            ->withCount(['likes as likes_count', 'comments as comments_count'])
            ->when($viewer, fn ($query) => $query->withExists([
                'likes as liked_by_viewer' => fn ($likeQuery) => $likeQuery->where('user_id', $viewer->id),
            ]))
            ->pinnedFirst()
            ->paginate(10);

        // author ki apni pending posts - sirf usi ko dikhti hain
        $viewerPendingPosts = $viewer
            ? CommunityPost::pending()->where('author_id', $viewer->id)->with('author')->latest('created_at')->get()
            : collect();

        return view('community.index', compact('posts', 'viewerPendingPosts'));
    }
}
