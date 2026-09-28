<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\OrderModificationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\UpdateOrderQuantitiesRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Services\Cart;
use App\Services\OrderItemQuantityUpdater;
use App\Services\OrderStatusUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    // jo orders khatam ho chuke
    private const CLOSED_STATUSES = [OrderStatus::Completed->value, OrderStatus::Declined->value, OrderStatus::Cancelled->value];

    public function index(Request $request): View
    {
        $customer = $request->user();
        $activeTab = $request->query('tab') === 'past' ? 'past' : 'upcoming';

        $upcomingCount = $customer->ordersAsCustomer()->open()->count();
        $pastCount = $customer->ordersAsCustomer()->whereIn('status', self::CLOSED_STATUSES)->count();

        $orders = $customer->ordersAsCustomer()
            ->when($activeTab === 'upcoming', fn ($query) => $query->open())
            ->when($activeTab === 'past', fn ($query) => $query->whereIn('status', self::CLOSED_STATUSES))
            ->with(['farmer', 'market'])
            ->withCount('items')
            ->orderByDesc('pickup_date')
            ->paginate(10)
            ->withQueryString();

        return view('customer.orders.index', compact('orders', 'activeTab', 'upcomingCount', 'pastCount'));
    }

    public function show(Request $request, Order $order): View
    {
        $this->abortUnlessOwnedByCustomer($request, $order);

        $order->load(['farmer', 'market', 'pickupWindow', 'items.product', 'statusChanges.changedBy']);

        return view('customer.orders.show', [
            'order' => $order,
            'canModify' => $order->customerCanStillChange(),
            // chalte hue order ko reorder karne se same items do baar book honge
            'canReorder' => $order->status->isClosed(),
            'hasReviewedOrder' => $order->status === OrderStatus::Completed
                && Review::where('order_id', $order->id)->where('customer_id', $request->user()->id)->exists(),
        ]);
    }

    public function updateQuantities(UpdateOrderQuantitiesRequest $request, Order $order, OrderItemQuantityUpdater $updater): RedirectResponse
    {
        try {
            $updater->update($order, $request->validated()['quantities']);
        } catch (OrderModificationException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Your order was updated.');
    }

    public function cancel(Request $request, Order $order, OrderStatusUpdater $updater): RedirectResponse
    {
        $this->abortUnlessOwnedByCustomer($request, $order);

        try {
            $updater->transition($order, OrderStatus::Cancelled, $request->user());
        } catch (InvalidOrderTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Order cancelled.');
    }

    // is order mein se jo abhi order ho sakta hai wo basket mein wapas
    public function reorder(Request $request, Order $order, Cart $cart): RedirectResponse
    {
        $this->abortUnlessOwnedByCustomer($request, $order);

        // order page ke button wala rule, direct POST ke liye yahan bhi
        if (! $order->status->isClosed()) {
            return back()->with('error', 'You can order this again once it has been completed, declined or cancelled.');
        }

        $order->load('items');
        $addedProductNames = [];
        $skippedProductNames = [];

        foreach ($order->items as $item) {
            $product = $item->product_id ? Product::visibleToCustomers()->find($item->product_id) : null;

            if (! $product || ! $product->canBeOrdered()) {
                $skippedProductNames[] = $item->product_name;

                continue;
            }

            $cart->add($product, min($item->quantity, $product->stock_quantity));
            $addedProductNames[] = $product->name;
        }

        if (empty($addedProductNames)) {
            return redirect()->route('customer.cart.index')->with('error', 'None of the items from that order are available right now.');
        }

        $message = count($addedProductNames).' item(s) added to your basket.';

        if ($skippedProductNames) {
            $message .= ' Not available any more: '.implode(', ', $skippedProductNames).'.';
        }

        return redirect()->route('customer.cart.index')->with('success', $message);
    }

    private function abortUnlessOwnedByCustomer(Request $request, Order $order): void
    {
        abort_unless($order->customer_id === $request->user()->id, 403);
    }
}
