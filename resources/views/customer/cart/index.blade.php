<x-layouts.panel title="My basket">

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold">My basket</h2>
            <p class="mt-1 text-sm text-soil-muted">Grouped by farmer - you check out with each farmer separately.</p>
        </div>
        <span class="inline-flex items-center gap-2 rounded-full border border-white/70 bg-white/60 px-4 py-2 text-sm text-soil shadow-sm">
            <iconify-icon icon="tabler:cash" class="text-leaf-600"></iconify-icon>
            You pay each farmer at pickup
        </span>
    </div>

    @if ($basketGroups->isEmpty())
        <div class="card">
            <x-ui.empty-state icon="tabler:shopping-bag" title="Your basket is empty"
                               message="Browse products and add a few to get started.">
                <a href="{{ route('customer.products.index') }}" class="btn-primary">Browse products</a>
            </x-ui.empty-state>
        </div>
    @else
        <div class="space-y-6">
            @foreach ($basketGroups as $group)
                <section class="card p-5">
                    <a href="{{ route('customer.farmers.show', $group['farmer']) }}" class="flex items-center gap-3 border-b border-white/60 pb-4 hover:text-leaf-700">
                        <img src="{{ $group['farmer']->coverImageUrl() }}" alt="{{ $group['farmer']->stall_name }}" class="h-11 w-11 rounded-full border-2 border-white/70 object-cover object-[center_20%]">
                        <span>
                            <span class="block font-medium">{{ $group['farmer']->stall_name }}</span>
                            <span class="block text-xs text-soil-muted">{{ $group['items']->count() }} {{ \Illuminate\Support\Str::plural('item', $group['items']->count()) }}</span>
                        </span>
                    </a>

                    <div class="divide-y divide-white/60">
                        @foreach ($group['items'] as $line)
                            @php $product = $line['product']; @endphp
                            <div class="flex flex-wrap items-center gap-4 py-4">
                                <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="h-16 w-16 rounded-xl object-cover">

                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('customer.products.show', $product) }}" class="font-medium hover:text-leaf-700">{{ $product->name }}</a>
                                    <p class="text-sm text-soil-muted"><x-ui.money :amount="$product->price" /> / {{ $product->unit }}</p>

                                    @if ($line['quantity'] > $product->stock_quantity)
                                        <p class="mt-1 text-xs text-tomato-700">Only {{ $product->stock_quantity }} left - lower the quantity.</p>
                                    @endif
                                </div>

                                {{-- har click foran save hota hai, number likhne pe field chhodte hi save --}}
                                <form method="POST" action="{{ route('customer.cart.update', $product) }}"
                                      x-data="{ quantity: {{ $line['quantity'] }}, maxQuantity: {{ max($product->stock_quantity, 1) }} }"
                                      class="flex items-center rounded-full border border-white/70 bg-white/70 p-1">
                                    @csrf
                                    @method('PATCH')
                                    <button type="button" :disabled="quantity <= 1"
                                            @click="quantity--; $nextTick(() => $el.form.submit())"
                                            class="flex h-8 w-8 items-center justify-center rounded-full text-soil transition hover:bg-white disabled:opacity-40"
                                            aria-label="One less {{ $product->name }}">
                                        <iconify-icon icon="tabler:minus"></iconify-icon>
                                    </button>
                                    <input type="number" name="quantity" x-model.number="quantity" min="1" :max="maxQuantity"
                                           @change="$el.form.submit()"
                                           class="w-12 border-0 bg-transparent p-0 text-center text-sm font-semibold text-soil focus:ring-0"
                                           aria-label="Quantity of {{ $product->name }}">
                                    <button type="button" :disabled="quantity >= maxQuantity"
                                            @click="quantity++; $nextTick(() => $el.form.submit())"
                                            class="flex h-8 w-8 items-center justify-center rounded-full text-soil transition hover:bg-white disabled:opacity-40"
                                            aria-label="One more {{ $product->name }}">
                                        <iconify-icon icon="tabler:plus"></iconify-icon>
                                    </button>
                                </form>

                                <p class="w-20 text-right font-medium"><x-ui.money :amount="$line['lineTotal']" /></p>

                                <form method="POST" action="{{ route('customer.cart.destroy', $product) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-full p-2 text-soil-muted transition hover:bg-white/70 hover:text-red-600" aria-label="Remove {{ $product->name }}">
                                        <iconify-icon icon="tabler:trash" class="text-lg"></iconify-icon>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-white/60 pt-4">
                        <p class="text-sm text-soil-muted">
                            Subtotal <x-ui.money :amount="$group['subtotal']" class="ml-1 font-display text-xl font-semibold text-tomato-600" />
                        </p>
                        <a href="{{ route('customer.checkout.show', $group['farmer']) }}" class="btn-glass-orange">
                            Checkout this farmer
                            <iconify-icon icon="tabler:arrow-right"></iconify-icon>
                        </a>
                    </div>
                </section>
            @endforeach
        </div>
    @endif

</x-layouts.panel>
