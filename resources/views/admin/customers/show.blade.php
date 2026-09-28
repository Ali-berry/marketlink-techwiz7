<x-layouts.panel :title="$customer->name">

    <div class="mb-6">
        <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-soil-muted hover:text-soil">
            <iconify-icon icon="tabler:arrow-left"></iconify-icon>
            Back to customers
        </a>
    </div>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-semibold">{{ $customer->name }}</h1>
            <p class="mt-1 text-sm text-soil-muted">{{ $customer->email }} &middot; {{ $customer->phone ?? 'No phone on file' }}</p>
            <p class="mt-1 text-sm text-soil-muted">Joined {{ $customer->created_at->format('j M Y') }}</p>
        </div>

        <form method="POST" action="{{ route('admin.customers.toggle-active', $customer) }}">
            @csrf
            <button type="submit" class="{{ $customer->is_active ? 'btn-danger' : 'btn-primary' }}">
                {{ $customer->is_active ? 'Deactivate account' : 'Activate account' }}
            </button>
        </form>
    </div>

    <section class="mt-8">
        <x-ui.section-heading title="Order history" />

        @if ($orders->isEmpty())
            <div class="card">
                <x-ui.empty-state icon="tabler:receipt" title="No orders yet" />
            </div>
        @else
            <div class="card overflow-x-auto">
                <table class="table-clean">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Farmer</th>
                            <th>Market</th>
                            <th>Pickup</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td class="font-medium">{{ $order->order_number }}</td>
                                <td>{{ $order->farmer->stall_name }}</td>
                                <td class="text-soil-muted">{{ $order->market->name }}</td>
                                <td class="text-soil-muted">{{ $order->pickup_date->format('j M Y') }}</td>
                                <td><x-ui.money :amount="$order->total_amount" /></td>
                                <td><x-ui.order-status-badge :status="$order->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6">{{ $orders->links() }}</div>
        @endif
    </section>

</x-layouts.panel>
