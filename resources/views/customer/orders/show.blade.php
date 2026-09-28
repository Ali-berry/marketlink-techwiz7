<x-layouts.panel title="Order {{ $order->order_number }}">

    <div class="mb-6">
        <a href="{{ route('customer.orders.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-soil-muted hover:text-soil">
            <iconify-icon icon="tabler:arrow-left"></iconify-icon>
            Back to my orders
        </a>
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-sm text-soil-muted">Order from {{ $order->farmer->stall_name }}</p>
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

                @if ($canModify)
                    <form method="PUT" action="{{ route('customer.orders.update-quantities', $order) }}" class="mt-4">
                        @csrf
                        @method('PUT')
                        <div class="space-y-3">
                            @foreach ($order->items as $item)
                                @php $maxQuantity = $item->product ? $item->product->stock_quantity + $item->quantity : $item->quantity; @endphp
                                <div class="flex flex-wrap items-center gap-4">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium">{{ $item->product_name }}</p>
                                        <p class="text-sm text-soil-muted"><x-ui.money :amount="$item->unit_price" /> / {{ $item->unit }}</p>
                                    </div>
                                    <input type="number" name="quantities[{{ $item->id }}]" value="{{ old('quantities.'.$item->id, $item->quantity) }}"
                                           min="1" max="{{ max($maxQuantity, 1) }}" class="form-input w-24">
                                    <p class="w-24 text-right font-medium"><x-ui.money :amount="$item->line_total" /></p>
                                </div>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('quantities')" class="mt-2" />

                        <div class="mt-4 flex justify-end">
                            <button type="submit" class="btn-outline">
                                <iconify-icon icon="tabler:refresh"></iconify-icon>
                                Save quantity changes
                            </button>
                        </div>
                    </form>
                @else
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
                @endif

                <div class="mt-4 flex justify-end border-t border-cream-dark pt-4 text-sm text-soil-muted">
                    Total: <x-ui.money :amount="$order->total_amount" class="ml-1 font-semibold text-soil" />
                </div>
            </section>

            @if ($order->customer_note)
                <section class="card">
                    <h3 class="font-semibold">Your note</h3>
                    <p class="mt-2 text-sm text-soil-muted">{{ $order->customer_note }}</p>
                </section>
            @endif

            @if ($order->decline_reason)
                <section class="card border-red-100 bg-red-50">
                    <h3 class="font-semibold text-red-800">Why it was declined</h3>
                    <p class="mt-2 text-sm text-red-700">{{ $order->decline_reason }}</p>
                </section>
            @endif

            <section class="card">
                <h3 class="font-semibold">Timeline</h3>
                <x-ui.order-timeline :status-changes="$order->statusChanges" :placed-via-ai-chat="$order->wasPlacedViaAiChat()" />
            </section>
        </div>

        <div class="space-y-6">
            @if ($order->is_urgent)
                <section class="card border-red-100">
                    <div class="flex items-center gap-3">
                        <span class="icon-chip h-10 w-10 bg-red-50 text-red-600">
                            <iconify-icon icon="tabler:clock-bolt"></iconify-icon>
                        </span>
                        <h3 class="font-semibold">Urgent pickup by {{ $order->urgentPickupTimeText() }} {{ $order->market->timezoneLabel() }}</h3>
                    </div>
                    <p class="mt-3 text-sm">Collect it at {{ $order->farmer->stall_name }}'s own stall, not the market:</p>
                    <p class="text-sm text-soil-muted">{{ $order->farmer->address }}</p>
                    @if ($order->status === \App\Enums\OrderStatus::Placed)
                        <p class="mt-2 text-sm text-soil-muted">Waiting for the farmer to confirm.</p>
                    @elseif (in_array($order->status, [\App\Enums\OrderStatus::Accepted, \App\Enums\OrderStatus::ReadyForPickup], true))
                        <p class="mt-2 text-sm text-soil-muted">Confirmed - the farmer knows you're coming.</p>
                    @endif
                    @if ($order->farmer->hasMapLocation())
                        <a href="{{ $order->farmer->directionsUrl() }}" target="_blank" rel="noopener" class="btn-outline mt-4 w-full">
                            <iconify-icon icon="tabler:map-2"></iconify-icon>
                            Get directions
                        </a>
                    @endif
                </section>
            @else
                <section class="card">
                    <h3 class="font-semibold">Pickup</h3>
                    <p class="mt-2 text-sm">{{ $order->pickup_date->format('l, j M Y') }}</p>
                    <p class="text-sm text-soil-muted">{{ $order->market->name }}</p>
                    @if ($order->pickupWindow)
                        <p class="text-sm text-soil-muted">{{ $order->pickupWindow->timeRangeText() }} {{ $order->market->timezoneLabel() }}</p>
                    @endif
                    <a href="{{ $order->market->directionsUrl() }}" target="_blank" rel="noopener" class="btn-outline mt-4 w-full">
                        <iconify-icon icon="tabler:map-2"></iconify-icon>
                        Get directions
                    </a>
                </section>
            @endif

            <section class="card space-y-3">
                <h3 class="font-semibold">Actions</h3>

                @if ($canModify && $order->is_urgent)
                    <p class="text-sm text-soil-muted">You can cancel an urgent order for {{ \App\Models\Order::URGENT_CANCEL_GRACE_MINUTES }} minutes after placing it.</p>
                @endif

                @if ($canModify)
                    <x-ui.confirm-dialog title="Cancel this order?"
                                          body="Your reserved stock will be released back to the farmer."
                                          :action="route('customer.orders.cancel', $order)" confirm-label="Cancel order">
                        <x-slot:trigger>
                            <span class="btn-outline w-full cursor-pointer">Cancel order</span>
                        </x-slot:trigger>
                    </x-ui.confirm-dialog>
                @else
                    <div class="rounded-xl bg-cream px-3 py-2.5 text-sm text-soil-muted">
                        @switch ($order->status)
                            @case (\App\Enums\OrderStatus::Completed)
                                <p>This order is completed. Thanks for shopping local!</p>
                                @unless ($hasReviewedOrder)
                                    <a href="{{ route('customer.reviews.index') }}" class="link-orange mt-1.5">
                                        <iconify-icon icon="tabler:star"></iconify-icon>
                                        Leave a review
                                    </a>
                                @endunless
                                @break
                            @case (\App\Enums\OrderStatus::Declined)
                                <p>The farmer declined this order.</p>
                                @if ($order->decline_reason)
                                    <p class="mt-1">Reason: {{ $order->decline_reason }}</p>
                                @endif
                                @break
                            @case (\App\Enums\OrderStatus::Cancelled)
                                <p>This order was cancelled.</p>
                                @break
                            @default
                                <p>{{ $order->is_urgent
                                    ? 'The '.\App\Models\Order::URGENT_CANCEL_GRACE_MINUTES.'-minute window to cancel this urgent order has passed - the farmer is getting it ready.'
                                    : "It's too close to pickup time to change or cancel this order." }}</p>
                        @endswitch
                    </div>
                @endif

                {{-- sirf order khatam hone ke baad - OrderController::reorder --}}
                @if ($canReorder)
                    <form method="POST" action="{{ route('customer.orders.reorder', $order) }}">
                        @csrf
                        <button type="submit" class="btn-primary w-full">
                            <iconify-icon icon="tabler:rotate-clockwise"></iconify-icon>
                            Order again
                        </button>
                    </form>
                @endif
            </section>

            <section class="card space-y-3">
                <h3 class="font-semibold">Message {{ $order->farmer->stall_name }}</h3>
                <p class="text-sm text-soil-muted">Have a question about this order? It'll be tagged so they know which one you mean.</p>

                <form method="POST" action="{{ route('customer.farmers.message', $order->farmer) }}">
                    @csrf
                    <input type="hidden" name="related_order_id" value="{{ $order->id }}">
                    <textarea name="body" rows="2" placeholder="Ask about order {{ $order->order_number }}..." class="form-input"></textarea>
                    <x-input-error :messages="$errors->get('body')" class="mt-1.5" />
                    <button type="submit" class="btn-outline mt-3 w-full">
                        <iconify-icon icon="tabler:send-2"></iconify-icon>
                        Send message
                    </button>
                </form>
            </section>
        </div>
    </div>

</x-layouts.panel>
