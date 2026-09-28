<x-layouts.panel title="Dashboard">

    @unless ($farmer->isApproved())
        @php
            $stallIsSuspended = $farmer->approval_status === \App\Enums\FarmerApprovalStatus::Suspended;
        @endphp
        <div @class([
            'mb-8 flex gap-4 rounded-card border p-5',
            'border-red-200 bg-red-50' => $stallIsSuspended,
            'border-amber-200 bg-amber-50' => ! $stallIsSuspended,
        ])>
            <span @class([
                'icon-chip bg-white',
                'text-red-600' => $stallIsSuspended,
                'text-amber-600' => ! $stallIsSuspended,
            ])>
                <iconify-icon icon="{{ $stallIsSuspended ? 'tabler:alert-triangle' : 'tabler:hourglass' }}"></iconify-icon>
            </span>
            <div>
                <p @class(['font-medium', 'text-red-900' => $stallIsSuspended, 'text-amber-900' => ! $stallIsSuspended])>
                    {{ $stallIsSuspended ? 'Your stall is suspended' : 'Your stall is waiting for admin approval' }}
                </p>
                @if ($stallIsSuspended)
                    <p class="mt-1 text-sm text-red-800">
                        Customers can't see your products while your stall is suspended.
                        @if ($farmer->suspension_reason)
                            Reason: {{ $farmer->suspension_reason }}.
                        @endif
                        Contact the MarketLink team if you think this is a mistake.
                    </p>
                @else
                    <p class="mt-1 text-sm text-amber-800">
                        Customers will see it once it's approved. You can still set up products and pickup slots in the meantime.
                    </p>
                @endif
            </div>
        </div>
    @endunless

    <x-ui.announcement-list :announcements="$announcements" />

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-soil-muted">{{ $farmer->stall_name }}</p>
            <h2 class="mt-1 text-2xl font-semibold">Here's how this week is going</h2>
        </div>
        @if (Route::has('farmer.products.create'))
            <a href="{{ route('farmer.products.create') }}" class="btn-primary">
                <iconify-icon icon="tabler:plus"></iconify-icon>
                Add product
            </a>
        @endif
    </div>

    @if ($openUrgentOrders->isNotEmpty())
        <section class="card mb-8 border-red-100">
            <div class="mb-4 flex items-center gap-3">
                <span class="icon-chip h-10 w-10 bg-red-50 text-red-600">
                    <iconify-icon icon="tabler:clock-bolt"></iconify-icon>
                </span>
                <div>
                    <h3 class="font-semibold">Urgent orders</h3>
                    <p class="text-sm text-soil-muted">Customers coming to {{ $farmer->address }} soon.</p>
                </div>
            </div>

            <ul class="divide-y divide-white/60">
                @foreach ($openUrgentOrders as $urgentOrder)
                    <li class="flex flex-wrap items-center gap-3 py-3 first:pt-0 last:pb-0">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ $urgentOrder->customer->name }} <span class="text-sm font-normal text-soil-muted">{{ $urgentOrder->order_number }}</span></p>
                            <p class="text-sm text-soil-muted">
                                {{ $urgentOrder->items->map(fn ($orderItem) => $orderItem->quantity.' '.$orderItem->unit.' '.$orderItem->product_name)->join(', ') }}
                            </p>
                        </div>
                        <x-ui.urgent-badge :order="$urgentOrder" />
                        <x-ui.order-status-badge :status="$urgentOrder->status" />
                        <a href="{{ route('farmer.orders.show', $urgentOrder) }}" class="text-sm font-medium text-leaf-600 hover:text-leaf-800">View</a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Total orders" :value="$farmerStats['total_orders']" icon="tabler:clipboard-list" />
        <x-ui.stat-card label="Waiting for your reply" :value="$farmerStats['pending_orders']" icon="tabler:bell-ringing" tone="amber" />
        <x-ui.stat-card label="Revenue from pickups" icon="tabler:cash" tone="sky"
                        :value="\App\Helpers\MoneyFormatter::format($farmerStats['revenue'])" />
        <x-ui.stat-card label="Products running low" :value="$farmerStats['low_stock_products']" icon="tabler:alert-triangle" tone="tomato"
                        hint="{{ config('marketlink.low_stock_threshold') }} or fewer left" />
    </section>

    <div class="grid gap-8 xl:grid-cols-5">

        <section class="card xl:col-span-3">
            <x-ui.section-heading title="New pre-orders" link-text="Manage all orders" link-route="farmer.orders.index" />

            @if ($ordersWaitingForReply->isEmpty())
                <x-ui.empty-state icon="tabler:mood-happy" title="You're all caught up"
                                  message="New pre-orders will appear here so you can accept or decline them." />
            @else
                <ul class="divide-y divide-white/60">
                    @foreach ($ordersWaitingForReply as $order)
                        <li class="flex flex-wrap items-center gap-4 py-4 first:pt-0 last:pb-0">
                            <span class="glass-icon-chip text-sm font-semibold text-tomato-700">
                                {{ strtoupper(substr($order->customer->name, 0, 1)) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium">{{ $order->customer->name }}</p>
                                <p class="text-sm text-soil-muted">
                                    {{ $order->items_count }} {{ \Illuminate\Support\Str::plural('item', $order->items_count) }},
                                    pickup {{ $order->pickup_date->format('D j M') }} at {{ $order->market->name }}
                                </p>
                            </div>
                            <x-ui.money :amount="$order->total_amount" class="font-semibold" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="card xl:col-span-2">
            <x-ui.section-heading title="Best sellers" link-text="Full insights" link-route="farmer.insights.index" />

            @if ($bestSellingProducts->isEmpty())
                <x-ui.empty-state icon="tabler:chart-bar" title="No sales yet"
                                  message="Once orders are picked up, your top products show here." />
            @else
                @php $highestQuantitySold = $bestSellingProducts->max('total_quantity_sold'); @endphp

                <ul class="space-y-5">
                    @foreach ($bestSellingProducts as $bestSeller)
                        <li>
                            <div class="mb-1.5 flex justify-between gap-3 text-sm">
                                <span class="font-medium">{{ $bestSeller->product_name }}</span>
                                <span class="text-soil-muted">{{ $bestSeller->total_quantity_sold }} {{ $bestSeller->unit }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-white/70">
                                <div class="h-full rounded-full bg-gradient-to-r from-tomato-300 to-tomato-500"
                                     style="width: {{ round($bestSeller->total_quantity_sold / $highestQuantitySold * 100) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

</x-layouts.panel>
