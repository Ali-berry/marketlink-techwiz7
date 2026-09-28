@php
    $tabLabels = ['farmers' => 'Farmers', 'products' => 'Products', 'markets' => 'Saved markets'];
@endphp

<x-layouts.panel title="Favourites">

    <div class="mb-6">
        <h2 class="text-2xl font-semibold">Favourites</h2>
        <p class="mt-1 text-sm text-soil-muted">Farmers, products and markets you've saved.</p>
    </div>

    <div class="tab-bar">
        @foreach ($tabLabels as $tabKey => $tabLabel)
            <a href="{{ route('customer.favourites.index', ['tab' => $tabKey]) }}"
               @class(['tab-pill', 'is-active' => $activeTab === $tabKey])>
                {{ $tabLabel }}
            </a>
        @endforeach
    </div>

    @if ($activeTab === 'farmers')
        @if ($favouriteFarmers->isEmpty())
            <div class="card">
                <x-ui.empty-state icon="tabler:heart" title="No favourite farmers yet" />
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($favouriteFarmers as $farmer)
                    <div class="card overflow-hidden p-0">
                        <a href="{{ route('customer.farmers.show', $farmer) }}">
                            <img src="{{ $farmer->coverImageUrl() }}" alt="{{ $farmer->stall_name }}" class="h-32 w-full object-cover object-[center_20%]">
                        </a>
                        <div class="p-4">
                            <a href="{{ route('customer.farmers.show', $farmer) }}" class="font-medium hover:text-leaf-700">{{ $farmer->stall_name }}</a>
                            <form method="POST" action="{{ route('customer.favourites.farmers.toggle', $farmer) }}" class="mt-3">
                                @csrf
                                <button type="submit" class="btn-outline w-full">
                                    <iconify-icon icon="tabler:heart-off"></iconify-icon>
                                    Remove
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @elseif ($activeTab === 'products')
        @if ($favouriteProducts->isEmpty())
            <div class="card">
                <x-ui.empty-state icon="tabler:heart" title="No favourite products yet" />
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($favouriteProducts as $product)
                    <div class="card overflow-hidden p-0">
                        <a href="{{ route('customer.products.show', $product) }}" class="relative block aspect-[4/3] w-full overflow-hidden">
                            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                        </a>
                        <div class="p-4">
                            <a href="{{ route('customer.products.show', $product) }}" class="font-medium hover:text-leaf-700">{{ $product->name }}</a>
                            <p class="mt-0.5 text-sm text-soil-muted">{{ $product->farmer->stall_name }}</p>

                            <form method="POST" action="{{ route('customer.favourites.products.restock-alert', $product) }}" class="mt-3">
                                @csrf
                                @method('PATCH')
                                <label class="flex items-center gap-2 text-sm text-soil-muted">
                                    <input type="hidden" name="wants_restock_alert" value="0">
                                    <input type="checkbox" name="wants_restock_alert" value="1" onchange="this.form.submit()"
                                           @checked($product->pivot->wants_restock_alert) class="rounded border-cream-dark text-leaf-500 focus:ring-leaf-400">
                                    Notify me on restock
                                </label>
                            </form>

                            <form method="POST" action="{{ route('customer.favourites.products.toggle', $product) }}" class="mt-2">
                                @csrf
                                <button type="submit" class="btn-outline w-full">
                                    <iconify-icon icon="tabler:heart-off"></iconify-icon>
                                    Remove
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        @if ($savedMarkets->isEmpty())
            <div class="card">
                <x-ui.empty-state icon="tabler:bookmark" title="No saved markets yet" />
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($savedMarkets as $market)
                    <div class="card overflow-hidden p-0">
                        <a href="{{ route('customer.markets.show', $market) }}">
                            <img src="{{ $market->coverImageUrl() }}" alt="{{ $market->name }}" class="h-32 w-full object-cover">
                        </a>
                        <div class="p-4">
                            <a href="{{ route('customer.markets.show', $market) }}" class="font-medium hover:text-leaf-700">{{ $market->name }}</a>
                            <p class="mt-0.5 text-sm text-soil-muted">{{ $market->operatingDaysText() }}</p>
                            <form method="POST" action="{{ route('customer.favourites.markets.toggle', $market) }}" class="mt-3">
                                @csrf
                                <button type="submit" class="btn-outline w-full">
                                    <iconify-icon icon="tabler:bookmark-off"></iconify-icon>
                                    Remove
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif

</x-layouts.panel>
