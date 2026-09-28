<?php

namespace App\Http\Controllers\Customer;

use App\Data\TexasAreas;
use App\Helpers\DistanceHelper;
use App\Http\Controllers\Controller;
use App\Models\Market;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketController extends Controller
{
    public function index(Request $request): View
    {
        $signedInCustomer = $request->user();

        $markets = Market::active()
            ->withCount(['farmers as approved_farmers_count' => fn ($farmerQuery) => $farmerQuery->approved()])
            ->with(['farmers' => fn ($farmerQuery) => $farmerQuery->visibleToCustomers()
                ->with(['products' => fn ($productQuery) => $productQuery->visibleToCustomers()->with('category')])])
            ->when($request->filled('day'), fn ($query) => $query->whereJsonContains('operating_days', $request->string('day')->value()))
            ->orderBy('name')
            ->get();

        // har card pe "yahan kya milta hai" ke liye kuch category naam
        $markets->each(function (Market $market) {
            $market->categoryPreview = $market->farmers
                ->flatMap(fn ($farmer) => $farmer->products)
                ->pluck('category.name')
                ->filter()
                ->unique()
                ->take(4)
                ->values();
        });

        // customer ki saved location ho to qareeb wali market pehle, warna alphabetical.
        // "Find markets near" field bhi isi jagah se bhari hoti hai
        $savedStartingPoint = null;

        if ($signedInCustomer?->hasSavedLocation()) {
            $markets = $markets
                ->each(function (Market $market) use ($signedInCustomer) {
                    $market->distanceMiles = DistanceHelper::distanceInMiles(
                        $signedInCustomer->latitude,
                        $signedInCustomer->longitude,
                        $market->latitude,
                        $market->longitude,
                    );
                })
                ->sortBy('distanceMiles')
                ->values();

            $savedStartingPoint = [
                'latitude' => (float) $signedInCustomer->latitude,
                'longitude' => (float) $signedInCustomer->longitude,
                'areaName' => TexasAreas::nearestTo($signedInCustomer->latitude, $signedInCustomer->longitude)['name'],
            ];
        }

        // market-map.js aur markets-distance-sort.js ko data attributes se jata hai
        $mapMarkers = $markets->map(fn (Market $market) => [
            'id' => $market->id,
            'lat' => $market->latitude,
            'lng' => $market->longitude,
            'name' => $market->name,
            'details' => $market->operatingDaysText().', '.$market->timingText(),
        ]);

        // guest ke paas saved markets nahi hoti
        $savedMarketIds = $signedInCustomer?->savedMarkets()->pluck('markets.id') ?? collect();

        return view('customer.markets.index', [
            'markets' => $markets,
            'mapMarkers' => $mapMarkers,
            'savedMarketIds' => $savedMarketIds,
            'savedStartingPoint' => $savedStartingPoint,
        ]);
    }

    public function show(Request $request, Market $market): View
    {
        $market->loadCount(['farmers as approved_farmers_count' => fn ($farmerQuery) => $farmerQuery->approved()]);

        $farmersAtMarket = $market->farmers()->visibleToCustomers()->get();

        $isSaved = $request->user()?->savedMarkets()->where('markets.id', $market->id)->exists() ?? false;

        return view('customer.markets.show', [
            'market' => $market,
            'farmersAtMarket' => $farmersAtMarket,
            'isSaved' => $isSaved,
        ]);
    }
}
