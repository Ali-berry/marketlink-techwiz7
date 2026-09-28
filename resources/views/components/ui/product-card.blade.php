@props(['product', 'showFarmer' => true])

{{-- har jagah yahi product card. Farmer ke apne page pe farmer ka naam repeat hota, wahan category dikhate hain --}}
<a href="{{ route('customer.products.show', $product) }}"
   {{ $attributes->class(['glass glass-tint-green group block overflow-hidden p-0 transition hover:-translate-y-1 hover:shadow-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-leaf-400']) }}>
    <div class="relative aspect-[4/3] w-full overflow-hidden">
        <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        {{-- photo ke upar halki rangeen glass film, photo neeche dikhti rehti hai --}}
        <div class="pointer-events-none absolute inset-0 bg-leaf-900/10"></div>

        @if ($product->availability === \App\Enums\ProductAvailability::SoldOut || $product->stock_quantity <= 0)
            <span class="absolute left-3 top-3 rounded-full bg-soil/80 px-3 py-1 text-xs font-medium text-white shadow-md">Sold out</span>
        @elseif ($product->isRunningLow())
            <span class="badge-orange absolute left-3 top-3 shadow-md">Only {{ $product->stock_quantity }} left</span>
        @endif
    </div>

    <div class="p-4">
        <p class="truncate font-medium text-soil">{{ $product->name }}</p>
        <p class="mt-0.5 truncate text-sm text-soil-muted">
            {{ $showFarmer ? $product->farmer->stall_name : $product->category->name }}
        </p>
        <p class="mt-2 font-display text-lg font-semibold text-leaf-700">
            <x-ui.money :amount="$product->price" />
            <span class="text-sm font-normal text-soil-muted">/ {{ $product->unit }}</span>
        </p>
    </div>
</a>
