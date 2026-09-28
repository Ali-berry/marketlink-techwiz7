<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FarmerApprovalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SuspendFarmerRequest;
use App\Models\FarmerProfile;
use App\Services\FarmerApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmerController extends Controller
{
    // index page ka har tab kaunsa status dikhata hai
    private const STATUS_PER_TAB = [
        'pending' => FarmerApprovalStatus::Pending,
        'approved' => FarmerApprovalStatus::Approved,
        'suspended' => FarmerApprovalStatus::Suspended,
    ];

    public function index(Request $request): View
    {
        $activeTab = array_key_exists($request->query('tab'), self::STATUS_PER_TAB) ? $request->query('tab') : 'pending';

        $farmers = FarmerProfile::query()
            ->where('approval_status', self::STATUS_PER_TAB[$activeTab]->value)
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($nameQuery) => $nameQuery
                ->where('stall_name', 'like', '%'.$request->string('search').'%')
                ->orWhere('contact_person', 'like', '%'.$request->string('search').'%')))
            ->withCount('products')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $tabCounts = [
            'pending' => FarmerProfile::pending()->count(),
            'approved' => FarmerProfile::approved()->count(),
            'suspended' => FarmerProfile::suspended()->count(),
        ];

        return view('admin.farmers.index', compact('farmers', 'activeTab', 'tabCounts'));
    }

    public function show(FarmerProfile $farmer): View
    {
        $farmer->load(['user', 'markets']);

        $orderStats = [
            'total_orders' => $farmer->orders()->count(),
            'completed_orders' => $farmer->orders()->completed()->count(),
            'revenue' => $farmer->orders()->completed()->sum('total_amount'),
        ];

        $products = $farmer->products()->with('category')->paginate(10, ['*'], 'products_page');

        return view('admin.farmers.show', compact('farmer', 'orderStats', 'products'));
    }

    // pending ko approve aur suspended ko dobara approve, dono yahi
    public function approve(FarmerProfile $farmer, FarmerApprovalService $approvalService): RedirectResponse
    {
        $approvalService->approve($farmer);

        return back()->with('success', $farmer->stall_name.' is now approved.');
    }

    public function suspend(SuspendFarmerRequest $request, FarmerProfile $farmer, FarmerApprovalService $approvalService): RedirectResponse
    {
        $approvalService->suspend($farmer, $request->validated()['reason']);

        return back()->with('success', $farmer->stall_name.' was suspended. Their products are now hidden from customers.');
    }
}
