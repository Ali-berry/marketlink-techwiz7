<x-layouts.panel title="Pickup slots">

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold">Pickup slots</h2>
            <p class="mt-1 text-sm text-soil-muted">These are the times customers can choose when they pre-order from you.</p>
        </div>
        <a href="{{ route('farmer.pickup-windows.create') }}" class="btn-primary">
            <iconify-icon icon="tabler:plus"></iconify-icon>
            Add pickup slot
        </a>
    </div>

    @if ($pickupWindowsByMarket->isEmpty())
        <div class="card">
            <x-ui.empty-state icon="tabler:clock-hour-4" title="No pickup slots yet"
                               message="Add a slot so customers know when to collect their order.">
                <a href="{{ route('farmer.pickup-windows.create') }}" class="btn-primary">Add pickup slot</a>
            </x-ui.empty-state>
        </div>
    @else
        <div class="space-y-6">
            @foreach ($pickupWindowsByMarket as $marketName => $pickupWindows)
                <div class="card">
                    <h3 class="font-display text-lg font-semibold">{{ $marketName }}</h3>

                    <div class="mt-4 overflow-x-auto">
                        <table class="table-clean">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Max orders</th>
                                    <th>Upcoming orders</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pickupWindows as $pickupWindow)
                                    <tr>
                                        <td>{{ $pickupWindow->dayName() }}</td>
                                        <td class="text-soil-muted">{{ $pickupWindow->timeRangeText() }}</td>
                                        <td class="text-soil-muted">{{ $pickupWindow->max_orders }}</td>
                                        <td class="text-soil-muted">{{ $pickupWindow->upcoming_orders_count }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('farmer.pickup-windows.toggle', $pickupWindow) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit"
                                                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ $pickupWindow->is_active ? 'bg-leaf-50 text-leaf-800' : 'bg-gray-100 text-gray-700' }}">
                                                    {{ $pickupWindow->is_active ? 'Active' : 'Turned off' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="flex items-center justify-end gap-4">
                                                <a href="{{ route('farmer.pickup-windows.edit', $pickupWindow) }}" class="text-sm font-medium text-leaf-600 hover:text-leaf-800">Edit</a>

                                                <x-ui.confirm-dialog title="Delete this pickup slot?"
                                                                      body="Customers won't be able to book this slot any more."
                                                                      :action="route('farmer.pickup-windows.destroy', $pickupWindow)" method="DELETE" confirm-label="Delete slot">
                                                    <x-slot:trigger>
                                                        <span class="cursor-pointer text-sm font-medium text-red-600 hover:text-red-800">Delete</span>
                                                    </x-slot:trigger>
                                                </x-ui.confirm-dialog>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</x-layouts.panel>
