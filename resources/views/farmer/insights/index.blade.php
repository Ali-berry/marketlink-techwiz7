<x-layouts.panel title="Sales insights">

    <div class="mb-6">
        <h2 class="text-2xl font-semibold">Sales insights</h2>
        <p class="mt-1 text-sm text-soil-muted">How your stall has been doing.</p>
    </div>

    <section class="mb-8 grid gap-4 sm:grid-cols-3">
        <x-ui.stat-card label="Total orders" :value="$summary['total_orders']" icon="tabler:clipboard-list" />
        <x-ui.stat-card label="Waiting for your reply" :value="$summary['pending_orders']" icon="tabler:bell-ringing" tone="amber" />
        <x-ui.stat-card label="Revenue from pickups" icon="tabler:cash" tone="sky"
                        :value="\App\Helpers\MoneyFormatter::format($summary['revenue'])" />
    </section>

    <section class="card mb-8">
        <h3 class="font-semibold">Revenue, last 8 weeks</h3>
        <div class="mt-4">
            <canvas data-revenue-chart data-weekly-revenue="{{ json_encode($weeklyRevenue) }}" height="90"></canvas>
        </div>
    </section>

    <div class="grid gap-8 lg:grid-cols-2">
        <section class="card">
            <h3 class="font-semibold">Best-selling products</h3>

            @if ($bestSellingProducts->isEmpty())
                <x-ui.empty-state icon="tabler:chart-bar" title="No sales yet"
                                   message="Once orders are picked up, your top products show here." />
            @else
                @php $highestQuantitySold = $bestSellingProducts->max('total_quantity_sold'); @endphp
                <ul class="mt-4 space-y-5">
                    @foreach ($bestSellingProducts as $bestSeller)
                        <li>
                            <div class="mb-1.5 flex justify-between gap-3 text-sm">
                                <span class="font-medium">{{ $bestSeller->product_name }}</span>
                                <span class="text-soil-muted">
                                    {{ $bestSeller->total_quantity_sold }} {{ $bestSeller->unit }} &middot;
                                    <x-ui.money :amount="$bestSeller->total_earned" />
                                </span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-white/70">
                                <div class="h-full rounded-full bg-leaf-400" style="width: {{ round($bestSeller->total_quantity_sold / $highestQuantitySold * 100) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="card">
            <h3 class="font-semibold">Revenue per market</h3>

            @if ($revenuePerMarket->isEmpty())
                <x-ui.empty-state icon="tabler:map-2" title="No completed orders yet" />
            @else
                @php $highestMarketRevenue = $revenuePerMarket->max(); @endphp
                <ul class="mt-4 space-y-5">
                    @foreach ($revenuePerMarket as $marketName => $total)
                        <li>
                            <div class="mb-1.5 flex justify-between gap-3 text-sm">
                                <span class="font-medium">{{ $marketName }}</span>
                                <x-ui.money :amount="$total" class="text-soil-muted" />
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-white/70">
                                <div class="h-full rounded-full bg-tomato-400"
                                     style="width: {{ $highestMarketRevenue > 0 ? round($total / $highestMarketRevenue * 100) : 0 }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    @push('scripts')
        @vite('resources/js/insights-chart.js')
    @endpush

</x-layouts.panel>
