<x-layouts.panel title="Order {{ $order->order_number }}">

    <div class="mb-6">
        <a href="{{ route('farmer.orders.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-soil-muted hover:text-soil">
            <iconify-icon icon="tabler:arrow-left"></iconify-icon>
            Back to pre-orders
        </a>
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-sm text-soil-muted">Order</p>
            <h2 class="text-2xl font-semibold">{{ $order->order_number }}</h2>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($order->is_urgent)
                <x-ui.urgent-badge :order="$order" />
            @endif
            <x-ui.order-status-badge :status="$order->status" />
        </div>
    </div>

    <div class="grid gap-8 xl:grid-cols-3">

        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <h3 class="font-semibold">Items</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="table-clean">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Unit price</th>
                                <th>Line total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td class="text-soil-muted">{{ $item->quantity }} {{ $item->unit }}</td>
                                    <td class="text-soil-muted"><x-ui.money :amount="$item->unit_price" /></td>
                                    <td class="font-medium"><x-ui.money :amount="$item->line_total" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex justify-end border-t border-cream-dark pt-4 text-sm text-soil-muted">
                    Total: <x-ui.money :amount="$order->total_amount" class="ml-1 font-semibold text-soil" />
                </div>
            </section>

            @if ($order->customer_note)
                <section class="card">
                    <h3 class="font-semibold">Note from customer</h3>
                    <p class="mt-2 text-sm text-soil-muted">{{ $order->customer_note }}</p>
                </section>
            @endif

            @if ($order->decline_reason)
                <section class="card border-red-100 bg-red-50">
                    <h3 class="font-semibold text-red-800">Decline reason</h3>
                    <p class="mt-2 text-sm text-red-700">{{ $order->decline_reason }}</p>
                </section>
            @endif

            <section class="card">
                <h3 class="font-semibold">Timeline</h3>
                <x-ui.order-timeline :status-changes="$order->statusChanges" :show-who-changed="true" :placed-via-ai-chat="$order->wasPlacedViaAiChat()" />
            </section>
        </div>

        <div class="space-y-6">
            <section class="card">
                <h3 class="font-semibold">Customer</h3>
                <p class="mt-2 text-sm">{{ $order->customer->name }}</p>
                <p class="text-sm text-soil-muted">{{ $order->customer->phone ?? 'No phone on file' }}</p>
            </section>

            <section class="card">
                <h3 class="font-semibold">Pickup</h3>
                @if ($order->is_urgent)
                    <p class="mt-2 text-sm font-medium text-red-700">Urgent pickup by {{ $order->urgentPickupTimeText() }} {{ $order->market->timezoneLabel() }}</p>
                    <p class="text-sm text-soil-muted">At your stall: {{ $order->farmer->address }}</p>
                @else
                    <p class="mt-2 text-sm">{{ $order->pickup_date->format('l, j M Y') }}</p>
                    <p class="text-sm text-soil-muted">{{ $order->market->name }}</p>
                    @if ($order->pickupWindow)
                        <p class="text-sm text-soil-muted">{{ $order->pickupWindow->timeRangeText() }} {{ $order->market->timezoneLabel() }}</p>
                    @endif
                @endif
            </section>

            @if ($canAccept || $canDecline || $canMarkReady || $canMarkCompleted)
                <section class="card space-y-3">
                    <h3 class="font-semibold">Actions</h3>

                    @if ($canAccept)
                        <form method="POST" action="{{ route('farmer.orders.accept', $order) }}">
                            @csrf
                            <button type="submit" class="btn-primary w-full">Accept order</button>
                        </form>
                    @endif

                    @if ($canDecline)
                        <x-ui.confirm-dialog title="Decline this order?"
                                              body="The customer will be told it's declined, and their reserved stock goes back on your shelf."
                                              :action="route('farmer.orders.decline', $order)" confirm-label="Decline order">
                            <x-slot:trigger>
                                <span class="btn-outline w-full cursor-pointer">Decline order</span>
                            </x-slot:trigger>
                            <x-slot:fields>
                                <label class="form-label" for="reason">Reason for the customer</label>
                                <textarea id="reason" name="reason" rows="3" class="form-input" required></textarea>
                            </x-slot:fields>
                        </x-ui.confirm-dialog>
                    @endif

                    @if ($canMarkReady)
                        <form method="POST" action="{{ route('farmer.orders.ready', $order) }}">
                            @csrf
                            <button type="submit" class="btn-primary w-full">Mark ready for pickup</button>
                        </form>
                    @endif

                    @if ($canMarkCompleted)
                        @if ($order->pickupDayHasArrived())
                            <form method="POST" action="{{ route('farmer.orders.complete', $order) }}">
                                @csrf
                                <button type="submit" class="btn-primary w-full">Mark completed (paid at stall)</button>
                            </form>
                        @else
                            {{-- OrderStatusUpdater wala rule. Button ke neeche wajah likhi hai kyunki disabled button ka tooltip har jagah nahi dikhta --}}
                            @php $completeFromText = 'You can mark this order completed on or after its pickup day ('.$order->pickup_date->format('D j M').').'; @endphp
                            <span title="{{ $completeFromText }}" class="block">
                                <button type="button" disabled aria-describedby="complete-from-note"
                                        class="btn-primary w-full cursor-not-allowed opacity-50">Mark completed (paid at stall)</button>
                            </span>
                            <p id="complete-from-note" class="text-xs text-soil-muted">{{ $completeFromText }}</p>
                        @endif
                    @endif
                </section>
            @endif
        </div>
    </div>

</x-layouts.panel>
