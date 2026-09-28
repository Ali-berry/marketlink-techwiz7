<x-layouts.panel title="Reports">

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold">Reports</h2>
            <p class="mt-1 text-sm text-soil-muted">Platform activity for the date range below.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.reports.export-csv', request()->only(['start_date', 'end_date'])) }}" class="btn-outline">
                <iconify-icon icon="tabler:file-spreadsheet"></iconify-icon>
                Export CSV
            </a>
            <a href="{{ route('admin.reports.print', request()->only(['start_date', 'end_date'])) }}" target="_blank" class="btn-outline">
                <iconify-icon icon="tabler:printer"></iconify-icon>
                Print view
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.reports.index') }}" class="card mb-8 flex flex-wrap items-end gap-4 p-5">
        <div>
            <label class="form-label" for="start_date">From</label>
            <input id="start_date" type="date" name="start_date" value="{{ $startDate }}" class="form-input">
        </div>
        <div>
            <label class="form-label" for="end_date">To</label>
            <input id="end_date" type="date" name="end_date" value="{{ $endDate }}" class="form-input">
        </div>
        <button type="submit" class="btn-primary">Apply</button>
    </form>

    <section class="mb-8 grid gap-4 sm:grid-cols-3">
        <x-ui.stat-card label="Orders in range" :value="$summary['total_orders']" icon="tabler:clipboard-list" />
        <x-ui.stat-card label="Revenue in range" icon="tabler:cash" tone="sky"
                        :value="\App\Helpers\MoneyFormatter::format($summary['total_revenue'])" />
        <x-ui.stat-card label="New customers" :value="$summary['new_customers']" icon="tabler:user-plus" tone="tomato" />
    </section>

    <div class="grid gap-8 lg:grid-cols-2">
        <section class="card">
            <h3 class="font-semibold">Orders by status</h3>
            <div class="mt-4">
                <canvas data-bar-chart data-chart-rows="{{ json_encode($ordersByStatus) }}" data-chart-label="Orders" data-chart-color="#2F6B3F" height="180"></canvas>
            </div>
        </section>

        <section class="card">
            <h3 class="font-semibold">Revenue per market</h3>
            @if ($revenuePerMarket->isEmpty())
                <x-ui.empty-state icon="tabler:map-2" title="No completed orders in this range" />
            @else
                <div class="mt-4">
                    <canvas data-bar-chart data-chart-rows="{{ json_encode($revenuePerMarket) }}" data-chart-label="Revenue" data-chart-color="#E0662F" height="180"></canvas>
                </div>
            @endif
        </section>

        <section class="card lg:col-span-2">
            <h3 class="font-semibold">New customers per week</h3>
            <div class="mt-4">
                <canvas data-line-chart data-chart-rows="{{ json_encode($newCustomersPerWeek) }}" data-chart-label="New customers" data-chart-color="#2F6B3F" height="90"></canvas>
            </div>
        </section>
    </div>

    <section class="mt-8 card">
        <h3 class="font-semibold">Most active farmers</h3>
        <div class="mt-4 overflow-x-auto">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th>Stall</th>
                        <th class="text-right">Orders in range</th>
                        <th class="text-right">Revenue in range</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mostActiveFarmers as $activeFarmer)
                        <tr>
                            <td class="font-medium">{{ $activeFarmer->stall_name }}</td>
                            <td class="text-right">{{ $activeFarmer->orders_in_range_count }}</td>
                            <td class="text-right"><x-ui.money :amount="$activeFarmer->revenue_in_range ?? 0" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-soil-muted">No farmer activity in this range</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @push('scripts')
        @vite('resources/js/reports-charts.js')
    @endpush

</x-layouts.panel>
