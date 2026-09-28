<?php

namespace App\Http\Controllers;

use App\Helpers\MoneyFormatter;
use App\Models\FarmerProfile;
use App\Models\Market;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Ctrl+K command palette ke peeche yahi hai. Auth nahi - guest waise bhi products / farmers / markets dekh sakta hai
class SearchController extends Controller
{
    private const MAX_RESULTS_PER_TYPE = 5;

    public function index(Request $request): JsonResponse
    {
        $searchTerm = trim((string) $request->query('q'));

        // kam az kam 2 letters, ek letter pe teeno tables se bas shor aata hai
        if (mb_strlen($searchTerm) < 2) {
            return response()->json(['results' => []]);
        }

        $results = [
            ...$this->matchingProducts($searchTerm),
            ...$this->matchingFarmers($searchTerm),
            ...$this->matchingMarkets($searchTerm),
        ];

        return response()->json(['results' => $results]);
    }

    private function matchingProducts(string $searchTerm): array
    {
        return Product::visibleToCustomers()
            ->with('farmer')
            ->where('name', 'like', "%{$searchTerm}%")
            ->take(self::MAX_RESULTS_PER_TYPE)
            ->get()
            ->map(fn (Product $product) => [
                'type' => 'Product',
                'title' => $product->name,
                'subtitle' => $product->farmer->stall_name.' - '.MoneyFormatter::format($product->price).' per '.$product->unit,
                'url' => route('customer.products.show', $product),
            ])->all();
    }

    private function matchingFarmers(string $searchTerm): array
    {
        return FarmerProfile::visibleToCustomers()
            ->where('stall_name', 'like', "%{$searchTerm}%")
            ->take(self::MAX_RESULTS_PER_TYPE)
            ->get()
            ->map(fn (FarmerProfile $farmer) => [
                'type' => 'Farmer',
                'title' => $farmer->stall_name,
                'subtitle' => $farmer->address,
                'url' => route('customer.farmers.show', $farmer),
            ])->all();
    }

    private function matchingMarkets(string $searchTerm): array
    {
        return Market::active()
            ->where('name', 'like', "%{$searchTerm}%")
            ->take(self::MAX_RESULTS_PER_TYPE)
            ->get()
            ->map(fn (Market $market) => [
                'type' => 'Market',
                'title' => $market->name,
                'subtitle' => $market->address,
                'url' => route('customer.markets.show', $market),
            ])->all();
    }
}
