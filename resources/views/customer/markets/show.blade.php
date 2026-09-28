@php
    // logged in user apne panel mein, guest ko public site - markets index jaisa
    $layoutComponent = auth()->check() ? 'layouts.panel' : 'layouts.public';
@endphp

<x-dynamic-component :component="$layoutComponent" :title="$market->name">

    <div @class(['mx-auto max-w-7xl px-4 py-10 sm:px-8' => ! auth()->check()])>
    @unless (auth()->check())
        <x-ui.flash-messages />
    @endunless

    <div class="mb-6">
        <a href="{{ route('customer.markets.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-soil-muted hover:text-soil">
            <iconify-icon icon="tabler:arrow-left"></iconify-icon>
            Back to markets
        </a>
    </div>

    {{-- glass frame mein market photo ki wide copy. fade sirf neeche tags wali patti pe --}}
    <div class="glass overflow-hidden p-2">
        <div class="relative overflow-hidden rounded-2xl">
            <img src="{{ $market->bannerImageUrl() }}" alt="{{ $market->name }}" class="h-56 w-full object-cover sm:h-72">
            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-leaf-900/70 to-transparent"></div>

            <div class="absolute inset-x-0 bottom-0 flex flex-wrap items-center gap-2 p-5 sm:p-6">
                <span class="glass inline-flex items-center gap-1.5 !rounded-full px-3 py-1 text-xs font-medium text-white">
                    <iconify-icon icon="tabler:map-pin"></iconify-icon>
                    {{ $market->city }}
                </span>
                <span class="glass inline-flex items-center gap-1.5 !rounded-full px-3 py-1 text-xs font-medium text-white">
                    <iconify-icon icon="tabler:users"></iconify-icon>
                    {{ $market->approved_farmers_count }} {{ \Illuminate\Support\Str::plural('farmer', $market->approved_farmers_count) }}
                </span>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-8 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="font-display text-2xl font-semibold sm:text-3xl">{{ $market->name }}</h1>
                        @if ($market->isOpenNow())
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-leaf-500/30 bg-leaf-500/10 px-3 py-1 text-xs font-medium text-leaf-700">
                                <span class="h-2 w-2 rounded-full bg-leaf-500"></span>
                                Open now
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-soil/15 bg-white/40 px-3 py-1 text-xs font-medium text-soil-muted">
                                <span class="h-2 w-2 rounded-full bg-soil-muted/60"></span>
                                Closed now
                            </span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-soil-muted">{{ $market->address }}</p>
                </div>

                <x-ui.toggle-button :action="route('customer.favourites.markets.toggle', $market)" :active="$isSaved" glass
                                     icon="tabler:bookmark" icon-active="tabler:bookmark-filled"
                                     active-label="Saved" inactive-label="Save market" />
            </div>

            @if ($market->description)
                <p class="text-soil-muted">{{ $market->description }}</p>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="glass flex items-start gap-4 p-5">
                    <span class="glass-icon-chip text-leaf-600"><iconify-icon icon="tabler:calendar"></iconify-icon></span>
                    <div>
                        <p class="font-medium text-soil">Open days</p>
                        <p class="text-sm text-soil-muted">{{ $market->operatingDaysText() }}</p>
                    </div>
                </div>
                <div class="glass flex items-start gap-4 p-5">
                    <span class="glass-icon-chip text-tomato-600"><iconify-icon icon="tabler:clock"></iconify-icon></span>
                    <div>
                        <p class="font-medium text-soil">Timings</p>
                        <p class="text-sm text-soil-muted">{{ $market->timingText() }}</p>
                    </div>
                </div>
            </div>

            {{-- location card - sirf is market ka chhota map, markets page wala Leaflet setup --}}
            <div class="glass p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <span class="glass-icon-chip text-leaf-600"><iconify-icon icon="tabler:map-pin"></iconify-icon></span>
                        <div>
                            <p class="font-medium text-soil">Location</p>
                            <p class="text-sm text-soil-muted">{{ $market->address }}</p>
                        </div>
                    </div>
                    <a href="{{ $market->directionsUrl() }}" target="_blank" rel="noopener" class="btn-glass-outline">
                        <iconify-icon icon="tabler:map-2"></iconify-icon>
                        Get directions
                    </a>
                </div>

                <div class="mt-4 h-56 overflow-hidden rounded-2xl"
                     data-leaflet-map
                     data-center-lat="{{ $market->latitude }}"
                     data-center-lng="{{ $market->longitude }}"
                     data-zoom="14"
                     data-markers="{{ json_encode([['lat' => $market->latitude, 'lng' => $market->longitude, 'name' => $market->name, 'details' => $market->timingText()]]) }}"
                     role="img" aria-label="Map showing where {{ $market->name }} is"></div>
            </div>

            <a href="{{ route('customer.products.index', ['market_id' => $market->id]) }}" class="btn-glass-orange w-full sm:w-auto">
                <iconify-icon icon="tabler:basket"></iconify-icon>
                Browse products from this market
            </a>
        </div>

        <div>
            <x-ui.section-heading title="Farmers at this market" />

            @if ($farmersAtMarket->isEmpty())
                <div class="glass p-6">
                    <x-ui.empty-state icon="tabler:tractor" title="No approved farmers here yet" />
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($farmersAtMarket as $farmer)
                        <a href="{{ route('customer.farmers.show', $farmer) }}"
                           class="glass group flex items-center gap-3 p-3 transition hover:-translate-y-0.5 hover:bg-white/30 hover:shadow-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-leaf-400">
                            <img src="{{ $farmer->coverImageUrl() }}" alt="{{ $farmer->stall_name }}" class="h-12 w-12 shrink-0 rounded-full border-2 border-white/60 object-cover object-[center_20%]">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-soil">{{ $farmer->stall_name }}</p>
                                @if ($farmer->pivot->stall_number)
                                    <p class="text-xs text-soil-muted">Stall {{ $farmer->pivot->stall_number }}</p>
                                @endif
                            </div>
                            <iconify-icon icon="tabler:chevron-right" class="shrink-0 text-soil-muted transition group-hover:translate-x-0.5 group-hover:text-soil"></iconify-icon>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    </div>

    @push('scripts')
        @vite('resources/js/market-map.js')
    @endpush

</x-dynamic-component>
