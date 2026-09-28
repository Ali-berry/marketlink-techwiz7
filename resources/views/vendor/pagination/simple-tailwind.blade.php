{{-- Same leaf/cream theme as tailwind.blade.php, for lists using simplePaginate() --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between gap-2">
        @if ($paginator->onFirstPage())
            <span class="btn-outline cursor-not-allowed px-3 py-1.5 text-xs opacity-50" aria-disabled="true">
                {!! __('pagination.previous') !!}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-outline px-3 py-1.5 text-xs">
                {!! __('pagination.previous') !!}
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-outline px-3 py-1.5 text-xs">
                {!! __('pagination.next') !!}
            </a>
        @else
            <span class="btn-outline cursor-not-allowed px-3 py-1.5 text-xs opacity-50" aria-disabled="true">
                {!! __('pagination.next') !!}
            </span>
        @endif
    </nav>
@endif
