@props(['icon' => 'tabler:basket', 'title', 'message' => null])

<div class="flex flex-col items-center px-6 py-10 text-center">
    <span class="glass-icon-chip mb-4 h-14 w-14 text-2xl text-leaf-600">
        <iconify-icon icon="{{ $icon }}"></iconify-icon>
    </span>
    <p class="font-medium">{{ $title }}</p>
    @if ($message)
        <p class="mt-1 max-w-sm text-sm text-soil-muted">{{ $message }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
