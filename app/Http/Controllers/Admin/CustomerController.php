<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = User::where('role', UserRole::Customer->value)
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($nameQuery) => $nameQuery
                ->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('email', 'like', '%'.$request->string('search').'%')))
            ->withCount('ordersAsCustomer')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $customer): View
    {
        abort_unless($customer->role === UserRole::Customer, 404);

        $orders = $customer->ordersAsCustomer()->with(['farmer', 'market'])->latest()->paginate(10);

        return view('admin.customers.show', compact('customer', 'orders'));
    }

    // "active" middleware customer ko agle page load pe logout kar deta hai
    public function toggleActive(User $customer): RedirectResponse
    {
        abort_unless($customer->role === UserRole::Customer, 404);

        $customer->update(['is_active' => ! $customer->is_active]);

        return back()->with('success', $customer->name.' is now '.($customer->is_active ? 'active' : 'deactivated').'.');
    }
}
