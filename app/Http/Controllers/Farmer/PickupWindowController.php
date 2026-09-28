<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StorePickupWindowRequest;
use App\Http\Requests\Farmer\UpdatePickupWindowRequest;
use App\Models\PickupWindow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PickupWindowController extends Controller
{
    public function index(Request $request): View
    {
        $farmer = $request->user()->farmerProfile;

        abort_if(! $farmer, 403, 'Your farmer profile is missing. Please contact the admin.');

        $pickupWindowsByMarket = $farmer->pickupWindows()
            ->with('market')
            ->withCount(['orders as upcoming_orders_count' => fn ($orderQuery) => $orderQuery
                ->where('pickup_date', '>=', today())
                ->open()])
            ->get()
            ->sortBy(['day_of_week', 'starts_at'])
            ->groupBy(fn (PickupWindow $pickupWindow) => $pickupWindow->market->name);

        return view('farmer.pickup-windows.index', [
            'farmer' => $farmer,
            'pickupWindowsByMarket' => $pickupWindowsByMarket,
        ]);
    }

    public function create(Request $request): View
    {
        $farmer = $request->user()->farmerProfile;

        abort_if(! $farmer, 403, 'Your farmer profile is missing. Please contact the admin.');

        return view('farmer.pickup-windows.create', [
            'pickupWindow' => new PickupWindow(),
            'markets' => $farmer->markets,
        ]);
    }

    public function store(StorePickupWindowRequest $request): RedirectResponse
    {
        $request->user()->farmerProfile->pickupWindows()->create($request->validated());

        return redirect()->route('farmer.pickup-windows.index')->with('success', 'Pickup slot created.');
    }

    public function edit(Request $request, PickupWindow $pickupWindow): View
    {
        $this->abortUnlessOwnedByFarmer($request, $pickupWindow);

        return view('farmer.pickup-windows.edit', [
            'pickupWindow' => $pickupWindow,
            'markets' => $request->user()->farmerProfile->markets,
        ]);
    }

    public function update(UpdatePickupWindowRequest $request, PickupWindow $pickupWindow): RedirectResponse
    {
        $pickupWindow->update($request->validated());

        return redirect()->route('farmer.pickup-windows.index')->with('success', 'Pickup slot updated.');
    }

    public function toggleActive(Request $request, PickupWindow $pickupWindow): RedirectResponse
    {
        $this->abortUnlessOwnedByFarmer($request, $pickupWindow);

        $pickupWindow->update(['is_active' => ! $pickupWindow->is_active]);

        return back()->with('success', $pickupWindow->is_active ? 'Pickup slot turned on.' : 'Pickup slot turned off.');
    }

    public function destroy(Request $request, PickupWindow $pickupWindow): RedirectResponse
    {
        $this->abortUnlessOwnedByFarmer($request, $pickupWindow);

        $pickupWindow->delete();

        return redirect()->route('farmer.pickup-windows.index')->with('success', 'Pickup slot deleted.');
    }

    private function abortUnlessOwnedByFarmer(Request $request, PickupWindow $pickupWindow): void
    {
        abort_unless($pickupWindow->farmer_profile_id === $request->user()->farmerProfile?->id, 403);
    }
}
