<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\FarmerProfile;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$startDate, $endDate] = $this->dateRangeFrom($request);

        return view('admin.reports.index', [
            ...$this->buildReportData($startDate, $endDate),
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
        ]);
    }

    public function print(Request $request): View
    {
        [$startDate, $endDate] = $this->dateRangeFrom($request);

        return view('admin.reports.print', [
            ...$this->buildReportData($startDate, $endDate),
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function exportCsv(Request $request): Response
    {
        [$startDate, $endDate] = $this->dateRangeFrom($request);

        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->with(['customer', 'farmer', 'market'])
            ->orderBy('created_at')
            ->get();

        $csvLines = $orders->map(fn (Order $order) => implode(',', array_map(
            fn ($value) => '"'.str_replace('"', '""', $value).'"',
            [
                $order->order_number,
                $order->created_at->toDateString(),
                $order->customer->name,
                $order->farmer->stall_name,
                $order->market->name,
                $order->pickup_date->toDateString(),
                $order->status->label(),
                $order->total_amount,
            ]
        )));

        $csvHeader = 'Order number,Placed on,Customer,Farmer,Market,Pickup date,Status,Total';
        $csvBody = $csvHeader."\n".$csvLines->implode("\n");

        $fileName = 'marketlink-report-'.$startDate->toDateString().'-to-'.$endDate->toDateString().'.csv';

        return response($csvBody, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    private function dateRangeFrom(Request $request): array
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->subWeeks(8)->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        return [$startDate, $endDate];
    }

    private function buildReportData(Carbon $startDate, Carbon $endDate): array
    {
        $ordersInRange = Order::whereBetween('created_at', [$startDate, $endDate]);

        $summary = [
            'total_orders' => (clone $ordersInRange)->count(),
            'total_revenue' => (clone $ordersInRange)->where('status', OrderStatus::Completed->value)->sum('total_amount'),
            'new_customers' => User::where('role', UserRole::Customer->value)->whereBetween('created_at', [$startDate, $endDate])->count(),
        ];

        $ordersByStatus = collect(OrderStatus::cases())->map(fn (OrderStatus $status) => [
            'label' => $status->label(),
            'value' => (clone $ordersInRange)->where('status', $status->value)->count(),
        ]);

        $revenuePerMarket = Order::completed()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('market')
            ->get()
            ->groupBy(fn (Order $order) => $order->market->name)
            ->map(fn (Collection $ordersAtMarket, string $marketName) => [
                'label' => $marketName,
                'value' => (float) $ordersAtMarket->sum('total_amount'),
            ])
            ->sortByDesc('value')
            ->values();

        $mostActiveFarmers = FarmerProfile::withCount(['orders as orders_in_range_count' => fn ($query) => $query
            ->whereBetween('created_at', [$startDate, $endDate])])
            ->withSum(['orders as revenue_in_range' => fn ($query) => $query
                ->completed()
                ->whereBetween('created_at', [$startDate, $endDate])], 'total_amount')
            ->orderByDesc('orders_in_range_count')
            ->take(10)
            ->get();

        $newCustomersPerWeek = $this->weeklyNewCustomers($startDate, $endDate);

        return compact('summary', 'ordersByStatus', 'revenuePerMarket', 'mostActiveFarmers', 'newCustomersPerWeek');
    }

    private function weeklyNewCustomers(Carbon $startDate, Carbon $endDate): Collection
    {
        $weeks = collect();
        $weekStart = $startDate->copy()->startOfWeek();

        while ($weekStart->lte($endDate)) {
            $weekEnd = $weekStart->copy()->endOfWeek();

            $weeks->push([
                'label' => $weekStart->format('j M'),
                'value' => User::where('role', UserRole::Customer->value)
                    ->whereBetween('created_at', [$weekStart, $weekEnd])
                    ->count(),
            ]);

            $weekStart = $weekStart->copy()->addWeek();
        }

        return $weeks;
    }
}
