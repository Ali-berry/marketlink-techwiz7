<x-layouts.panel title="Add market">

    <div class="mb-6">
        <a href="{{ route('admin.markets.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-soil-muted hover:text-soil">
            <iconify-icon icon="tabler:arrow-left"></iconify-icon>
            Back to markets
        </a>
    </div>

    <div class="card max-w-3xl">
        <h2 class="text-lg font-semibold">Add a market</h2>

        <form method="POST" action="{{ route('admin.markets.store') }}" enctype="multipart/form-data" class="mt-6">
            @csrf
            @include('admin.markets._form', ['market' => $market])

            <div class="mt-6 flex justify-end">
                <button type="submit" class="btn-primary">
                    <iconify-icon icon="tabler:check"></iconify-icon>
                    Save market
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        @vite('resources/js/stall-location-map.js')
    @endpush

</x-layouts.panel>
