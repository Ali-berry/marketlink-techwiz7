<x-layouts.panel title="Add pickup slot">

    <div class="mb-6">
        <a href="{{ route('farmer.pickup-windows.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-soil-muted hover:text-soil">
            <iconify-icon icon="tabler:arrow-left"></iconify-icon>
            Back to pickup slots
        </a>
    </div>

    <div class="card max-w-2xl">
        <h2 class="text-lg font-semibold">Add a pickup slot</h2>
        <p class="mt-1 text-sm text-soil-muted">Customers can only pick a pickup time you've set up here.</p>

        @if ($markets->isEmpty())
            <x-ui.empty-state icon="tabler:map-pin-off" title="You're not selling at any market yet"
                               message="Add at least one market on your stall profile before creating pickup slots.">
                <a href="{{ route('farmer.stall.edit') }}" class="btn-primary">Go to stall profile</a>
            </x-ui.empty-state>
        @else
            <form method="POST" action="{{ route('farmer.pickup-windows.store') }}" class="mt-6">
                @csrf
                @include('farmer.pickup-windows._form', ['pickupWindow' => $pickupWindow, 'markets' => $markets])

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="btn-primary">
                        <iconify-icon icon="tabler:check"></iconify-icon>
                        Save pickup slot
                    </button>
                </div>
            </form>
        @endif
    </div>

</x-layouts.panel>
