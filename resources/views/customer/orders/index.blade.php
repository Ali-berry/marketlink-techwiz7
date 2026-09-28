@php
    $tabLabels = ['upcoming' => 'Upcoming', 'past' => 'Past'];
    $tabCounts = ['upcoming' => $upcomingCount, 'past' => $pastCount];
@endphp

<x-layouts.panel title="My orders">

    <div class="mb-6">
        <h2 class="text-2xl font-semibold">My orders</h2>
        <p class="mt-1 text-sm text-soil-muted">Every pre-order you've placed, and where it's up to.</p>
    </div>

    <div class="tab-bar">
        @foreach ($tabLabels as $tabKey => $tabLabel)
            <a href="{{ route('customer.orders.index', ['tab' => $tabKey]) }}"
               @class(['tab-pill', 'is-active' => $activeTab === $tabKey])>
                {{ $tabLabel }} ({{ $tabCounts[$tabKey] }})
            </a>
        @endforeach
    </div>

    @if ($orders->isEmpty())
        <div class="card">
            <x-ui.empty-state icon="tabler:receipt" title="No orders here"
                               message="{{ $activeTab === 'upcoming' ? 'Your active pre-orders will show up here.' : 'Completed, declined and cancelled orders show up here.' }}" />
        </div>
    @else
        <div class="card overflow-x-auto">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Farmer</th>
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
                            <td class="font-medium">{{ $order->order_number }}</td>
                            <td>{{ $order->farmer->stall_name }}</td>
                            @if ($order->is_urgent)
                                <td><x-ui.urgent-badge :order="$order" /></td>
                                <td class="text-soil-muted">At the farm</td>
                            @else
                                <td class="text-soil-muted">{{ $order->pickup_date->format('D j M') }}</td>
                                <td class="text-soil-muted">{{ $order->market->name }}</td>
                            @endif
                            <td class="text-soil-muted">{{ $order->items_count }}</td>
                            <td><x-ui.money :amount="$order->total_amount" /></td>
                            <td><x-ui.order-status-badge :status="$order->status" /></td>
                            <td>
                                <a href="{{ route('customer.orders.show', $order) }}" class="text-sm font-medium text-leaf-600 hover:text-leaf-800">View</a>
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
