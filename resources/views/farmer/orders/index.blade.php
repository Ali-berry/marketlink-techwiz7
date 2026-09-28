@php
    $tabLabels = [
        'new' => 'New',
        'accepted' => 'Accepted',
        'ready' => 'Ready for pickup',
        'completed' => 'Completed',
        'closed' => 'Declined / cancelled',
    ];
@endphp

<x-layouts.panel title="Pre-orders">

    <div class="mb-6">
        <h2 class="text-2xl font-semibold">Pre-orders</h2>
        <p class="mt-1 text-sm text-soil-muted">Accept or decline new orders, then track them through to pickup.</p>
    </div>

    <div class="tab-bar">
        @foreach ($tabLabels as $tabKey => $tabLabel)
            <a href="{{ route('farmer.orders.index', array_merge(request()->except(['tab', 'page']), ['tab' => $tabKey])) }}"
               @class(['tab-pill', 'is-active' => $activeTab === $tabKey])>
                {{ $tabLabel }} ({{ $tabCounts[$tabKey] }})
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('farmer.orders.index') }}" class="card mb-6 grid gap-4 p-5 sm:grid-cols-4">
        <input type="hidden" name="tab" value="{{ $activeTab }}">

        <div>
            <label class="form-label" for="pickup_date">Pickup date</label>
            <input id="pickup_date" type="date" name="pickup_date" value="{{ request('pickup_date') }}" class="form-input">
        </div>
        <div>
            <label class="form-label" for="market_id">Market</label>
            <select id="market_id" name="market_id" class="form-input">
                <option value="">All markets</option>
                @foreach ($markets as $market)
                    <option value="{{ $market->id }}" @selected(request('market_id') == $market->id)>{{ $market->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-3 sm:col-span-2">
            @if (request()->anyFilled(['pickup_date', 'market_id']))
                <a href="{{ route('farmer.orders.index', ['tab' => $activeTab]) }}" class="btn-outline">Clear filters</a>
            @endif
            <button type="submit" class="btn-primary">Filter</button>
        </div>
    </form>

    @if ($orders->isEmpty())
        <div class="card">
            <x-ui.empty-state icon="tabler:clipboard-list" title="No orders here"
                               message="Nothing matches this tab and filter right now." />
        </div>
    @else
        <div class="card overflow-x-auto">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Pickup</th>
                        <th>Market</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td class="font-medium">
                                <span class="whitespace-nowrap">{{ $order->order_number }}</span>
                                @if ($order->wasPlacedViaAiChat())
                                    <span class="ml-1 inline-flex rounded-full bg-tomato-50 px-2 py-0.5 text-xs font-semibold text-tomato-700"
                                          title="Placed through MarketLink AI chat">AI</span>
                                @endif
                            </td>
                            <td>{{ $order->customer->name }}</td>
                            @if ($order->is_urgent)
                                <td><x-ui.urgent-badge :order="$order" /></td>
                                <td class="text-soil-muted">At your stall</td>
                            @else
                                <td class="text-soil-muted">{{ $order->pickup_date->format('D j M') }}</td>
                                <td class="text-soil-muted">{{ $order->market->name }}</td>
                            @endif
                            <td class="text-soil-muted">{{ $order->items_count }}</td>
                            <td><x-ui.money :amount="$order->total_amount" /></td>
                            <td><x-ui.order-status-badge :status="$order->status" /></td>
                            <td>
                                <a href="{{ route('farmer.orders.show', $order) }}" class="text-sm font-medium text-leaf-600 hover:text-leaf-800">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    @endif

</x-layouts.panel>
