@php
    // stall page sab dekh sakte hain - customer apne panel mein, guest public site mein
    $layoutComponent = auth()->check() ? 'layouts.panel' : 'layouts.public';
@endphp

<x-dynamic-component :component="$layoutComponent" :title="$farmer->stall_name">

    <div @class(['mx-auto max-w-7xl px-4 py-10 sm:px-8' => ! auth()->check()])>
    @unless (auth()->check())
        <x-ui.flash-messages />
    @endunless

    {{-- stall header - farmer photo square hai (768px), details ke saath square frame mein.
         phone pe photo upar, max 320px --}}
    <section class="glass p-4 sm:p-6">
        <div class="flex flex-col gap-6 md:flex-row md:items-center">
            <div class="mx-auto w-full max-w-[320px] shrink-0 md:mx-0 md:w-56 lg:w-[280px]">
                <img src="{{ $farmer->coverImageUrl() }}" alt="{{ $farmer->stall_name }}"
                     class="aspect-square w-full rounded-2xl border border-white/60 object-cover shadow-md">
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <h1 class="font-display text-2xl font-semibold sm:text-3xl lg:text-4xl">{{ $farmer->stall_name }}</h1>

                    <x-ui.toggle-button :action="route('customer.favourites.farmers.toggle', $farmer)" :active="$isFavourited" glass
                                         active-label="Favourited" inactive-label="Add to favourites" />
                </div>

                <div class="mt-2 flex items-center gap-2">
                    <x-ui.star-rating :rating="$farmer->averageRating() ?? 0" />
                    <span class="text-sm text-soil-muted">
                        @if ($farmer->averageRating())
                            {{ $farmer->averageRating() }} average rating ({{ $reviews->count() }} {{ \Illuminate\Support\Str::plural('review', $reviews->count()) }})
                        @else
                            No ratings yet
                        @endif
                    </span>
                </div>

                <p class="mt-3 flex items-center gap-1.5 text-sm text-soil-muted">
                    <iconify-icon icon="tabler:calendar-stats"></iconify-icon>
                    {{ $farmer->sellingSinceLabel() }}
                </p>
                @if ($farmer->farming_experience)
                    <p class="mt-1 flex items-center gap-1.5 text-sm text-soil-muted">
                        <iconify-icon icon="tabler:plant-2"></iconify-icon>
                        {{ $farmer->farming_experience }}
                    </p>
                @endif

                @if ($farmer->bio)
                    <p class="mt-4 max-w-2xl text-soil-muted">{{ $farmer->bio }}</p>
                @endif

                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-leaf-50 px-3 py-1 text-xs font-medium text-leaf-700">
                        <iconify-icon icon="tabler:basket"></iconify-icon>
                        {{ $products->count() }} {{ \Illuminate\Support\Str::plural('product', $products->count()) }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-leaf-50 px-3 py-1 text-xs font-medium text-leaf-700">
                        <iconify-icon icon="tabler:building-store"></iconify-icon>
                        {{ $farmer->markets->count() }} {{ \Illuminate\Support\Str::plural('market', $farmer->markets->count()) }}
                    </span>
                </div>
            </div>
        </div>
    </section>

    <div class="mt-8 grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-ui.section-heading title="Products" />

            @if ($products->isEmpty())
                <div class="glass p-6">
                    <x-ui.empty-state icon="tabler:carrot" title="No products listed right now" />
                </div>
            @else
                <div class="grid grid-cols-2 gap-5 lg:grid-cols-3">
                    @foreach ($products as $product)
                        <x-ui.product-card :product="$product" :show-farmer="false" />
                    @endforeach
                </div>
            @endif

            <div class="mt-10">
                <x-ui.section-heading title="Reviews" />

                @if ($reviews->isEmpty())
                    <div class="glass p-6">
                        <x-ui.empty-state icon="tabler:message-star" title="No reviews yet" />
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($reviews as $review)
                            <div class="glass p-5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <p class="font-medium">{{ $review->customer->name }}</p>
                                        @if ($review->product)
                                            <p class="text-xs text-soil-muted">About {{ $review->product->name }}</p>
                                        @endif
                                    </div>
                                    <x-ui.star-rating :rating="$review->rating" />
                                </div>

                                @if ($review->comment)
                                    <p class="mt-2 text-sm text-soil-muted">{{ $review->comment }}</p>
                                @endif

                                @if ($review->farmer_reply)
                                    <div class="mt-3 rounded-xl border border-white/40 bg-white/40 p-3">
                                        <p class="text-xs font-medium text-soil-muted">Reply from {{ $farmer->stall_name }}</p>
                                        <p class="mt-1 text-sm">{{ $review->farmer_reply }}</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-8">
            <div>
                <x-ui.section-heading title="Message this stall" />

                <form method="POST" action="{{ route('customer.farmers.message', $farmer) }}" class="glass space-y-3 p-5">
                    @csrf
                    <textarea name="body" rows="3" placeholder="Ask {{ $farmer->stall_name }} a question..." class="form-input"></textarea>
                    <x-input-error :messages="$errors->get('body')" />
                    <button type="submit" class="btn-glass-orange w-full">
                        <iconify-icon icon="tabler:send-2"></iconify-icon>
                        Send message
                    </button>
                </form>
            </div>

            {{-- location card - market page jaisa: address, stall ke pin ka chhota map aur directions button --}}
            <div>
                <x-ui.section-heading title="Stall location" />

                <div class="glass p-5">
                    <div class="flex items-start gap-4">
                        <span class="glass-icon-chip text-leaf-600"><iconify-icon icon="tabler:map-pin"></iconify-icon></span>
                        <div class="min-w-0">
                            <p class="font-medium text-soil">Address</p>
                            <p class="text-sm text-soil-muted">{{ $farmer->address }}</p>
                        </div>
                    </div>

                    @if ($farmer->hasMapLocation())
                        <div class="mt-4 h-48 overflow-hidden rounded-2xl"
                             data-leaflet-map
                             data-center-lat="{{ $farmer->latitude }}"
                             data-center-lng="{{ $farmer->longitude }}"
                             data-zoom="12"
                             data-markers="{{ json_encode([['lat' => (float) $farmer->latitude, 'lng' => (float) $farmer->longitude, 'name' => $farmer->stall_name, 'details' => $farmer->address]]) }}"
                             role="img" aria-label="Map showing where {{ $farmer->stall_name }} is"></div>

                        <a href="{{ $farmer->directionsUrl() }}" target="_blank" rel="noopener" class="btn-glass-outline mt-4 w-full">
                            <iconify-icon icon="tabler:map-2"></iconify-icon>
                            Get directions
                        </a>
                    @endif
                </div>
            </div>

            <div>
                <x-ui.section-heading title="Markets and pickup slots" />

                @if ($farmer->markets->isEmpty())
                    <div class="glass p-6">
                        <x-ui.empty-state icon="tabler:building-store" title="Not selling at any market right now" />
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($farmer->markets as $market)
                            @php $windowsAtMarket = $farmer->pickupWindows->where('market_id', $market->id); @endphp

                            <div class="glass p-5">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <a href="{{ route('customer.markets.show', $market) }}" class="font-medium text-soil hover:text-leaf-700">{{ $market->name }}</a>
                                    @if ($market->pivot->stall_number)
                                        <span class="rounded-full bg-leaf-50 px-2.5 py-0.5 text-xs font-medium text-leaf-700">Stall {{ $market->pivot->stall_number }}</span>
                                    @endif
                                </div>
                                <p class="mt-0.5 text-xs text-soil-muted">{{ $market->city }}</p>

                                @if ($windowsAtMarket->isEmpty())
                                    <p class="mt-2 text-sm text-soil-muted">No pickup slots here yet.</p>
                                @else
                                    <ul class="mt-2 space-y-1 text-sm text-soil-muted">
                                        @foreach ($windowsAtMarket as $window)
                                            <li>{{ $window->dayName() }}, {{ $window->timeRangeText() }} {{ $market->timezoneLabel() }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    </div>

    @push('scripts')
        @vite('resources/js/market-map.js')
    @endpush

</x-dynamic-component>
