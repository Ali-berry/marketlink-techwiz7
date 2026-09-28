<x-layouts.panel title="Edit pickup slot">

    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('farmer.pickup-windows.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-soil-muted hover:text-soil">
            <iconify-icon icon="tabler:arrow-left"></iconify-icon>
            Back to pickup slots
        </a>

        <x-ui.confirm-dialog title="Delete this pickup slot?"
                              body="Customers won't be able to book this slot any more. Orders already booked into it are kept, just without a slot attached."
                              :action="route('farmer.pickup-windows.destroy', $pickupWindow)" method="DELETE" confirm-label="Delete slot">
            <x-slot:trigger>
                <span class="btn-danger cursor-pointer">
                    <iconify-icon icon="tabler:trash"></iconify-icon>
                    Delete
                </span>
            </x-slot:trigger>
        </x-ui.confirm-dialog>
    </div>

    <div class="card max-w-2xl">
        <h2 class="text-lg font-semibold">{{ $pickupWindow->market->name }} - {{ $pickupWindow->dayName() }}</h2>
        <p class="mt-1 text-sm text-soil-muted">Update the time or size of this pickup slot.</p>

        <form method="POST" action="{{ route('farmer.pickup-windows.update', $pickupWindow) }}" class="mt-6">
            @csrf
            @method('PUT')
            @include('farmer.pickup-windows._form', ['pickupWindow' => $pickupWindow, 'markets' => $markets])

            <div class="mt-6 flex justify-end">
                <button type="submit" class="btn-primary">
                    <iconify-icon icon="tabler:check"></iconify-icon>
                    Save changes
                </button>
            </div>
        </form>
    </div>

</x-layouts.panel>
