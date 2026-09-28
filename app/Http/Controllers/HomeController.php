<?php

namespace App\Http\Controllers;

use App\Enums\ProductAvailability;
use App\Models\FarmerProfile;
use App\Models\Market;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\TopFarmersThisWeek;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(TopFarmersThisWeek $topFarmersThisWeek): View
    {
        $platformStats = [
            'approvedFarmers' => FarmerProfile::approved()->count(),
            'activeMarkets' => Market::active()->count(),
            'productsAvailable' => Product::visibleToCustomers()->where('availability', ProductAvailability::Available->value)->count(),
            'pickupsCompleted' => Order::completed()->count(),
        ];

        $categoriesWithCounts = ProductCategory::withCount([
            'products as available_products_count' => fn ($productQuery) => $productQuery->visibleToCustomers(),
        ])->orderBy('sort_order')->get();

        $freshProducts = Product::visibleToCustomers()
            ->newestListedFirst()
            ->with('farmer')
            ->take(8)
            ->get();

        return view('home', [
            'platformStats' => $platformStats,
            'categoriesWithCounts' => $categoriesWithCounts,
            'freshProducts' => $freshProducts,
            'topFarmers' => $topFarmersThisWeek->forHomePage(),
        ]);
    }
}
