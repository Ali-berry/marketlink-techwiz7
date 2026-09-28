@props(['announcements'])

@if ($announcements->isNotEmpty())
    <div class="mb-8 space-y-3">
        @foreach ($announcements as $announcement)
            <div class="flex gap-3 rounded-2xl border border-tomato-100 bg-tomato-50 px-5 py-4">
                <iconify-icon icon="tabler:speakerphone" class="mt-0.5 text-lg text-tomato-600"></iconify-icon>
                <div>
                    <p class="text-sm font-medium text-tomato-900">{{ $announcement->title }}</p>
                    <p class="mt-0.5 text-sm text-tomato-800">{{ $announcement->body }}</p>
                </div>
            </div>
        @endforeach
    </div>
@endif
