<x-layouts.panel title="Edit product">

    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('farmer.products.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-soil-muted hover:text-soil">
            <iconify-icon icon="tabler:arrow-left"></iconify-icon>
            Back to products
        </a>

        <x-ui.confirm-dialog title="Delete this product?"
                              body="{{ $product->name }} will be removed from your stall. Past orders that included it are not affected."
                              :action="route('farmer.products.destroy', $product)" method="DELETE" confirm-label="Delete product">
            <x-slot:trigger>
                <span class="btn-danger cursor-pointer">
                    <iconify-icon icon="tabler:trash"></iconify-icon>
                    Delete
                </span>
            </x-slot:trigger>
        </x-ui.confirm-dialog>
    </div>

    <div class="card max-w-3xl">
        <h2 class="text-lg font-semibold">{{ $product->name }}</h2>
        <p class="mt-1 text-sm text-soil-muted">Update the details below and save.</p>

        <form method="POST" action="{{ route('farmer.products.update', $product) }}" enctype="multipart/form-data" class="mt-6">
            @csrf
            @method('PUT')
            @include('farmer.products._form', ['product' => $product, 'categories' => $categories])

            <div class="mt-6 flex justify-end">
                <button type="submit" class="btn-primary">
                    <iconify-icon icon="tabler:check"></iconify-icon>
                    Save changes
                </button>
            </div>
        </form>
    </div>

</x-layouts.panel>
