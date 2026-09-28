<x-layouts.panel title="Admin dashboard">

    {{-- har block apni permission ke andar hai - AdminDashboardController dekho --}}
    @php
        $statCardDetails = [
            'farmers' => ['label' => 'Farmers', 'icon' => 'tabler:tractor', 'tone' => 'leaf'],
            'customers' => ['label' => 'Customers', 'icon' => 'tabler:users', 'tone' => 'sky'],
            'markets' => ['label' => 'Markets', 'icon' => 'tabler:map-2', 'tone' => 'tomato'],
            'orders' => ['label' => 'Orders', 'icon' => 'tabler:receipt', 'tone' => 'amber'],
            'revenue' => ['label' => 'Pickup revenue', 'icon' => 'tabler:cash', 'tone' => 'leaf'],
        ];
    @endphp

    {{-- auto-fit: jitne bhi cards hon, row barabar bant jati hai --}}
    <section class="mb-8 grid grid-cols-[repeat(auto-fit,minmax(11rem,1fr))] gap-4">
        @foreach ($platformStats as $statKey => $statValue)
            <x-ui.stat-card :label="$statCardDetails[$statKey]['label']" :icon="$statCardDetails[$statKey]['icon']" :tone="$statCardDetails[$statKey]['tone']"
                            :value="$statKey === 'revenue' ? \App\Helpers\MoneyFormatter::format($statValue) : $statValue" />
        @endforeach
    </section>

    @if ($canSee['farmers'] || $canSee['reports'])
        <div class="mb-8 grid gap-8 xl:grid-cols-5">

            @if ($canSee['farmers'])
                <section @class(['card', 'xl:col-span-3' => $canSee['reports'], 'xl:col-span-5' => ! $canSee['reports']])>
                    <x-ui.section-heading title="Farmers waiting for approval" link-text="All farmers" link-route="admin.farmers.index" />

                    @if ($farmersWaitingForApproval->isEmpty())
                        <x-ui.empty-state icon="tabler:user-check" title="No pending requests"
                                          message="New farmer sign-ups will show here for review." />
                    @else
                        <ul class="divide-y divide-white/60">
                            @foreach ($farmersWaitingForApproval as $pendingFarmer)
                                <li class="flex items-center gap-3 py-3.5 first:pt-0 last:pb-0">
                                    <span class="glass-icon-chip text-amber-700">
                                        <iconify-icon icon="tabler:building-store"></iconify-icon>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        {{-- do lines tak, taake lambe stall naam Approve ke saath kat na jayen --}}
                                        <p class="line-clamp-2 break-words font-medium">{{ $pendingFarmer->stall_name }}</p>
                                        <p class="line-clamp-2 text-sm text-soil-muted">
                                            {{ $pendingFarmer->contact_person }}, signed up {{ $pendingFarmer->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    {{-- farmer ke admin page wala hi approve route --}}
                                    <form method="POST" action="{{ route('admin.farmers.approve', $pendingFarmer) }}" class="shrink-0">
                                        @csrf
                                        <button type="submit" class="btn-glass-orange px-4 py-1.5 text-xs">Approve</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endif

            @if ($canSee['reports'])
                <section @class(['card', 'xl:col-span-2' => $canSee['farmers'], 'xl:col-span-5' => ! $canSee['farmers']])>
                    <x-ui.section-heading title="Orders by status" />

                    @php $largestStatusCount = max($ordersPerStatus->max(), 1); @endphp

                    <ul class="space-y-4">
                        @foreach ($ordersPerStatus as $statusLabel => $orderCount)
                            <li class="grid grid-cols-[8.5rem_1fr_2.5rem] items-center gap-3 text-sm">
                                <span class="text-soil-muted">{{ $statusLabel }}</span>
                                <div class="h-2.5 overflow-hidden rounded-full bg-white/70">
                                    <div class="h-full rounded-full bg-gradient-to-r from-leaf-300 to-leaf-500" style="width: {{ round($orderCount / $largestStatusCount * 100) }}%"></div>
                                </div>
                                <span class="text-right font-medium">{{ $orderCount }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    @endif

    @if ($canSee['farmers'] || $canSee['customers'])
        <div class="grid gap-8 xl:grid-cols-2">

            @if ($canSee['farmers'])
                <section class="card">
                    <x-ui.section-heading title="Most active farmers"
                                           :link-text="$canSee['reports'] ? 'Reports' : null"
                                           :link-route="$canSee['reports'] ? 'admin.reports.index' : null" />

                    <div class="overflow-x-auto">
                        <table class="table-clean">
                            <thead>
                                <tr>
                                    <th>Stall</th>
                                    <th class="text-right">Completed orders</th>
                                    @if ($canSee['reports'])
                                        <th class="text-right">Revenue</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($mostActiveFarmers as $activeFarmer)
                                    <tr>
                                        <td class="font-medium">{{ $activeFarmer->stall_name }}</td>
                                        <td class="text-right">{{ $activeFarmer->completed_orders_count }}</td>
                                        @if ($canSee['reports'])
                                            <td class="text-right"><x-ui.money :amount="$activeFarmer->completed_revenue ?? 0" /></td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-soil-muted">No approved farmers yet</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            @if ($canSee['customers'])
                <section class="card">
                    <x-ui.section-heading title="Latest orders" />

                    <div class="overflow-x-auto">
                        <table class="table-clean">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Farmer</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($latestOrders as $order)
                                    <tr>
                                        <td class="font-medium">{{ $order->order_number }}</td>
                                        <td>{{ $order->customer->name }}</td>
                                        <td class="text-soil-muted">{{ $order->farmer->stall_name }}</td>
                                        <td><x-ui.order-status-badge :status="$order->status" /></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-soil-muted">No orders yet</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>
    @endif

</x-layouts.panel>
