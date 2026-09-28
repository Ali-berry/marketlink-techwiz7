@php
    // logged in user apne panel mein, guest ko public site
    $layoutComponent = auth()->check() ? 'layouts.panel' : 'layouts.public';
    $sortLabels = ['top_rated' => 'Top rated', 'most_products' => 'Most products', 'newest' => 'Newest'];
@endphp

<x-dynamic-component :component="$layoutComponent" title="Farmers">

    <div @class(['mx-auto max-w-7xl px-4 py-10 sm:px-8' => ! auth()->check()])>
    @unless (auth()->check())
        <x-ui.flash-messages />
    @endunless

    <div class="mb-6">
        <h2 class="text-2xl font-semibold">Farmers</h2>
        <p class="mt-1 text-sm text-soil-muted">{{ $farmers->total() }} approved {{ \Illuminate\Support\Str::plural('stall', $farmers->total()) }} selling on MarketLink.</p>
    </div>

    <form method="GET" action="{{ route('customer.farmers.index') }}" class="glass mb-6 grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-5">
        <div class="sm:col-span-2 lg:col-span-2">
            <label class="form-label" for="search">Search</label>
            <input id="search" type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="e.g. Green Valley">
        </div>

        <div>
            <label class="form-label" for="market_id">Market</label>
            <select id="market_id" name="market_id" class="form-input">
                <option value="">All</option>
                @foreach ($markets as $market)
                    <option value="{{ $market->id }}" @selected(request('market_id') == $market->id)>{{ $market->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label" for="category_id">Sells</label>
            <select id="category_id" name="category_id" class="form-input">
                <option value="">Anything</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label" for="sort">Sort by</label>
            <select id="sort" name="sort" class="form-input">
                @foreach ($sortLabels as $sortValue => $sortLabel)
                    <option value="{{ $sortValue }}" @selected($sortBy === $sortValue)>{{ $sortLabel }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-end justify-end gap-3 sm:col-span-2 lg:col-span-5">
            @if (request()->anyFilled(['search', 'market_id', 'category_id']))
                <a href="{{ route('customer.farmers.index') }}" class="btn-glass-outline">Clear filters</a>
            @endif
            <button type="submit" class="btn-glass-orange">Filter</button>
        </div>
    </form>

    @if ($farmers->isEmpty())
        <div class="glass p-6">
            <x-ui.empty-state icon="tabler:tractor" title="No farmers match those filters"
                               message="Try another market or category, or clear the search." />
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($farmers as $farmer)
                <a href="{{ route('customer.farmers.show', $farmer) }}"
                   class="glass group flex flex-col overflow-hidden p-0 transition hover:-translate-y-1 hover:shadow-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-leaf-400">
                    <div class="border-b border-white/25 p-2">
                        <img src="{{ $farmer->coverImageUrl() }}" alt="{{ $farmer->stall_name }}" class="h-40 w-full rounded-2xl object-cover object-[center_20%]">
                    </div>

                    <div class="flex flex-1 flex-col p-5">
                        <p class="font-display text-lg font-semibold text-soil group-hover:text-leaf-700">{{ $farmer->stall_name }}</p>
                        <p class="mt-1 flex items-center gap-1.5 text-sm text-soil-muted">
                            <iconify-icon icon="tabler:map-pin"></iconify-icon>
                            {{ $farmer->address }}
                        </p>
                        @if ($farmer->bio)
                            <p class="mt-3 text-sm text-soil-muted">{{ \Illuminate\Support\Str::limit($farmer->bio, 90) }}</p>
                        @endif

                        @if ($farmer->markets->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach ($farmer->markets as $marketSoldAt)
                                    <span class="rounded-full bg-leaf-50 px-2.5 py-1 text-[11px] font-medium text-leaf-700">{{ $marketSoldAt->name }}</span>
                                @endforeach
                            </div>
                        @endif

                        {{-- mt-auto se ye row neeche rehti hai taake cards line mein hon, pt-4 chhoti bio pe gap ke liye --}}
                        <div class="mt-auto pt-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-white/30 pt-3 text-sm">
                            <span class="flex items-center gap-1.5">
                                @if ($farmer->average_rating)
                                    <x-ui.star-rating :rating="$farmer->average_rating" />
                                    <span class="text-soil-muted">
                                        <span class="font-medium text-soil">{{ number_format($farmer->average_rating, 1) }}</span>
                                        ({{ $farmer->visible_reviews_count }} {{ \Illuminate\Support\Str::plural('review', $farmer->visible_reviews_count) }})
                                    </span>
                                @else
                                    <span class="text-soil-muted">No reviews yet</span>
                                @endif
                            </span>
                            <span class="font-medium text-tomato-600">
                                {{ $farmer->listed_products_count }} {{ \Illuminate\Support\Str::plural('product', $farmer->listed_products_count) }}
                            </span>
                        </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $farmers->links() }}
        </div>
    @endif
    </div>

</x-dynamic-component>
