@php
    $hourNow = now()->hour;
    $greeting = $hourNow < 12 ? 'Good morning' : ($hourNow < 17 ? 'Good afternoon' : 'Good evening');
    $nextPickup = $upcomingPickups->first();

    $nextPickupPlace = $nextPickup
        ? $nextPickup->farmer->stall_name . ' at ' . $nextPickup->market->name
            . ($nextPickup->pickupWindow ? ', ' . $nextPickup->pickupWindow->timeRangeText() : '')
        : null;
@endphp

<x-layouts.panel title="Dashboard">

    <x-ui.announcement-list :announcements="$announcements" />

    {{-- welcome banner mein agla pickup - customer sab se pehle yahi dekhna chahta hai --}}
    <section class="relative mb-8 overflow-hidden rounded-card">
        <img src="{{ asset('images/site/hero-produce-stall.webp') }}" alt="" class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-r from-leaf-900/90 via-leaf-900/70 to-leaf-900/20"></div>

        <div class="relative flex flex-col gap-6 p-8 sm:p-10 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm text-leaf-100">{{ $greeting }}, {{ $customer->firstName() }}</p>
                @if ($nextPickup)
                    <h2 class="mt-2 text-2xl font-semibold text-white sm:text-3xl">
                        Your next pickup is {{ $nextPickup->pickup_date->isToday() ? 'today' : $nextPickup->pickup_date->format('l, j M') }}
                    </h2>
                    <p class="mt-2 text-white/80">{{ $nextPickupPlace }}</p>
                @else
                    <h2 class="mt-2 text-2xl font-semibold text-white sm:text-3xl">What's fresh this week?</h2>
                    <p class="mt-2 text-white/80">Browse local stalls and reserve before it sells out.</p>
                @endif
            </div>

            <div class="flex flex-wrap gap-3">
                @if (Route::has('customer.products.index'))
                    <a href="{{ route('customer.products.index') }}" class="btn-accent">
                        <iconify-icon icon="tabler:basket"></iconify-icon>
                        Browse products
                    </a>
                @endif
                @if (Route::has('customer.markets.index'))
                    <a href="{{ route('customer.markets.index') }}" class="btn-glass">
                        <iconify-icon icon="tabler:map-pin"></iconify-icon>
                        Find a market
                    </a>
                @endif
            </div>
        </div>
    </section>

    <section class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Active orders" :value="$customerStats['open_orders']" icon="tabler:clock" tone="amber" />
        <x-ui.stat-card label="Completed pickups" :value="$customerStats['completed_orders']" icon="tabler:circle-check" />
        <x-ui.stat-card label="Favourite farmers" :value="$customerStats['favourite_farmers']" icon="tabler:heart" tone="tomato" />
        <x-ui.stat-card label="Spent at local stalls" icon="tabler:wallet" tone="sky"
                        :value="\App\Helpers\MoneyFormatter::format($customerStats['total_spent'])" />
    </section>

    <div class="grid gap-8 xl:grid-cols-5">

        <section class="card xl:col-span-3">
            <x-ui.section-heading title="Recent orders" link-text="See all orders" link-route="customer.orders.index" />

            @if ($recentOrders->isEmpty())
                <x-ui.empty-state icon="tabler:receipt" title="No orders yet"
                                  message="Your pre-orders will show up here with their pickup status." />
            @else
                <div class="overflow-x-auto">
                    <table class="table-clean">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Farmer</th>
                                <th>Pickup</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentOrders as $order)
                                <tr>
                                    <td class="font-medium">
                                        <a href="{{ route('customer.orders.show', $order) }}" class="hover:text-leaf-700">{{ $order->order_number }}</a>
                                    </td>
                                    <td>{{ $order->farmer->stall_name }}</td>
                                    <td class="text-soil-muted">{{ $order->pickup_date->format('j M') }}</td>
                                    <td><x-ui.money :amount="$order->total_amount" /></td>
                                    <td><x-ui.order-status-badge :status="$order->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="xl:col-span-2">
            <x-ui.section-heading title="Markets to explore" link-text="View map" link-route="customer.markets.index" />

            <div class="space-y-4">
                @forelse ($marketsToExplore as $market)
                    <a href="{{ route('customer.markets.show', $market) }}" class="card flex gap-4 p-4 transition hover:-translate-y-0.5 hover:shadow-xl">
                        <img src="{{ $market->coverImageUrl() }}" alt="{{ $market->name }}"
                             class="h-20 w-24 shrink-0 rounded-xl object-cover">
                        <div class="min-w-0">
                            <h3 class="truncate font-semibold">{{ $market->name }}</h3>
                            <p class="mt-1 flex items-center gap-1.5 text-sm text-soil-muted">
                                <iconify-icon icon="tabler:calendar"></iconify-icon>
                                {{ $market->operatingDaysText() }}
                            </p>
                            <span class="mt-2 inline-flex rounded-full bg-leaf-50 px-3 py-0.5 text-xs font-medium text-leaf-700">
                                {{ $market->approved_farmers_count }} {{ \Illuminate\Support\Str::plural('farmer', $market->approved_farmers_count) }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="card">
                        <x-ui.empty-state icon="tabler:map-pin" title="No markets listed yet" />
                    </div>
                @endforelse
            </div>
        </section>
    </div>

</x-layouts.panel>
