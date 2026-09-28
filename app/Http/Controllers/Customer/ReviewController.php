<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreReviewRequest;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $customer = $request->user();

        $reviewableOrders = $customer->ordersAsCustomer()
            ->completed()
            ->with(['farmer', 'items.product'])
            ->latest('completed_at')
            ->get();

        // order id se keyed, taake view firstWhere('product_id', ...) se farmer aur product reviews nikaal le
        $existingReviews = Review::where('customer_id', $customer->id)
            ->whereIn('order_id', $reviewableOrders->pluck('id'))
            ->get()
            ->groupBy('order_id');

        return view('customer.reviews.index', compact('reviewableOrders', 'existingReviews'));
    }

    // farmer review aur product reviews dono ek endpoint se, aur yahi edit bhi - same pair dobara bhejo to update
    public function store(StoreReviewRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $order = Order::findOrFail($validated['order_id']);

        Review::updateOrCreate(
            [
                'customer_id' => $request->user()->id,
                'order_id' => $order->id,
                'product_id' => $validated['product_id'] ?? null,
            ],
            [
                'farmer_profile_id' => $order->farmer_profile_id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ],
        );

        return back()->with('success', 'Thanks for your review.');
    }
}
