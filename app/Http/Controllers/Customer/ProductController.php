<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ProductAvailability;
use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Review;
use App\Services\Cart;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    private const DAY_NAMES = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    public function index(Request $request): View
    {
        $products = Product::visibleToCustomers()
            ->with(['farmer', 'category'])
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('category_id'), fn ($query) => $query->where('product_category_id', $request->integer('category_id')))
            ->when($request->filled('market_id'), fn ($query) => $query->whereHas(
                'farmer.markets',
                fn ($marketQuery) => $marketQuery->where('markets.id', $request->integer('market_id'))
            ))
            ->when($request->filled('day'), fn ($query) => $query->whereHas('farmer.pickupWindows', function ($pickupWindowQuery) use ($request) {
                $pickupWindowQuery->where('is_active', true)
                    ->where('day_of_week', array_search($request->string('day')->value(), self::DAY_NAMES, true))
                    ->when($request->filled('market_id'), fn ($q) => $q->where('market_id', $request->integer('market_id')));
            }))
            ->when($request->filled('price_min'), fn ($query) => $query->where('price', '>=', $request->float('price_min')))
            ->when($request->filled('price_max'), fn ($query) => $query->where('price', '<=', $request->float('price_max')))
            ->when($request->boolean('in_stock_only'), fn ($query) => $query
                ->where('availability', ProductAvailability::Available->value)
                ->where('stock_quantity', '>', 0))
            // "newest" (default) home page ke "Fresh this week" wala sort hai
            ->when($request->query('sort'), function ($query, $sort) {
                match ($sort) {
                    'price_asc' => $query->orderBy('price'),
                    'price_desc' => $query->orderByDesc('price'),
                    default => $query->newestListedFirst(),
                };
            }, fn ($query) => $query->newestListedFirst())
            ->paginate(12)
            ->withQueryString();

        return view('customer.products.index', [
            'products' => $products,
            'categories' => ProductCategory::orderBy('sort_order')->get(),
            'markets' => Market::active()->orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, Product $product, Cart $cart): View
    {
        abort_unless(
            Product::visibleToCustomers()->whereKey($product->id)->exists(),
            404
        );

        $product->load([
            'category',
            'farmer.markets' => fn ($marketQuery) => $marketQuery->active()->orderBy('name'),
            'farmer.pickupWindows' => fn ($windowQuery) => $windowQuery->where('is_active', true)->orderBy('day_of_week')->orderBy('starts_at'),
        ]);

        $reviews = Review::where('product_id', $product->id)
            ->where('is_hidden_by_admin', false)
            ->with('customer')
            ->latest()
            ->get();

        // guest page dekh sakta hai, bas favourites nahi hote
        $favourite = $request->user()?->favouriteProducts()->where('products.id', $product->id)->first();

        // "You may also like" - isi farmer ya category se, ye product nahi
        $relatedProducts = Product::visibleToCustomers()
            ->with('farmer')
            ->whereKeyNot($product->id)
            ->where(fn ($query) => $query
                ->where('farmer_profile_id', $product->farmer_profile_id)
                ->orWhere('product_category_id', $product->product_category_id))
            // pehle same farmer ke products, phir category ke baqi
            ->orderByRaw('farmer_profile_id = ? DESC', [$product->farmer_profile_id])
            ->inRandomOrder()
            ->take(4)
            ->get();

        return view('customer.products.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'reviews' => $reviews,
            'isFavourited' => $favourite !== null,
            'wantsRestockAlert' => $favourite?->pivot->wants_restock_alert ?? true,
            'basketQuantity' => $cart->quantityFor($product->id),
        ]);
    }
}
