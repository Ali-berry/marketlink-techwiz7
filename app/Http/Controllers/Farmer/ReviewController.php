<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\ReplyToReviewRequest;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $farmer = $request->user()->farmerProfile;

        abort_if(! $farmer, 403, 'Your farmer profile is missing. Please contact the admin.');

        $reviews = $farmer->reviews()
            ->where('is_hidden_by_admin', false)
            ->with(['customer', 'product'])
            ->latest()
            ->paginate(10);

        return view('farmer.reviews.index', [
            'farmer' => $farmer,
            'reviews' => $reviews,
        ]);
    }

    // har review ka ek reply, pehle se ho to replace
    public function reply(ReplyToReviewRequest $request, Review $review): RedirectResponse
    {
        $review->update([
            'farmer_reply' => $request->validated()['farmer_reply'],
            'farmer_replied_at' => now(),
        ]);

        return back()->with('success', 'Your reply was saved.');
    }
}
