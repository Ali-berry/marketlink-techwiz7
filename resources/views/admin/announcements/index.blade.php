<x-layouts.panel title="Announcements">

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold">Announcements</h2>
            <p class="mt-1 text-sm text-soil-muted">Published ones show as a banner on the matching dashboards.</p>
        </div>
        <a href="{{ route('admin.announcements.create') }}" class="btn-primary">
            <iconify-icon icon="tabler:plus"></iconify-icon>
            New announcement
        </a>
    </div>

    <form method="GET" action="{{ route('admin.announcements.index') }}" class="card mb-6 flex flex-wrap items-end gap-4 p-5">
        <div class="flex-1">
            <label class="form-label" for="search">Search by title</label>
            <input id="search" type="text" name="search" value="{{ request('search') }}" class="form-input">
        </div>
        <button type="submit" class="btn-primary">Search</button>
        @if (request()->filled('search'))
            <a href="{{ route('admin.announcements.index') }}" class="btn-outline">Clear</a>
        @endif
    </form>

    @if ($announcements->isEmpty())
        <div class="card">
            <x-ui.empty-state icon="tabler:speakerphone" title="No announcements yet">
                <a href="{{ route('admin.announcements.create') }}" class="btn-primary">New announcement</a>
            </x-ui.empty-state>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($announcements as $announcement)
                <a href="{{ route('admin.announcements.edit', $announcement) }}" class="card flex flex-wrap items-start justify-between gap-3 hover:border-leaf-200">
                    <div class="min-w-0">
                        <p class="font-medium">{{ $announcement->title }}</p>
                        <p class="mt-1 truncate text-sm text-soil-muted">{{ $announcement->body }}</p>
                        <p class="mt-1 text-xs text-soil-muted">
                            {{ ucfirst($announcement->audience) }} &middot; by {{ $announcement->author->name ?? 'Deleted user' }}
                        </p>
                    </div>
                    <span class="shrink-0 inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ $announcement->published_at ? 'bg-leaf-50 text-leaf-800' : 'bg-amber-50 text-amber-800' }}">
                        {{ $announcement->published_at ? 'Published' : 'Draft' }}
                    </span>
                </a>
            @endforeach
        </div>

        <div class="mt-6">{{ $announcements->links() }}</div>
    @endif

</x-layouts.panel>
