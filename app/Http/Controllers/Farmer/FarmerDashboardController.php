<?php

namespace App\Http\Controllers\Farmer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmerDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $farmer = $request->user()->farmerProfile;

        abort_if(! $farmer, 403, 'Your farmer profile is missing. Please contact the admin.');

        $farmerStats = [
            'total_orders' => $farmer->orders()->count(),
            'pending_orders' => $farmer->orders()->where('status', OrderStatus::Placed->value)->count(),
            'revenue' => $farmer->orders()->completed()->sum('total_amount'),
            'low_stock_products' => $farmer->products()
                ->where('stock_quantity', '<=', config('marketlink.low_stock_threshold'))
                ->count(),
        ];

        // accept / decline ke intezar mein naye orders, pehle wala pickup upar
        $ordersWaitingForReply = $farmer->orders()
            ->where('status', OrderStatus::Placed->value)
            ->with(['customer', 'market'])
            ->withCount('items')
            ->orderBy('pickup_date')
            ->take(5)
            ->get();

        // chalte hue urgent pickups, jo pehle due hai wo upar
        $openUrgentOrders = $farmer->orders()
            ->urgent()
            ->open()
            ->with(['customer', 'market', 'items'])
            ->orderBy('urgent_pickup_at')
            ->get();

        $bestSellingProducts = $farmer->bestSellingProducts(5);

        $announcements = Announcement::visibleTo('farmers')->latest('published_at')->take(2)->get();

        return view('farmer.dashboard', compact(
            'farmer',
            'farmerStats',
            'ordersWaitingForReply',
            'openUrgentOrders',
            'bestSellingProducts',
            'announcements',
        ));
    }
}
