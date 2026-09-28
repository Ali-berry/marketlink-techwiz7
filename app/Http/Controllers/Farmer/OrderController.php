<?php

namespace App\Http\Controllers\Farmer;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\DeclineOrderRequest;
use App\Models\Order;
use App\Services\OrderStatusUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    // order list ke upar tabs, har tab ek ya zyada statuses
    private const TABS = [
        'new' => [OrderStatus::Placed],
        'accepted' => [OrderStatus::Accepted],
        'ready' => [OrderStatus::ReadyForPickup],
        'completed' => [OrderStatus::Completed],
        'closed' => [OrderStatus::Declined, OrderStatus::Cancelled],
    ];

    public function index(Request $request): View
    {
        $farmer = $request->user()->farmerProfile;

        abort_if(! $farmer, 403, 'Your farmer profile is missing. Please contact the admin.');

        $activeTab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'new';

        $filteredOrders = $farmer->orders()
            ->when($request->filled('pickup_date'), fn ($query) => $query->whereDate('pickup_date', $request->date('pickup_date')))
            ->when($request->filled('market_id'), fn ($query) => $query->where('market_id', $request->integer('market_id')));

        $tabCounts = collect(self::TABS)->map(
            fn (array $statuses) => (clone $filteredOrders)->whereIn('status', $this->statusValues($statuses))->count()
        );

        $orders = (clone $filteredOrders)
            ->whereIn('status', $this->statusValues(self::TABS[$activeTab]))
            ->with(['customer', 'market'])
            ->withCount('items')
            // urgent orders upar - customer ghante ke andar aa raha hai
            ->orderByDesc('is_urgent')
            ->orderBy('urgent_pickup_at')
            ->orderBy('pickup_date')
            ->paginate(15)
            ->withQueryString();

        return view('farmer.orders.index', [
            'orders' => $orders,
            'activeTab' => $activeTab,
            'tabCounts' => $tabCounts,
            'markets' => $farmer->markets,
        ]);
    }

    public function show(Request $request, Order $order, OrderStatusUpdater $updater): View
    {
        $this->abortUnlessOwnedByFarmer($request, $order);

        $order->load(['customer', 'market', 'pickupWindow', 'items', 'statusChanges.changedBy']);

        $actor = $request->user();

        return view('farmer.orders.show', [
            'order' => $order,
            'canAccept' => $updater->canTransition($order, OrderStatus::Accepted, $actor),
            'canDecline' => $updater->canTransition($order, OrderStatus::Declined, $actor),
            'canMarkReady' => $updater->canTransition($order, OrderStatus::ReadyForPickup, $actor),
            'canMarkCompleted' => $updater->canTransition($order, OrderStatus::Completed, $actor),
        ]);
    }

    public function accept(Request $request, Order $order, OrderStatusUpdater $updater): RedirectResponse
    {
        return $this->attemptTransition($request, $order, $updater, OrderStatus::Accepted, null, 'Order accepted.');
    }

    public function decline(DeclineOrderRequest $request, Order $order, OrderStatusUpdater $updater): RedirectResponse
    {
        try {
            $updater->transition($order, OrderStatus::Declined, $request->user(), $request->validated()['reason']);
        } catch (InvalidOrderTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('farmer.orders.index')->with('success', 'Order declined and stock released.');
    }

    public function markReady(Request $request, Order $order, OrderStatusUpdater $updater): RedirectResponse
    {
        return $this->attemptTransition($request, $order, $updater, OrderStatus::ReadyForPickup, null, 'Order marked ready for pickup.');
    }

    public function markCompleted(Request $request, Order $order, OrderStatusUpdater $updater): RedirectResponse
    {
        return $this->attemptTransition($request, $order, $updater, OrderStatus::Completed, null, 'Order marked completed.');
    }

    private function attemptTransition(
        Request $request,
        Order $order,
        OrderStatusUpdater $updater,
        OrderStatus $newStatus,
        ?string $note,
        string $successMessage,
    ): RedirectResponse {
        $this->abortUnlessOwnedByFarmer($request, $order);

        try {
            $updater->transition($order, $newStatus, $request->user(), $note);
        } catch (InvalidOrderTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $successMessage);
    }

    private function abortUnlessOwnedByFarmer(Request $request, Order $order): void
    {
        abort_unless($order->farmer_profile_id === $request->user()->farmerProfile?->id, 403);
    }

    private function statusValues(array $statuses): array
    {
        return array_map(fn (OrderStatus $status) => $status->value, $statuses);
    }
}
