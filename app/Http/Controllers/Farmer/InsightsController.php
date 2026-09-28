<?php

namespace App\Http\Controllers\Farmer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\FarmerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class InsightsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $farmer = $request->user()->farmerProfile;

        abort_if(! $farmer, 403, 'Your farmer profile is missing. Please contact the admin.');

        $summary = [
            'total_orders' => $farmer->orders()->count(),
            'pending_orders' => $farmer->orders()->where('status', OrderStatus::Placed->value)->count(),
            'revenue' => $farmer->orders()->completed()->sum('total_amount'),
        ];

        $weeklyRevenue = $this->revenuePerWeek($farmer, weeks: 8);
        $bestSellingProducts = $farmer->bestSellingProducts(10);

        $revenuePerMarket = $farmer->orders()
            ->completed()
            ->with('market')
            ->get()
            ->groupBy(fn ($order) => $order->market->name)
            ->map(fn ($ordersAtMarket) => $ordersAtMarket->sum('total_amount'))
            ->sortByDesc(fn ($total) => $total);

        return view('farmer.insights.index', [
            'summary' => $summary,
            'weeklyRevenue' => $weeklyRevenue,
            'bestSellingProducts' => $bestSellingProducts,
            'revenuePerMarket' => $revenuePerMarket,
        ]);
    }

    // har hafte ki ek entry, purani pehle taake chart left se right parha jaye
    private function revenuePerWeek(FarmerProfile $farmer, int $weeks): Collection
    {
        return collect(range($weeks - 1, 0))->map(function (int $weeksAgo) use ($farmer) {
            $weekStart = now()->subWeeks($weeksAgo)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();

            return [
                'label' => $weekStart->format('j M'),
                'total' => (float) $farmer->orders()
                    ->completed()
                    ->whereBetween('completed_at', [$weekStart, $weekEnd])
                    ->sum('total_amount'),
            ];
        })->values();
    }
}
