<x-layouts.panel title="Customers">

    <div class="mb-6">
        <h2 class="text-2xl font-semibold">Customers</h2>
        <p class="mt-1 text-sm text-soil-muted">Everyone who has registered to buy on MarketLink.</p>
    </div>

    <form method="GET" action="{{ route('admin.customers.index') }}" class="card mb-6 flex flex-wrap items-end gap-4 p-5">
        <div class="flex-1">
            <label class="form-label" for="search">Search by name or email</label>
            <input id="search" type="text" name="search" value="{{ request('search') }}" class="form-input">
        </div>
        <button type="submit" class="btn-primary">Search</button>
        @if (request()->filled('search'))
            <a href="{{ route('admin.customers.index') }}" class="btn-outline">Clear</a>
        @endif
    </form>

    @if ($customers->isEmpty())
        <div class="card">
            <x-ui.empty-state icon="tabler:users" title="No customers found" />
        </div>
    @else
        <div class="card overflow-x-auto">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Orders</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr>
                            <td class="font-medium">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="hover:text-leaf-700">{{ $customer->name }}</a>
                            </td>
                            <td class="text-soil-muted">{{ $customer->email }}</td>
                            <td class="text-soil-muted">{{ $customer->orders_as_customer_count }}</td>
                            <td>
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ $customer->is_active ? 'bg-leaf-50 text-leaf-800' : 'bg-red-50 text-red-700' }}">
                                    {{ $customer->is_active ? 'Active' : 'Deactivated' }}
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.customers.toggle-active', $customer) }}">
                                    @csrf
                                    <button type="submit" class="text-sm font-medium {{ $customer->is_active ? 'text-red-600 hover:text-red-800' : 'text-leaf-600 hover:text-leaf-800' }}">
                                        {{ $customer->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $customers->links() }}</div>
    @endif

</x-layouts.panel>
