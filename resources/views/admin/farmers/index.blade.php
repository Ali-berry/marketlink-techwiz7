@php
    $tabLabels = ['pending' => 'Pending', 'approved' => 'Approved', 'suspended' => 'Suspended'];
@endphp

<x-layouts.panel title="Farmers">

    <div class="mb-6">
        <h2 class="text-2xl font-semibold">Farmers</h2>
        <p class="mt-1 text-sm text-soil-muted">Review new sign-ups, and manage who can sell on MarketLink.</p>
    </div>

    <div class="tab-bar">
        @foreach ($tabLabels as $tabKey => $tabLabel)
            <a href="{{ route('admin.farmers.index', ['tab' => $tabKey]) }}"
               @class(['tab-pill', 'is-active' => $activeTab === $tabKey])>
                {{ $tabLabel }} ({{ $tabCounts[$tabKey] }})
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.farmers.index') }}" class="card mb-6 flex flex-wrap items-end gap-4 p-5">
        <input type="hidden" name="tab" value="{{ $activeTab }}">
        <div class="flex-1">
            <label class="form-label" for="search">Search by stall or contact name</label>
            <input id="search" type="text" name="search" value="{{ request('search') }}" class="form-input">
        </div>
        <button type="submit" class="btn-primary">Search</button>
        @if (request()->filled('search'))
            <a href="{{ route('admin.farmers.index', ['tab' => $activeTab]) }}" class="btn-outline">Clear</a>
        @endif
    </form>

    @if ($farmers->isEmpty())
        <div class="card">
            <x-ui.empty-state icon="tabler:tractor" title="No farmers here" />
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($farmers as $farmer)
                <a href="{{ route('admin.farmers.show', $farmer) }}" class="card flex gap-4 hover:border-leaf-200">
                    <img src="{{ $farmer->coverImageUrl() }}" alt="{{ $farmer->stall_name }}" class="h-16 w-16 shrink-0 rounded-xl object-cover object-[center_20%]">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ $farmer->stall_name }}</p>
                        <p class="truncate text-sm text-soil-muted">{{ $farmer->contact_person }}</p>
                        <p class="mt-1 text-xs text-soil-muted">{{ $farmer->products_count }} {{ \Illuminate\Support\Str::plural('product', $farmer->products_count) }}</p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $farmers->links() }}
        </div>
    @endif

</x-layouts.panel>
