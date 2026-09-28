<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommunityPostStatus;
use App\Exceptions\CommunityModerationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectCommunityPostRequest;
use App\Models\CommunityPost;
use App\Services\CommunityPostModerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// poora route group moderate-community-posts permission ke peeche hai.
// asal changes CommunityPostModerator se hote hain, admin AI bhi wahi use karta hai
class CommunityModerationController extends Controller
{
    private const TABS = ['pending', 'approved', 'rejected'];

    public function index(Request $request): View
    {
        $activeTab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'pending';
        $startOfWeek = now()->startOfWeek();

        // sirf community ke numbers - farmers, orders, revenue nahi
        $communityStats = [
            'waiting' => CommunityPost::pending()->count(),
            'approved_this_week' => CommunityPost::approved()->where('moderated_at', '>=', $startOfWeek)->count(),
            'pinned' => CommunityPost::where('is_pinned', true)->count(),
            'rejected_this_week' => CommunityPost::rejected()->where('moderated_at', '>=', $startOfWeek)->count(),
        ];

        $tabCounts = [
            'pending' => $communityStats['waiting'],
            'approved' => CommunityPost::approved()->count(),
            'rejected' => CommunityPost::rejected()->count(),
        ];

        $posts = CommunityPost::where('status', CommunityPostStatus::from($activeTab)->value)
            ->with('author')
            ->withCount(['likes as likes_count', 'comments as comments_count'])
            // Approved tab pe pinned pehle, public feed jaisa
            ->when($activeTab === 'approved', fn ($query) => $query->pinnedFirst(), fn ($query) => $query->latest('created_at'))
            ->paginate(10)
            ->withQueryString();

        return view('admin.community.index', [
            'activeTab' => $activeTab,
            'communityStats' => $communityStats,
            'tabCounts' => $tabCounts,
            'posts' => $posts,
            'maxPinnedPosts' => CommunityPostModerator::MAX_PINNED_POSTS,
        ]);
    }

    public function approve(CommunityPost $post, CommunityPostModerator $moderator): RedirectResponse
    {
        $moderator->approve($post);

        return back()->with('success', 'Post approved - it now shows in the community feed.');
    }

    public function reject(RejectCommunityPostRequest $request, CommunityPost $post, CommunityPostModerator $moderator): RedirectResponse
    {
        $moderator->reject($post, $request->validated()['reason']);

        return back()->with('success', 'Post rejected - the author has been told why.');
    }

    public function pin(CommunityPost $post, CommunityPostModerator $moderator): RedirectResponse
    {
        try {
            $moderator->pin($post);
        } catch (CommunityModerationException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Post pinned to the top of the community feed.');
    }

    public function unpin(CommunityPost $post, CommunityPostModerator $moderator): RedirectResponse
    {
        $moderator->unpin($post);

        return back()->with('success', 'Post unpinned.');
    }

    public function destroy(CommunityPost $post, CommunityPostModerator $moderator): RedirectResponse
    {
        $moderator->delete($post);

        return back()->with('success', 'Post deleted, along with its comments and likes.');
    }
}
