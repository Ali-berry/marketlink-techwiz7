<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ToggleFavouriteProductRequest;
use App\Http\Requests\Customer\UpdateRestockAlertRequest;
use App\Models\FarmerProfile;
use App\Models\Market;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavouriteController extends Controller
{
    public function index(Request $request): View
    {
        $customer = $request->user();
        $activeTab = in_array($request->query('tab'), ['products', 'markets'], true) ? $request->query('tab') : 'farmers';

        return view('customer.favourites.index', [
            'activeTab' => $activeTab,
            'favouriteFarmers' => $customer->favouriteFarmers()->get(),
            'favouriteProducts' => $customer->favouriteProducts()->with(['farmer', 'category'])->get(),
            'savedMarkets' => $customer->savedMarkets()->get(),
        ]);
    }

    public function toggleFarmer(Request $request, FarmerProfile $farmer): RedirectResponse
    {
        $customer = $request->user();
        $alreadyFavourited = $customer->favouriteFarmers()->where('farmer_profiles.id', $farmer->id)->exists();

        if ($alreadyFavourited) {
            $customer->favouriteFarmers()->detach($farmer->id);

            return back()->with('success', 'Removed from your favourite farmers.');
        }

        $customer->favouriteFarmers()->attach($farmer->id);

        return back()->with('success', $farmer->stall_name.' added to your favourite farmers.');
    }

    public function toggleProduct(ToggleFavouriteProductRequest $request, Product $product): RedirectResponse
    {
        $customer = $request->user();
        $alreadyFavourited = $customer->favouriteProducts()->where('products.id', $product->id)->exists();

        if ($alreadyFavourited) {
            $customer->favouriteProducts()->detach($product->id);

            return back()->with('success', 'Removed from your favourite products.');
        }

        $customer->favouriteProducts()->attach($product->id, [
            'wants_restock_alert' => $request->boolean('wants_restock_alert', true),
        ]);

        return back()->with('success', $product->name.' added to your favourites.');
    }

    public function updateProductRestockAlert(UpdateRestockAlertRequest $request, Product $product): RedirectResponse
    {
        $request->user()->favouriteProducts()->updateExistingPivot($product->id, [
            'wants_restock_alert' => $request->validated()['wants_restock_alert'],
        ]);

        return back()->with('success', 'Restock alert preference updated.');
    }

    public function toggleMarket(Request $request, Market $market): RedirectResponse
    {
        $customer = $request->user();
        $alreadySaved = $customer->savedMarkets()->where('markets.id', $market->id)->exists();

        if ($alreadySaved) {
            $customer->savedMarkets()->detach($market->id);

            return back()->with('success', 'Removed from your saved markets.');
        }

        $customer->savedMarkets()->attach($market->id);

        return back()->with('success', $market->name.' saved.');
    }
}
