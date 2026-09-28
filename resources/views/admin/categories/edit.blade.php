<x-layouts.panel title="Edit category">

    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-soil-muted hover:text-soil">
            <iconify-icon icon="tabler:arrow-left"></iconify-icon>
            Back to categories
        </a>

        @if ($category->products()->withTrashed()->exists())
            <span class="text-xs text-soil-muted">Has products - can't be deleted.</span>
        @else
            <x-ui.confirm-dialog title="Delete this category?"
                                  body="{{ $category->name }} will be permanently removed."
                                  :action="route('admin.categories.destroy', $category)" method="DELETE" confirm-label="Delete category">
                <x-slot:trigger>
                    <span class="btn-danger cursor-pointer">
                        <iconify-icon icon="tabler:trash"></iconify-icon>
                        Delete
                    </span>
                </x-slot:trigger>
            </x-ui.confirm-dialog>
        @endif
    </div>

    <div class="card max-w-xl">
        <h2 class="text-lg font-semibold">{{ $category->name }}</h2>

        <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="mt-6">
            @csrf
            @method('PUT')
            @include('admin.categories._form', ['category' => $category])

            <div class="mt-6 flex justify-end">
                <button type="submit" class="btn-primary">
                    <iconify-icon icon="tabler:check"></iconify-icon>
                    Save changes
                </button>
            </div>
        </form>
    </div>

</x-layouts.panel>
