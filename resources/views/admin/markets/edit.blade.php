<x-layouts.panel title="Edit market">

    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('admin.markets.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-soil-muted hover:text-soil">
            <iconify-icon icon="tabler:arrow-left"></iconify-icon>
            Back to markets
        </a>

        @if ($market->orders()->exists())
            <span class="text-xs text-soil-muted">Has orders - use the active toggle instead of deleting.</span>
        @else
            <x-ui.confirm-dialog title="Delete this market?"
                                  body="{{ $market->name }} will be permanently removed."
                                  :action="route('admin.markets.destroy', $market)" method="DELETE" confirm-label="Delete market">
                <x-slot:trigger>
                    <span class="btn-danger cursor-pointer">
                        <iconify-icon icon="tabler:trash"></iconify-icon>
                        Delete
                    </span>
                </x-slot:trigger>
            </x-ui.confirm-dialog>
        @endif
    </div>

    <div class="card max-w-3xl">
        <h2 class="text-lg font-semibold">{{ $market->name }}</h2>

        <form method="POST" action="{{ route('admin.markets.update', $market) }}" enctype="multipart/form-data" class="mt-6">
            @csrf
            @method('PUT')
            @include('admin.markets._form', ['market' => $market])

            <div class="mt-6 flex justify-end">
                <button type="submit" class="btn-primary">
                    <iconify-icon icon="tabler:check"></iconify-icon>
                    Save changes
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        @vite('resources/js/stall-location-map.js')
    @endpush

</x-layouts.panel>
