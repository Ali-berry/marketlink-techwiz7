<x-layouts.panel title="Edit announcement">

    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('admin.announcements.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-soil-muted hover:text-soil">
            <iconify-icon icon="tabler:arrow-left"></iconify-icon>
            Back to announcements
        </a>

        <x-ui.confirm-dialog title="Delete this announcement?"
                              body="It will be removed for good."
                              :action="route('admin.announcements.destroy', $announcement)" method="DELETE" confirm-label="Delete">
            <x-slot:trigger>
                <span class="btn-danger cursor-pointer">
                    <iconify-icon icon="tabler:trash"></iconify-icon>
                    Delete
                </span>
            </x-slot:trigger>
        </x-ui.confirm-dialog>
    </div>

    <div class="card max-w-2xl">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">{{ $announcement->title }}</h2>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ $announcement->published_at ? 'bg-leaf-50 text-leaf-800' : 'bg-amber-50 text-amber-800' }}">
                {{ $announcement->published_at ? 'Published' : 'Draft' }}
            </span>
        </div>

        <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}" class="mt-6">
            @csrf
            @method('PUT')
            @include('admin.announcements._form', ['announcement' => $announcement])

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                @if ($announcement->published_at)
                    <button type="submit" name="action" value="unpublish" class="btn-outline">Unpublish</button>
                @else
                    <button type="submit" name="action" value="publish" class="btn-outline">Publish now</button>
                @endif
                <button type="submit" name="action" value="save" class="btn-primary">Save changes</button>
            </div>
        </form>
    </div>

</x-layouts.panel>
