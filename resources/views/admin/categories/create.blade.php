<x-layouts.panel title="Add category">

    <div class="mb-6">
        <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-soil-muted hover:text-soil">
            <iconify-icon icon="tabler:arrow-left"></iconify-icon>
            Back to categories
        </a>
    </div>

    <div class="card max-w-xl">
        <h2 class="text-lg font-semibold">Add a category</h2>

        <form method="POST" action="{{ route('admin.categories.store') }}" class="mt-6">
            @csrf
            @include('admin.categories._form', ['category' => $category])

            <div class="mt-6 flex justify-end">
                <button type="submit" class="btn-primary">
                    <iconify-icon icon="tabler:check"></iconify-icon>
                    Save category
                </button>
            </div>
        </form>
    </div>

</x-layouts.panel>
