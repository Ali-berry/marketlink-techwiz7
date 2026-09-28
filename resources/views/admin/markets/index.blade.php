<x-layouts.panel title="Markets">

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold">Markets</h2>
            <p class="mt-1 text-sm text-soil-muted">Every physical market MarketLink lists.</p>
        </div>
        <a href="{{ route('admin.markets.create') }}" class="btn-primary">
            <iconify-icon icon="tabler:plus"></iconify-icon>
            Add market
        </a>
    </div>

    <form method="GET" action="{{ route('admin.markets.index') }}" class="card mb-6 flex flex-wrap items-end gap-4 p-5">
        <div class="flex-1">
            <label class="form-label" for="search">Search by name</label>
            <input id="search" type="text" name="search" value="{{ request('search') }}" class="form-input">
        </div>
        <button type="submit" class="btn-primary">Search</button>
        @if (request()->filled('search'))
            <a href="{{ route('admin.markets.index') }}" class="btn-outline">Clear</a>
        @endif
    </form>

    @if ($markets->isEmpty())
        <div class="card">
            <x-ui.empty-state icon="tabler:map-pin-off" title="No markets found">
                <a href="{{ route('admin.markets.create') }}" class="btn-primary">Add market</a>
            </x-ui.empty-state>
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($markets as $market)
                <div class="card overflow-hidden p-0">
                    <div class="relative">
                        <img src="{{ $market->coverImageUrl() }}" alt="{{ $market->name }}" class="h-36 w-full object-cover">
                        <span class="absolute left-3 top-3 rounded-full px-3 py-1 text-xs font-medium {{ $market->is_active ? 'bg-leaf-50 text-leaf-800' : 'bg-gray-100 text-gray-700' }}">
                            {{ $market->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="p-4">
                        <p class="font-medium">{{ $market->name }}</p>
                        <p class="mt-1 text-sm text-soil-muted">{{ $market->operatingDaysText() }}</p>
                        <p class="mt-1 text-xs text-soil-muted">{{ $market->orders_count }} {{ \Illuminate\Support\Str::plural('order', $market->orders_count) }}</p>

                        <div class="mt-4 flex items-center justify-between border-t border-cream-dark pt-4">
                            <a href="{{ route('admin.markets.edit', $market) }}" class="text-sm font-medium text-leaf-600 hover:text-leaf-800">Edit</a>

                            <form method="POST" action="{{ route('admin.markets.toggle-active', $market) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-sm font-medium text-soil-muted hover:text-soil">
                                    {{ $market->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $markets->links() }}
        </div>
    @endif

</x-layouts.panel>
