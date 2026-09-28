<x-layouts.panel title="Add product">

    <div class="mb-6">
        <a href="{{ route('farmer.products.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-soil-muted hover:text-soil">
            <iconify-icon icon="tabler:arrow-left"></iconify-icon>
            Back to products
        </a>
    </div>

    <div class="card max-w-3xl">
        <h2 class="text-lg font-semibold">Add a product</h2>
        <p class="mt-1 text-sm text-soil-muted">This shows to customers once saved, as long as your stall is approved.</p>

        <form method="POST" action="{{ route('farmer.products.store') }}" enctype="multipart/form-data" class="mt-6">
            @csrf
            @include('farmer.products._form', ['product' => $product, 'categories' => $categories])

            <div class="mt-6 flex justify-end">
                <button type="submit" class="btn-primary">
                    <iconify-icon icon="tabler:check"></iconify-icon>
                    Save product
                </button>
            </div>
        </form>
    </div>

</x-layouts.panel>
