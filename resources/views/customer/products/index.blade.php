@php
    $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    // products sab browse kar sakte hain - customer apne panel mein, guest public site mein
    $layoutComponent = auth()->check() ? 'layouts.panel' : 'layouts.public';
@endphp

<x-dynamic-component :component="$layoutComponent" title="Browse products">

    <div @class(['mx-auto max-w-7xl px-4 py-10 sm:px-8' => ! auth()->check()])>
    @unless (auth()->check())
        <x-ui.flash-messages />
    @endunless

    <div class="mb-6">
        <h2 class="text-2xl font-semibold">Browse products</h2>
        <p class="mt-1 text-sm text-soil-muted">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }} from approved farmers.</p>
    </div>

    <form method="GET" action="{{ route('customer.products.index') }}" class="glass mb-6 grid gap-4 p-5 sm:grid-cols-3 lg:grid-cols-6">
        <div class="sm:col-span-3 lg:col-span-2">
            <label class="form-label" for="search">Search</label>
            <input id="search" type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="e.g. tomatoes">
        </div>

        <div>
            <label class="form-label" for="category_id">Category</label>
            <select id="category_id" name="category_id" class="form-input">
                <option value="">All</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
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
            <label class="form-label" for="day">Market day</label>
            <select id="day" name="day" class="form-input">
                <option value="">Any</option>
                @foreach ($dayNames as $dayOption)
                    <option value="{{ $dayOption }}" @selected(request('day') === $dayOption)>{{ ucfirst($dayOption) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label" for="sort">Sort by</label>
            <select id="sort" name="sort" class="form-input">
                <option value="newest" @selected(request('sort', 'newest') === 'newest')>Newest</option>
                <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: low to high</option>
                <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: high to low</option>
            </select>
        </div>

        <div>
            <label class="form-label" for="price_min">Min price</label>
            <input id="price_min" type="number" min="0" step="0.01" name="price_min" value="{{ request('price_min') }}" class="form-input">
        </div>

        <div>
            <label class="form-label" for="price_max">Max price</label>
            <input id="price_max" type="number" min="0" step="0.01" name="price_max" value="{{ request('price_max') }}" class="form-input">
        </div>

        <div class="flex items-end">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="in_stock_only" value="1" @checked(request()->boolean('in_stock_only')) class="rounded border-cream-dark text-leaf-500 focus:ring-leaf-400">
                In stock only
            </label>
        </div>

        <div class="flex items-end gap-3 sm:col-span-3 lg:col-span-2 lg:justify-end">
            @if (request()->anyFilled(['search', 'category_id', 'market_id', 'day', 'price_min', 'price_max', 'in_stock_only']))
                <a href="{{ route('customer.products.index') }}" class="btn-glass-outline">Clear filters</a>
            @endif
            <button type="submit" class="btn-glass-orange">Filter</button>
        </div>
    </form>

    @if ($products->isEmpty())
        <div class="glass p-6">
            <x-ui.empty-state icon="tabler:basket-off" title="No products match those filters"
                               message="Try widening your search or clearing a filter." />
        </div>
    @else
        <div class="grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                <x-ui.product-card :product="$product" />
            @endforeach
        </div>

        <div class="mt-8">
            {{ $products->links() }}
        </div>
    @endif
    </div>

</x-dynamic-component>
