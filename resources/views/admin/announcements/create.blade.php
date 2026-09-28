<x-layouts.panel title="New announcement">

    <div class="mb-6">
        <a href="{{ route('admin.announcements.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-soil-muted hover:text-soil">
            <iconify-icon icon="tabler:arrow-left"></iconify-icon>
            Back to announcements
        </a>
    </div>

    <div class="card max-w-2xl">
        <h2 class="text-lg font-semibold">New announcement</h2>

        <form method="POST" action="{{ route('admin.announcements.store') }}" class="mt-6">
            @csrf
            @include('admin.announcements._form', ['announcement' => $announcement])

            <div class="mt-6 flex justify-end gap-3">
                <button type="submit" name="action" value="draft" class="btn-outline">Save as draft</button>
                <button type="submit" name="action" value="publish" class="btn-primary">Publish now</button>
            </div>
        </form>
    </div>

</x-layouts.panel>
