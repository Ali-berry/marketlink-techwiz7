<x-layouts.panel title="My products">

    @unless ($farmer->isApproved())
        <div class="mb-6 flex gap-4 rounded-card border border-amber-200 bg-amber-50 p-5">
            <span class="icon-chip bg-white text-amber-600">
                <iconify-icon icon="tabler:eye-off"></iconify-icon>
            </span>
            <div>
                <p class="font-medium text-amber-900">Customers can't see your products yet</p>
                <p class="mt-1 text-sm text-amber-800">Your stall is still waiting for admin approval. Feel free to set everything up now, it will go live as soon as you're approved.</p>
            </div>
        </div>
    @endunless

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold">My products</h2>
            <p class="mt-1 text-sm text-soil-muted">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }} in total</p>
        </div>

        <div class="flex flex-wrap gap-3">
            <x-ui.confirm-dialog title="Refill stock for every product?"
                                  body="Each product's stock will be set back to its usual weekly amount. Products that were sold out will become available again and customers who asked for a restock alert will be notified."
                                  :action="route('farmer.products.refill-stock')" confirm-label="Refill stock" confirm-class="btn-primary">
                <x-slot:trigger>
                    <span class="btn-outline cursor-pointer">
                        <iconify-icon icon="tabler:refresh"></iconify-icon>
                        Refill weekly stock
                    </span>
                </x-slot:trigger>
            </x-ui.confirm-dialog>

            <a href="{{ route('farmer.products.create') }}" class="btn-primary">
                <iconify-icon icon="tabler:plus"></iconify-icon>
                Add product
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('farmer.products.index') }}" class="card mb-6 grid gap-4 p-5 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label class="form-label" for="search">Search by name</label>
            <input id="search" type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="e.g. tomatoes">
        </div>
        <div>
            <label class="form-label" for="category_id">Category</label>
            <select id="category_id" name="category_id" class="form-input">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="availability">Availability</label>
            <select id="availability" name="availability" class="form-input">
                <option value="">All</option>
                @foreach ($availabilityOptions as $availabilityOption)
                    <option value="{{ $availabilityOption->value }}" @selected(request('availability') === $availabilityOption->value)>
                        {{ $availabilityOption->label() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-4 flex justify-end gap-3">
            @if (request()->anyFilled(['search', 'category_id', 'availability']))
                <a href="{{ route('farmer.products.index') }}" class="btn-outline">Clear filters</a>
            @endif
            <button type="submit" class="btn-primary">Filter</button>
        </div>
    </form>

    @if ($products->isEmpty())
        <div class="card">
            <x-ui.empty-state icon="tabler:carrot" title="No products found"
                               message="Try a different filter, or add your first product.">
                <a href="{{ route('farmer.products.create') }}" class="btn-primary">Add product</a>
            </x-ui.empty-state>
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($products as $product)
                <div class="card overflow-hidden p-0">
                    <div class="relative aspect-[4/3] w-full overflow-hidden">
                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                        <div class="absolute left-3 top-3 flex flex-wrap gap-1.5">
                            <x-ui.availability-badge :availability="$product->availability" />
                            @if ($product->isRunningLow())
                                <span class="inline-flex items-center rounded-full bg-tomato-500 px-3 py-1 text-xs font-medium text-white">Running low</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-5">
                        <p class="font-medium">{{ $product->name }}</p>
                        <p class="mt-0.5 text-sm text-soil-muted">{{ $product->category->name }}</p>

                        <div class="mt-3 flex items-center justify-between text-sm">
                            <x-ui.money :amount="$product->price" class="font-semibold text-leaf-700" />
                            <span class="text-soil-muted">{{ $product->stock_quantity }} {{ $product->unit }} left</span>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @if ($product->availability !== \App\Enums\ProductAvailability::SoldOut)
                                <form method="POST" action="{{ route('farmer.products.update-availability', $product) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="availability" value="sold_out">
                                    <button type="submit" class="btn-outline px-3 py-1.5 text-xs">Mark sold out</button>
                                </form>
                            @endif

                            @if ($product->availability !== \App\Enums\ProductAvailability::TemporarilyUnavailable)
                                <form method="POST" action="{{ route('farmer.products.update-availability', $product) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="availability" value="temporarily_unavailable">
                                    <button type="submit" class="btn-outline px-3 py-1.5 text-xs">Not this week</button>
                                </form>
                            @endif

                            @if ($product->availability !== \App\Enums\ProductAvailability::Available)
                                <form method="POST" action="{{ route('farmer.products.update-availability', $product) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="availability" value="available">
                                    <button type="submit" class="btn-outline px-3 py-1.5 text-xs">Mark available</button>
                                </form>
                            @endif
                        </div>

                        <div class="mt-4 flex items-center justify-between border-t border-cream-dark pt-4">
                            <a href="{{ route('farmer.products.edit', $product) }}" class="text-sm font-medium text-leaf-600 hover:text-leaf-800">Edit</a>

                            <x-ui.confirm-dialog title="Delete this product?"
                                                  body="{{ $product->name }} will be removed from your stall."
                                                  :action="route('farmer.products.destroy', $product)" method="DELETE" confirm-label="Delete product">
                                <x-slot:trigger>
                                    <span class="cursor-pointer text-sm font-medium text-red-600 hover:text-red-800">Delete</span>
                                </x-slot:trigger>
                            </x-ui.confirm-dialog>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $products->links() }}
        </div>
    @endif

</x-layouts.panel>
