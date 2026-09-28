<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FarmerApprovalStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\FarmerProfile;
use App\Models\Market;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

// har block ek permission ka hai aur sirf usi admin ke liye banta hai - Support Admin ko revenue page mein chhupa ke bhi nahi jata.
//   manage-farmers   -> farmer count, approval wale farmers, most active farmers
//   manage-customers -> customer count, latest orders
//   manage-markets   -> market count
//   view-reports     -> order count, status wise orders, revenue
class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $admin = $request->user();

        $canSee = [
            'farmers' => $admin->can('manage-farmers'),
            'customers' => $admin->can('manage-customers'),
            'markets' => $admin->can('manage-markets'),
            'reports' => $admin->can('view-reports'),
        ];

        $platformStats = array_filter([
            'farmers' => $canSee['farmers'] ? FarmerProfile::count() : null,
            'customers' => $canSee['customers'] ? User::where('role', UserRole::Customer->value)->count() : null,
            'markets' => $canSee['markets'] ? Market::count() : null,
            'orders' => $canSee['reports'] ? Order::count() : null,
            'revenue' => $canSee['reports'] ? Order::completed()->sum('total_amount') : null,
        ], fn ($statValue) => $statValue !== null);

        $ordersPerStatus = $canSee['reports']
            ? collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $status) => [
                $status->label() => Order::where('status', $status->value)->count(),
            ])
            : collect();

        $farmersWaitingForApproval = $canSee['farmers']
            ? FarmerProfile::where('approval_status', FarmerApprovalStatus::Pending->value)->with('user')->oldest()->take(5)->get()
            : collect();

        $mostActiveFarmers = $canSee['farmers']
            ? FarmerProfile::approved()
                ->withCount(['orders as completed_orders_count' => fn ($orderQuery) => $orderQuery->completed()])
                ->when($canSee['reports'], fn ($query) => $query->withSum(
                    ['orders as completed_revenue' => fn ($orderQuery) => $orderQuery->completed()],
                    'total_amount'
                ))
                ->orderByDesc('completed_orders_count')
                ->take(5)
                ->get()
            : collect();

        $latestOrders = $canSee['customers'] ? Order::with(['customer', 'farmer'])->latest()->take(6)->get() : collect();

        return view('admin.dashboard', compact(
            'canSee',
            'platformStats',
            'ordersPerStatus',
            'farmersWaitingForApproval',
            'mostActiveFarmers',
            'latestOrders',
        ));
    }
}
