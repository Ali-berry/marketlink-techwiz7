@php
    $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    // logged in user apne panel mein, guest ko public site - products page jaisa.
    // panel ka <main> padding aur flash khud deta hai, yahan sirf guest ke liye
    $layoutComponent = auth()->check() ? 'layouts.panel' : 'layouts.public';
@endphp

<x-dynamic-component :component="$layoutComponent" title="Markets near me">

    <div @class(['mx-auto max-w-7xl px-4 py-10 sm:px-8' => ! auth()->check()]) x-data="{ openMarket: null }">
    @unless (auth()->check())
        <x-ui.flash-messages />
    @endunless

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold">Markets near me</h2>
            <p class="mt-1 text-sm text-soil-muted">Every active MarketLink market, on one map.</p>
        </div>

        {{-- area chuno ya location do - dono markets-distance-sort.js se sort, jo aakhir mein use hua wahi.
             saved location (MarketController) ho to pehle se bhara hota hai --}}
        <div data-markets-starting-point
             @if ($savedStartingPoint)
                 data-initial-latitude="{{ $savedStartingPoint['latitude'] }}"
                 data-initial-longitude="{{ $savedStartingPoint['longitude'] }}"
                 data-initial-label="{{ $savedStartingPoint['areaName'] }}"
             @endif
             class="w-full sm:w-auto">
            <label class="form-label" for="market-area">Find markets near</label>
            <div class="flex flex-wrap items-center gap-2">
                <div class="w-full sm:w-60">
                    @include('partials.texas-area-combobox', [
                        'inputId' => 'market-area',
                        'inputName' => 'market_area',
                        'latInputName' => 'market_area_latitude',
                        'lngInputName' => 'market_area_longitude',
                        'placeholder' => 'Type your city or area...',
                        'initialValue' => $savedStartingPoint['areaName'] ?? '',
                        'onLightBackground' => true,
                        'noMatchMessage' => 'No matching area - pick one from the list.',
                    ])
                </div>
                <button type="button" data-use-my-location class="btn-glass-outline">
                    <iconify-icon icon="tabler:current-location"></iconify-icon>
                    Use my location
                </button>
                <button type="button" data-clear-starting-point hidden class="btn-glass-outline">
                    <iconify-icon icon="tabler:x"></iconify-icon>
                    Clear
                </button>
            </div>
            <p data-starting-point-note hidden class="mt-1.5 text-xs text-soil-muted">
                Showing markets nearest to <span data-starting-point-label class="font-medium text-soil"></span>
            </p>
        </div>
    </div>

    <form method="GET" action="{{ route('customer.markets.index') }}" class="glass mb-6 flex flex-wrap items-end gap-4 p-5">
        <div>
            <label class="form-label" for="day">Open on</label>
            <select id="day" name="day" class="form-input">
                <option value="">Any day</option>
                @foreach ($dayNames as $dayOption)
                    <option value="{{ $dayOption }}" @selected(request('day') === $dayOption)>{{ ucfirst($dayOption) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-glass-outline">Filter</button>
        @if (request()->filled('day'))
            <a href="{{ route('customer.markets.index') }}" class="text-sm font-medium text-soil-muted hover:text-soil">Clear</a>
        @endif
    </form>

    <div class="mb-3 flex items-center justify-between">
        <p class="text-sm font-medium text-soil">Map view</p>
        <p class="text-xs text-soil-muted">Click a market card below to zoom in on the map</p>
    </div>
    <div class="glass mb-8 p-2">
    <div class="h-[420px] overflow-hidden rounded-2xl"
         data-leaflet-map
         data-follows-starting-point
         data-center-lat="{{ config('marketlink.default_map_center.latitude') }}"
         data-center-lng="{{ config('marketlink.default_map_center.longitude') }}"
         data-zoom="{{ config('marketlink.default_map_center.zoom') }}"
         data-markers="{{ json_encode($mapMarkers) }}"
         role="img" aria-label="Map showing every active market"></div>
    </div>

    @if ($markets->isEmpty())
        <div class="glass p-6">
            <x-ui.empty-state icon="tabler:map-pin-off" title="No markets match that filter" />
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3" data-market-cards>
            @foreach ($markets as $market)
                <article class="glass glass-tint-green group relative overflow-hidden p-0 transition hover:-translate-y-1 hover:shadow-xl"
                         data-market-card data-market-id="{{ $market->id }}" data-market-name="{{ $market->name }}" data-lat="{{ $market->latitude }}" data-lng="{{ $market->longitude }}">
                    {{-- rounded frame, market banner jaisa --}}
                    <a href="{{ route('customer.markets.show', $market) }}" class="relative m-2 block aspect-[16/9] overflow-hidden rounded-2xl">
                        <img src="{{ $market->coverImageUrl() }}" alt="{{ $market->name }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        {{-- sirf halka tint, blur se asli photos out of focus lagti hain --}}
                        <div class="pointer-events-none absolute inset-0 bg-leaf-900/10"></div>

                        {{-- saved location ho to pehle se bhara (MarketController), area ya "Use my location" browser mein badal deta hai --}}
                        <span data-distance-label @class(['badge-orange absolute right-3 top-3 shadow-md', 'hidden' => ! isset($market->distanceMiles)])>
                            @isset($market->distanceMiles)
                                {{ number_format($market->distanceMiles, 1) }} mi away
                            @endisset
                        </span>
                    </a>

                    {{-- alag pin button - card itna bhara hai ke khali jagah click karna mushkil tha --}}
                    <button type="button" data-locate-on-map
                            class="glass absolute left-5 top-5 flex h-9 w-9 items-center justify-center !rounded-full text-soil transition hover:bg-white/30"
                            aria-label="Show {{ $market->name }} on the map">
                        <iconify-icon icon="tabler:map-pin"></iconify-icon>
                    </button>
                    <div class="px-5 pb-5 pt-3">
                        <a href="{{ route('customer.markets.show', $market) }}" class="font-display text-lg font-semibold text-soil hover:text-leaf-700">{{ $market->name }}</a>
                        <p class="mt-1 flex items-center gap-1.5 text-sm text-soil-muted">
                            <iconify-icon icon="tabler:clock"></iconify-icon>
                            {{ $market->timingText() }}
                        </p>

                        {{-- page chhodne ki jagah neeche wala shared modal khulta hai. image / naam wale links poore market page pe jate hain --}}
                        <button type="button"
                                @click="openMarket = {
                                    name: @js($market->name),
                                    timing: @js($market->timingText()),
                                    days: @js($market->operatingDaysText()),
                                    address: @js($market->address.', '.$market->city),
                                    lat: @js($market->latitude),
                                    lng: @js($market->longitude),
                                    directionsUrl: @js($market->directionsUrl()),
                                    showUrl: @js(route('customer.markets.show', $market)),
                                    farmers: @js($market->farmers->filter->isApproved()->pluck('stall_name')->values()),
                                }"
                                class="btn-glass-orange relative z-10 mt-4 w-full">
                            View market
                            <iconify-icon icon="tabler:arrow-right"></iconify-icon>
                        </button>

                        <div class="mt-4 grid grid-cols-2 divide-x divide-cream-dark border-t border-cream-dark pt-3 text-center">
                            <div>
                                <p class="text-xs text-soil-muted">Open days</p>
                                <p class="mt-0.5 truncate text-sm font-semibold text-soil">{{ $market->operatingDaysText() }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-soil-muted">Farmers</p>
                                <p class="mt-0.5 text-sm font-semibold text-soil">{{ $market->approved_farmers_count }}</p>
                            </div>
                        </div>

                        @if ($market->categoryPreview->isNotEmpty())
                            <div class="mt-4 flex flex-wrap gap-1.5 border-t border-cream-dark pt-3">
                                @foreach ($market->categoryPreview as $categoryName)
                                    <span class="rounded-full bg-leaf-50 px-2.5 py-1 text-[11px] font-medium text-leaf-700">{{ $categoryName }}</span>
                                @endforeach
                            </div>
                        @endif

                        @if ($market->farmers->isNotEmpty())
                            <div class="mt-4 border-t border-cream-dark pt-3">
                                <p class="text-xs text-soil-muted">Sellers here</p>
                                <ul class="mt-2 space-y-1.5">
                                    @foreach ($market->farmers->take(4) as $sellerAtMarket)
                                        <li>
                                            <a href="{{ route('customer.farmers.show', $sellerAtMarket) }}" class="text-sm text-soil-muted hover:text-soil">
                                                {{ $sellerAtMarket->stall_name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    {{-- shared "View market" modal - har card ke liye ek hi, click hue card ke data se bharta hai. Guest bhi dekh sakta hai --}}
    <div x-show="openMarket" x-transition.opacity x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="openMarket = null">
        <div class="absolute inset-0 bg-soil/70 backdrop-blur-sm" @click="openMarket = null"></div>

        <div class="modal-glass relative z-10 max-h-[85vh] w-full max-w-lg overflow-y-auto p-6 sm:p-8" @click.stop x-show="openMarket" x-transition.scale.95>
            <button type="button" @click="openMarket = null"
                    class="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full bg-white/50 text-soil transition hover:bg-white/70"
                    aria-label="Close">
                <iconify-icon icon="tabler:x"></iconify-icon>
            </button>

            <template x-if="openMarket">
                <div>
                    <h3 class="pr-10 font-display text-2xl font-semibold text-soil" x-text="openMarket.name"></h3>

                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="glass p-4">
                            <p class="text-xs font-medium text-soil-muted">Open days</p>
                            <p class="mt-1 text-sm font-semibold text-soil" x-text="openMarket.days"></p>
                        </div>
                        <div class="glass p-4">
                            <p class="text-xs font-medium text-soil-muted">Timings</p>
                            <p class="mt-1 text-sm font-semibold text-soil" x-text="openMarket.timing"></p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <p class="text-xs font-medium text-soil-muted">Location</p>
                        <p class="mt-1 text-sm text-soil" x-text="openMarket.address"></p>
                        <p class="mt-0.5 text-xs text-soil-muted" x-text="openMarket.lat.toFixed(5) + ', ' + openMarket.lng.toFixed(5)"></p>
                        <a :href="openMarket.directionsUrl" target="_blank" rel="noopener" class="link-orange mt-2">
                            Get directions
                            <iconify-icon icon="tabler:arrow-right"></iconify-icon>
                        </a>
                    </div>

                    <div class="mt-5">
                        <p class="text-xs font-medium text-soil-muted" x-text="openMarket.farmers.length + (openMarket.farmers.length === 1 ? ' approved farmer' : ' approved farmers')"></p>
                        <ul class="mt-2 space-y-1.5">
                            <template x-for="farmerName in openMarket.farmers" :key="farmerName">
                                <li class="text-sm text-soil" x-text="farmerName"></li>
                            </template>
                        </ul>
                        <p x-show="openMarket.farmers.length === 0" class="mt-1 text-sm text-soil-muted">No approved farmers here yet.</p>
                    </div>

                    <a :href="openMarket.showUrl" class="btn-glass-orange mt-6 w-full justify-center">
                        See full market page
                        <iconify-icon icon="tabler:arrow-right"></iconify-icon>
                    </a>
                </div>
            </template>
        </div>
    </div>

    @push('scripts')
        @vite(['resources/js/market-map.js', 'resources/js/markets-distance-sort.js'])
    @endpush
    </div>

</x-dynamic-component>
