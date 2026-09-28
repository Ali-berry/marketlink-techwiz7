<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Market;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $customer = $request->user();

        $upcomingPickups = $customer->ordersAsCustomer()
            ->open()
            ->whereDate('pickup_date', '>=', today())
            ->with(['farmer', 'market', 'pickupWindow'])
            ->orderBy('pickup_date')
            ->take(3)
            ->get();

        $customerStats = [
            'open_orders' => $customer->ordersAsCustomer()->open()->count(),
            'completed_orders' => $customer->ordersAsCustomer()->completed()->count(),
            'favourite_farmers' => $customer->favouriteFarmers()->count(),
            'total_spent' => $customer->ordersAsCustomer()->completed()->sum('total_amount'),
        ];

        $marketsToExplore = Market::active()
            ->withCount(['farmers as approved_farmers_count' => fn ($farmerQuery) => $farmerQuery->approved()])
            ->orderByDesc('approved_farmers_count')
            ->take(3)
            ->get();

        $recentOrders = $customer->ordersAsCustomer()
            ->with('farmer')
            ->latest()
            ->take(5)
            ->get();

        $announcements = Announcement::visibleTo('customers')->latest('published_at')->take(2)->get();

        return view('customer.dashboard', compact(
            'customer',
            'upcomingPickups',
            'customerStats',
            'marketsToExplore',
            'recentOrders',
            'announcements',
        ));
    }
}
