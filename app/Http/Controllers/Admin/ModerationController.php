<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HideContentRequest;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModerationController extends Controller
{
    public function index(Request $request): View
    {
        $activeTab = $request->query('tab') === 'reviews' ? 'reviews' : 'products';

        $products = Product::with(['farmer', 'category'])
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderByDesc('is_hidden_by_admin')
            ->latest()
            ->paginate(12, ['*'], 'products_page')
            ->withQueryString();

        $reviews = Review::with(['customer', 'farmer', 'product'])
            ->when($request->filled('search'), fn ($query) => $query->where('comment', 'like', '%'.$request->string('search').'%'))
            ->orderByDesc('is_hidden_by_admin')
            ->latest()
            ->paginate(12, ['*'], 'reviews_page')
            ->withQueryString();

        return view('admin.moderation.index', compact('activeTab', 'products', 'reviews'));
    }

    public function hideProduct(HideContentRequest $request, Product $product): RedirectResponse
    {
        $product->update(['is_hidden_by_admin' => true, 'hidden_reason' => $request->validated()['reason']]);

        return back()->with('success', $product->name.' is now hidden from customers.');
    }

    public function unhideProduct(Product $product): RedirectResponse
    {
        $product->update(['is_hidden_by_admin' => false, 'hidden_reason' => null]);

        return back()->with('success', $product->name.' is visible to customers again.');
    }

    public function hideReview(HideContentRequest $request, Review $review): RedirectResponse
    {
        $review->update(['is_hidden_by_admin' => true, 'hidden_reason' => $request->validated()['reason']]);

        return back()->with('success', 'Review hidden.');
    }

    public function unhideReview(Review $review): RedirectResponse
    {
        $review->update(['is_hidden_by_admin' => false, 'hidden_reason' => null]);

        return back()->with('success', 'Review is visible again.');
    }
}
