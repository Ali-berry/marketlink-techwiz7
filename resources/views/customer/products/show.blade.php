@php
    // product page sab dekh sakte hain - customer apne panel mein, guest public site mein
    $layoutComponent = auth()->check() ? 'layouts.panel' : 'layouts.public';

    $isSoldOut = $product->availability === \App\Enums\ProductAvailability::SoldOut || $product->stock_quantity <= 0;
    [$stockPillText, $stockPillClasses] = match (true) {
        $isSoldOut => ['Sold out', 'bg-soil/80 text-white'],
        $product->availability === \App\Enums\ProductAvailability::TemporarilyUnavailable => ['Not this week', 'bg-soil/80 text-white'],
        $product->isRunningLow() => ['Only '.$product->stock_quantity.' left', 'bg-tomato-500 text-white'],
        default => ['Available', 'bg-leaf-600 text-white'],
    };

    $averageRating = $reviews->avg('rating');
    $farmer = $product->farmer;
@endphp

<x-dynamic-component :component="$layoutComponent" :title="$product->name">

    <div @class(['mx-auto max-w-7xl px-4 py-10 sm:px-8' => ! auth()->check()])>
    @unless (auth()->check())
        <x-ui.flash-messages />
    @endunless

    <div class="mb-6">
        <a href="{{ route('customer.products.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-soil-muted hover:text-soil">
            <iconify-icon icon="tabler:arrow-left"></iconify-icon>
            Back to products
        </a>
    </div>

    <div class="grid gap-8 lg:grid-cols-2 lg:items-start">
        {{-- glass photo frame, market aur farmer banners jaisa --}}
        <div class="glass p-2">
            <div class="relative aspect-[4/3] w-full overflow-hidden rounded-2xl">
                <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
            </div>
        </div>

        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-leaf-500/15 px-3 py-1 text-xs font-medium text-leaf-800">{{ $product->category->name }}</span>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $stockPillClasses }}">{{ $stockPillText }}</span>
            </div>

            <h1 class="mt-3 font-display text-3xl font-semibold">{{ $product->name }}</h1>

            @if ($averageRating)
                <div class="mt-2 flex items-center gap-2 text-sm text-soil-muted">
                    <x-ui.star-rating :rating="$averageRating" />
                    {{ number_format($averageRating, 1) }} ({{ $reviews->count() }} {{ \Illuminate\Support\Str::plural('review', $reviews->count()) }})
                </div>
            @endif

            <p class="mt-4 font-display text-4xl font-semibold text-tomato-600">
                <x-ui.money :amount="$product->price" />
                <span class="text-base font-normal text-soil-muted">/ {{ $product->unit }}</span>
            </p>
            @unless ($isSoldOut)
                <p class="mt-1 text-sm text-soil-muted">{{ $product->stock_quantity }} {{ $product->unit }} left in stock</p>
            @endunless

            @if ($product->description)
                <p class="mt-4 text-soil-muted">{{ $product->description }}</p>
            @endif

            <div class="card mt-6 space-y-4 p-5">
                @if ($product->canBeOrdered())
                    <form method="POST" action="{{ route('customer.cart.store', $product) }}"
                          x-data="{ quantity: {{ (int) old('quantity', max($basketQuantity, 1)) }}, maxQuantity: {{ $product->stock_quantity }} }"
                          class="flex flex-wrap items-center gap-3">
                        @csrf
                        {{-- quantity stepper - number field khud bhi type ke liye chalta hai --}}
                        <div class="flex items-center rounded-full border border-white/70 bg-white/70 p-1">
                            <button type="button" @click="quantity = Math.max(1, quantity - 1)" :disabled="quantity <= 1"
                                    class="flex h-9 w-9 items-center justify-center rounded-full text-soil transition hover:bg-white disabled:opacity-40"
                                    aria-label="One less">
                                <iconify-icon icon="tabler:minus"></iconify-icon>
                            </button>
                            <input type="number" name="quantity" x-model.number="quantity" min="1" :max="maxQuantity"
                                   class="w-14 border-0 bg-transparent text-center text-sm font-semibold text-soil focus:ring-0" aria-label="Quantity">
                            <button type="button" @click="quantity = Math.min(maxQuantity, quantity + 1)" :disabled="quantity >= maxQuantity"
                                    class="flex h-9 w-9 items-center justify-center rounded-full text-soil transition hover:bg-white disabled:opacity-40"
                                    aria-label="One more">
                                <iconify-icon icon="tabler:plus"></iconify-icon>
                            </button>
                        </div>
                        <button type="submit" class="btn-glass-orange flex-1">
                            <iconify-icon icon="tabler:shopping-bag-plus"></iconify-icon>
                            Add to basket
                        </button>
                    </form>
                    <x-input-error :messages="$errors->get('quantity')" />

                    {{-- AI chat widget sirf customers ke page pe hota hai --}}
                    @if (auth()->user()?->isCustomer())
                        <button type="button" x-data class="btn-glass-outline w-full"
                                @click="$dispatch('open-agent-chat', { message: @js('I need '.strtolower($product->name).' urgently, within 30 minutes.') })">
                            <iconify-icon icon="tabler:clock-bolt"></iconify-icon>
                            Need it within the hour?
                        </button>
                    @endif
                @else
                    <p class="rounded-xl bg-white/60 px-4 py-2.5 text-sm text-soil-muted">This product isn't available to order right now.</p>
                @endif

                <div class="flex flex-wrap items-center gap-3 border-t border-white/60 pt-4">
                    <x-ui.toggle-button :action="route('customer.favourites.products.toggle', $product)" :active="$isFavourited" glass
                                         active-label="Favourited" inactive-label="Add to favourites">
                        @unless ($isFavourited)
                            <label class="mb-2 flex items-center gap-2 text-sm text-soil-muted">
                                <input type="checkbox" name="wants_restock_alert" value="1" checked class="rounded border-leaf-900/20 text-leaf-500 focus:ring-leaf-400">
                                Tell me when it's back in stock
                            </label>
                        @endunless
                    </x-ui.toggle-button>

                    {{-- favourite hone ke baad alert ka apna switch (Favourites page wala route) --}}
                    @if ($isFavourited)
                        <form method="POST" action="{{ route('customer.favourites.products.restock-alert', $product) }}">
                            @csrf
                            @method('PATCH')
                            <label class="flex items-center gap-2 text-sm text-soil-muted">
                                <input type="hidden" name="wants_restock_alert" value="0">
                                <input type="checkbox" name="wants_restock_alert" value="1" onchange="this.form.submit()"
                                       @checked($wantsRestockAlert) class="rounded border-leaf-900/20 text-leaf-500 focus:ring-leaf-400">
                                Tell me when it's back in stock
                            </label>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="mt-10 grid gap-6 lg:grid-cols-3">
        <section class="card p-5">
            <div class="flex items-center gap-3">
                <img src="{{ $farmer->coverImageUrl() }}" alt="{{ $farmer->stall_name }}" class="h-14 w-14 rounded-full border-2 border-white/70 object-cover object-[center_20%]">
                <div class="min-w-0">
                    <p class="truncate font-medium text-soil">{{ $farmer->stall_name }}</p>
                    @if ($farmer->averageRating())
                        <div class="flex items-center gap-1.5 text-sm text-soil-muted">
                            <x-ui.star-rating :rating="$farmer->averageRating()" /> {{ $farmer->averageRating() }}
                        </div>
                    @else
                        <p class="text-sm text-soil-muted">No stall reviews yet</p>
                    @endif
                </div>
            </div>
            @if ($farmer->bio)
                <p class="mt-3 text-sm text-soil-muted">{{ \Illuminate\Support\Str::limit($farmer->bio, 120) }}</p>
            @endif
            <a href="{{ route('customer.farmers.show', $farmer) }}" class="link-orange mt-3">
                Visit stall
                <iconify-icon icon="tabler:arrow-right"></iconify-icon>
            </a>
        </section>

        <section class="card p-5 lg:col-span-2">
            <h2 class="flex items-center gap-2 font-display text-lg font-semibold">
                <span class="glass-icon-chip h-9 w-9 text-base text-leaf-600"><iconify-icon icon="tabler:calendar-time"></iconify-icon></span>
                Pickup
            </h2>

            @if ($farmer->markets->isEmpty())
                <p class="mt-3 text-sm text-soil-muted">This stall isn't selling at a market right now.</p>
            @else
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($farmer->markets as $market)
                        @php $windowsAtMarket = $farmer->pickupWindows->where('market_id', $market->id); @endphp
                        <div class="rounded-xl border border-white/70 bg-white/50 p-3">
                            <a href="{{ route('customer.markets.show', $market) }}" class="font-medium text-soil hover:text-leaf-700">{{ $market->name }}</a>
                            <p class="text-xs text-soil-muted">{{ $market->city }}</p>
                            @if ($windowsAtMarket->isEmpty())
                                <p class="mt-2 text-sm text-soil-muted">No pickup slots yet</p>
                            @else
                                <ul class="mt-2 space-y-0.5 text-sm text-soil">
                                    @foreach ($windowsAtMarket as $window)
                                        <li>{{ $window->dayName() }}, {{ $window->timeRangeText() }} {{ $market->timezoneLabel() }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <section class="mt-10">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <h2 class="font-display text-xl font-semibold">Reviews for this product</h2>
            @if ($averageRating)
                <div class="flex items-center gap-2 text-sm text-soil-muted">
                    <x-ui.star-rating :rating="$averageRating" />
                    <span><span class="font-semibold text-soil">{{ number_format($averageRating, 1) }}</span> from {{ $reviews->count() }} {{ \Illuminate\Support\Str::plural('review', $reviews->count()) }}</span>
                </div>
            @endif
        </div>

        @if ($reviews->isEmpty())
            <div class="card">
                <x-ui.empty-state icon="tabler:message-star" title="No reviews yet"
                                   message="Customers can review it after they've picked it up." />
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($reviews as $review)
                    <div class="card p-5">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-medium">{{ $review->customer->name }}</p>
                            <x-ui.star-rating :rating="$review->rating" />
                        </div>
                        <p class="text-xs text-soil-muted">{{ $review->created_at->format('j M Y') }}</p>
                        @if ($review->comment)
                            <p class="mt-2 text-sm text-soil">{{ $review->comment }}</p>
                        @endif
                        @if ($review->farmer_reply)
                            <div class="mt-3 rounded-xl border border-white/70 bg-white/50 p-3">
                                <p class="text-xs font-medium text-leaf-700">Reply from {{ $farmer->stall_name }}</p>
                                <p class="mt-1 text-sm text-soil">{{ $review->farmer_reply }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    @if ($relatedProducts->isNotEmpty())
        <section class="mt-10">
            <h2 class="mb-4 font-display text-xl font-semibold">You may also like</h2>
            <div class="grid grid-cols-2 gap-5 lg:grid-cols-4">
                @foreach ($relatedProducts as $relatedProduct)
                    <x-ui.product-card :product="$relatedProduct" />
                @endforeach
            </div>
        </section>
    @endif
    </div>

</x-dynamic-component>
