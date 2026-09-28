<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>MarketLink report - {{ $startDate->format('j M Y') }} to {{ $endDate->format('j M Y') }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #1F2A22; margin: 2rem; }
        h1 { font-size: 1.4rem; margin-bottom: 0.25rem; }
        h2 { font-size: 1.1rem; margin-top: 2rem; border-bottom: 1px solid #ddd; padding-bottom: 0.25rem; }
        p.subtitle { color: #5B665E; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 0.75rem; font-size: 0.9rem; }
        th, td { text-align: left; padding: 0.4rem 0.6rem; border-bottom: 1px solid #eee; }
        th { color: #5B665E; font-weight: 600; }
        .summary { display: flex; gap: 2rem; margin-top: 1rem; }
        .summary div { border: 1px solid #eee; border-radius: 8px; padding: 0.75rem 1.25rem; }
        .summary strong { display: block; font-size: 1.3rem; }
        .print-button { margin-top: 1.5rem; }
        @media print {
            .print-button { display: none; }
        }
    </style>
</head>
<body>
    <h1>MarketLink report</h1>
    <p class="subtitle">{{ $startDate->format('j M Y') }} to {{ $endDate->format('j M Y') }}</p>

    <button class="print-button" onclick="window.print()">Print this report</button>

    <div class="summary">
        <div><span>Orders</span><strong>{{ $summary['total_orders'] }}</strong></div>
        <div><span>Revenue</span><strong>{{ \App\Helpers\MoneyFormatter::format($summary['total_revenue']) }}</strong></div>
        <div><span>New customers</span><strong>{{ $summary['new_customers'] }}</strong></div>
    </div>

    <h2>Orders by status</h2>
    <table>
        <thead><tr><th>Status</th><th>Orders</th></tr></thead>
        <tbody>
            @foreach ($ordersByStatus as $row)
                <tr><td>{{ $row['label'] }}</td><td>{{ $row['value'] }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Revenue per market</h2>
    <table>
        <thead><tr><th>Market</th><th>Revenue</th></tr></thead>
        <tbody>
            @forelse ($revenuePerMarket as $row)
                <tr><td>{{ $row['label'] }}</td><td>{{ \App\Helpers\MoneyFormatter::format($row['value']) }}</td></tr>
            @empty
                <tr><td colspan="2">No completed orders in this range</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Most active farmers</h2>
    <table>
        <thead><tr><th>Stall</th><th>Orders</th><th>Revenue</th></tr></thead>
        <tbody>
            @forelse ($mostActiveFarmers as $activeFarmer)
                <tr>
                    <td>{{ $activeFarmer->stall_name }}</td>
                    <td>{{ $activeFarmer->orders_in_range_count }}</td>
                    <td>{{ \App\Helpers\MoneyFormatter::format($activeFarmer->revenue_in_range ?? 0) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No farmer activity in this range</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>New customers per week</h2>
    <table>
        <thead><tr><th>Week starting</th><th>New customers</th></tr></thead>
        <tbody>
            @foreach ($newCustomersPerWeek as $row)
                <tr><td>{{ $row['label'] }}</td><td>{{ $row['value'] }}</td></tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
