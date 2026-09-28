<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Exceptions\BasketCheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCheckoutRequest;
use App\Models\FarmerProfile;
use App\Models\Market;
use App\Models\Order;
use App\Models\PickupWindow;
use App\Services\Cart;
use App\Services\PlaceOrderFromBasket;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    // pickup date kitne din aage tak dhoondni hai
    private const DAYS_AHEAD_TO_OFFER = 21;

    public function show(Request $request, FarmerProfile $farmer, Cart $cart): View|RedirectResponse
    {
        $basketGroup = $cart->groupedByFarmer()->get($farmer->id);

        if (! $basketGroup || $basketGroup['items']->isEmpty()) {
            return redirect()->route('customer.cart.index')->with('error', 'Your basket for this farmer is empty.');
        }

        // sirf chalti hui markets naye orders le sakti hain
        $availableMarkets = $farmer->markets()
            ->active()
            ->whereHas('pickupWindows', fn ($query) => $query->where('farmer_profile_id', $farmer->id)->where('is_active', true))
            ->get();

        $selectedMarket = $request->filled('market_id')
            ? $availableMarkets->firstWhere('id', $request->integer('market_id'))
            : null;

        $pickupOptions = $selectedMarket ? $this->pickupOptionsFor($farmer, $selectedMarket) : collect();

        return view('customer.checkout.show', [
            'farmer' => $farmer,
            'basketGroup' => $basketGroup,
            'availableMarkets' => $availableMarkets,
            'selectedMarket' => $selectedMarket,
            'pickupOptions' => $pickupOptions,
        ]);
    }

    public function store(StoreCheckoutRequest $request, FarmerProfile $farmer, PlaceOrderFromBasket $placeOrder, Cart $cart): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $order = $placeOrder->place(
                customer: $request->user(),
                farmer: $farmer,
                market: Market::findOrFail($validated['market_id']),
                pickupWindow: PickupWindow::findOrFail($request->pickupWindowId()),
                pickupDate: $request->pickupDate(),
                customerNote: $validated['customer_note'] ?? null,
                cart: $cart,
            );
        } catch (BasketCheckoutException $exception) {
            return redirect()->route('customer.cart.index')->with('error', $exception->getMessage());
        }

        return redirect()->route('customer.orders.show', $order)
            ->with('success', 'Order placed! '.$farmer->stall_name.' has been notified.');
    }

    // agle kuch hafton ki dates jo farmer ke active slots se milti hain, cutoff guzre ya full slots ke bina.
    // asal capacity check checkout pe hota hai
    private function pickupOptionsFor(FarmerProfile $farmer, Market $market): Collection
    {
        $activeWindows = $farmer->pickupWindows()->where('market_id', $market->id)->where('is_active', true)->get();

        // market ka "aaj", server ka nahi - raat 12 ke qareeb dono alag ho sakte hain
        $marketToday = $market->localNow()->startOfDay();

        $candidateDates = collect(range(0, self::DAYS_AHEAD_TO_OFFER - 1))
            ->map(fn (int $daysAhead) => $marketToday->copy()->addDays($daysAhead));

        return $activeWindows->flatMap(function (PickupWindow $window) use ($candidateDates, $farmer, $market) {
            return $candidateDates
                ->filter(fn (Carbon $date) => $date->dayOfWeek === $window->day_of_week)
                ->map(fn (Carbon $date) => $this->optionFor($market, $window, $date, $farmer->order_cutoff_hours))
                ->filter();
        })->sortBy('date')->values();
    }

    private function optionFor(Market $market, PickupWindow $window, Carbon $date, int $cutoffHours): ?array
    {
        if (! Order::isWithinCutoff($market, $date, $window->starts_at, $cutoffHours)) {
            return null;
        }

        $bookedCount = Order::where('pickup_window_id', $window->id)
            ->whereDate('pickup_date', $date)
            ->whereIn('status', OrderStatus::openStatuses())
            ->count();

        if ($bookedCount >= $window->max_orders) {
            return null;
        }

        return [
            'pickup_window_id' => $window->id,
            'window' => $window,
            'date' => $date,
            'spots_left' => $window->max_orders - $bookedCount,
        ];
    }
}
