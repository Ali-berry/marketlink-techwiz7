<x-layouts.panel title="Categories">

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold">Categories</h2>
            <p class="mt-1 text-sm text-soil-muted">Shown on the home page and the product filters, in this order.</p>
        </div>
        <a href="{{ route('admin.categories.create') }}" class="btn-primary">
            <iconify-icon icon="tabler:plus"></iconify-icon>
            Add category
        </a>
    </div>

    @if ($categories->isEmpty())
        <div class="card">
            <x-ui.empty-state icon="tabler:category" title="No categories yet">
                <a href="{{ route('admin.categories.create') }}" class="btn-primary">Add category</a>
            </x-ui.empty-state>
        </div>
    @else
        <div class="card overflow-x-auto">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Icon</th>
                        <th>Name</th>
                        <th>Products</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td class="text-soil-muted">{{ $category->sort_order }}</td>
                            <td>
                                <span class="icon-chip h-9 w-9 bg-leaf-50 text-lg text-leaf-600">
                                    <iconify-icon icon="{{ $category->icon }}"></iconify-icon>
                                </span>
                            </td>
                            <td class="font-medium">{{ $category->name }}</td>
                            <td class="text-soil-muted">{{ $category->products_count }}</td>
                            <td>
                                <a href="{{ route('admin.categories.edit', $category) }}" class="text-sm font-medium text-leaf-600 hover:text-leaf-800">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</x-layouts.panel>
