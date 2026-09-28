<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\FarmerProfile;
use App\Models\Market;
use App\Models\ProductCategory;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmerController extends Controller
{
    private const SORT_OPTIONS = ['top_rated', 'most_products', 'newest'];

    // saare approved stalls, guests ke liye bhi - products page jaise filters
    public function index(Request $request): View
    {
        $sortBy = in_array($request->query('sort'), self::SORT_OPTIONS, true) ? $request->query('sort') : 'top_rated';

        $farmers = FarmerProfile::visibleToCustomers()
            ->with(['markets' => fn ($marketQuery) => $marketQuery->active()->orderBy('name')])
            ->withCount(['products as listed_products_count' => fn ($productQuery) => $productQuery->visibleToCustomers()])
            ->withCount(['reviews as visible_reviews_count' => fn ($reviewQuery) => $reviewQuery->where('is_hidden_by_admin', false)])
            ->withAvg(['reviews as average_rating' => fn ($reviewQuery) => $reviewQuery->where('is_hidden_by_admin', false)], 'rating')
            ->when($request->filled('search'), fn ($query) => $query->where('stall_name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('market_id'), fn ($query) => $query->whereHas(
                'markets',
                fn ($marketQuery) => $marketQuery->where('markets.id', $request->integer('market_id'))
            ))
            // "is category mein bechta hai" = kam az kam ek visible product us category mein
            ->when($request->filled('category_id'), fn ($query) => $query->whereHas(
                'products',
                fn ($productQuery) => $productQuery->visibleToCustomers()->where('product_category_id', $request->integer('category_id'))
            ))
            ->when($sortBy === 'top_rated', fn ($query) => $query->orderByDesc('average_rating')->orderByDesc('visible_reviews_count'))
            ->when($sortBy === 'most_products', fn ($query) => $query->orderByDesc('listed_products_count'))
            ->when($sortBy === 'newest', fn ($query) => $query->orderByDesc('approved_at'))
            ->orderBy('stall_name')
            ->paginate(12)
            ->withQueryString();

        return view('customer.farmers.index', [
            'farmers' => $farmers,
            'sortBy' => $sortBy,
            'markets' => Market::active()->orderBy('name')->get(),
            'categories' => ProductCategory::orderBy('sort_order')->get(),
        ]);
    }

    public function show(Request $request, FarmerProfile $farmer): View
    {
        abort_unless(FarmerProfile::visibleToCustomers()->whereKey($farmer->id)->exists(), 404);

        // sirf chalti hui markets - band market ke pickups nahi dikhate
        $farmer->load([
            'markets' => fn ($query) => $query->active()->orderBy('name'),
            'pickupWindows' => fn ($query) => $query->where('is_active', true)->orderBy('day_of_week')->orderBy('starts_at'),
        ]);

        $products = $farmer->products()->visibleToCustomers()->with('category')->orderBy('name')->get();

        $reviews = Review::where('farmer_profile_id', $farmer->id)
            ->where('is_hidden_by_admin', false)
            ->with(['customer', 'product'])
            ->latest()
            ->get();

        $isFavourited = $request->user()?->favouriteFarmers()->where('farmer_profiles.id', $farmer->id)->exists() ?? false;

        return view('customer.farmers.show', [
            'farmer' => $farmer,
            'products' => $products,
            'reviews' => $reviews,
            'isFavourited' => $isFavourited,
        ]);
    }
}
