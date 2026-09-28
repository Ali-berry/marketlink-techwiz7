<?php

namespace App\Services;

use App\Models\FarmerProfile;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

// Home page ke "Top farmers this week" - pichle 7 din ke completed orders, tie pe rating, phir naya stall.
// Is hafte koi order na ho to all-time orders. Hafte ki key pe cache (home-top-farmers-2026-W39),
// agle hafte khud naya ban jata hai - scheduler nahi chahiye
class TopFarmersThisWeek
{
    private const HOW_MANY_TO_SHOW = 3;
    private const RECENT_ORDER_DAYS = 7;
    private const NEW_STALL_DAYS = 30;

    // har entry: farmer, completedOrders, averageRating, ordersLabel, badge, isNewStall
    public function forHomePage(): Collection
    {
        $weekCacheKey = 'home-top-farmers-'.now()->format('o-\WW');

        // cache mein sirf ids aur numbers - farmers fresh load hote hain taake suspended stall foran hat jaye
        $rankedFarmerStats = Cache::remember($weekCacheKey, now()->endOfWeek(), fn () => $this->rankFarmers());

        $approvedFarmersById = FarmerProfile::approved()
            ->whereIn('id', array_column($rankedFarmerStats, 'farmer_id'))
            ->get()
            ->keyBy('id');

        return collect($rankedFarmerStats)
            ->filter(fn (array $farmerStats) => $approvedFarmersById->has($farmerStats['farmer_id']))
            ->map(function (array $farmerStats) use ($approvedFarmersById) {
                $farmer = $approvedFarmersById[$farmerStats['farmer_id']];

                return [
                    'farmer' => $farmer,
                    'completedOrders' => $farmerStats['completed_orders'],
                    'averageRating' => $farmerStats['average_rating'],
                    'ordersLabel' => $this->ordersLabel($farmerStats['completed_orders'], $farmerStats['counted_this_week_only']),
                    'badge' => $farmerStats['badge'],
                    'isNewStall' => $farmer->created_at->gt(now()->subDays(self::NEW_STALL_DAYS)),
                ];
            })
            ->values();
    }

    private function rankFarmers(): array
    {
        $recentOrdersFrom = now()->subDays(self::RECENT_ORDER_DAYS);

        $anyoneCompletedOrdersThisWeek = Order::completed()
            ->where('completed_at', '>=', $recentOrdersFrom)
            ->whereHas('farmer', fn ($farmerQuery) => $farmerQuery->approved())
            ->exists();

        $candidateFarmers = FarmerProfile::approved()
            ->withCount(['orders as completed_orders_count' => fn ($orderQuery) => $orderQuery
                ->completed()
                ->when($anyoneCompletedOrdersThisWeek, fn ($recentQuery) => $recentQuery->where('completed_at', '>=', $recentOrdersFrom))])
            ->withAvg(['reviews as average_rating' => fn ($reviewQuery) => $reviewQuery->where('is_hidden_by_admin', false)], 'rating')
            ->get();

        $mostCompletedOrders = $candidateFarmers->max('completed_orders_count');

        return $candidateFarmers
            // zyada orders pehle, rating sirf tie todti hai, phir naya approved stall
            ->sort(fn (FarmerProfile $first, FarmerProfile $second) => $this->rankingKeyFor($second) <=> $this->rankingKeyFor($first))
            ->take(self::HOW_MANY_TO_SHOW)
            ->map(fn (FarmerProfile $farmer) => [
                'farmer_id' => $farmer->id,
                'completed_orders' => $farmer->completed_orders_count,
                'average_rating' => $farmer->average_rating ? round((float) $farmer->average_rating, 1) : null,
                'counted_this_week_only' => $anyoneCompletedOrdersThisWeek,
                'badge' => $this->strongestBadge($farmer, $mostCompletedOrders),
            ])
            ->values()
            ->all();
    }

    private function rankingKeyFor(FarmerProfile $farmer): array
    {
        return [$farmer->completed_orders_count, (float) $farmer->average_rating, $farmer->approved_at];
    }

    // jis cheez mein farmer behtar hai wo badge - orders (sab se busy stall ke muqable) ya rating
    private function strongestBadge(FarmerProfile $farmer, ?int $mostCompletedOrders): ?string
    {
        if ($farmer->completed_orders_count === 0 && ! $farmer->average_rating) {
            return null;
        }

        $orderStrength = $mostCompletedOrders ? $farmer->completed_orders_count / $mostCompletedOrders : 0;
        $ratingStrength = $farmer->average_rating ? $farmer->average_rating / 5 : 0;

        return $orderStrength >= $ratingStrength ? 'Most orders' : 'Top rated';
    }

    // orders na hon to null - card sirf rating dikhata hai, "no orders" bura lagta hai
    private function ordersLabel(int $completedOrders, bool $countedThisWeekOnly): ?string
    {
        if ($completedOrders === 0) {
            return null;
        }

        $ordersText = $completedOrders.' '.str('order')->plural($completedOrders);

        return $countedThisWeekOnly ? $ordersText.' this week' : $ordersText;
    }
}
