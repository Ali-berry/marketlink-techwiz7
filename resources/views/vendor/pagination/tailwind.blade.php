{{-- MarketLink's own pagination markup, swapped in for Laravel's default indigo/gray
     Tailwind theme so every paginated list matches the leaf/cream design system. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <p class="text-sm text-soil-muted">
            {{ __('Showing') }}
            <span class="font-medium text-soil">{{ $paginator->firstItem() ?? $paginator->count() }}</span>
            {{ __('to') }}
            <span class="font-medium text-soil">{{ $paginator->lastItem() ?? $paginator->count() }}</span>
            {{ __('of') }}
            <span class="font-medium text-soil">{{ $paginator->total() }}</span>
            {{ __('results') }}
        </p>

        <div class="flex items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="btn-outline cursor-not-allowed px-3 py-1.5 text-xs opacity-50" aria-disabled="true">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-outline px-3 py-1.5 text-xs">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-sm text-soil-muted" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-leaf-500 text-xs font-semibold text-white">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="inline-flex h-8 w-8 items-center justify-center rounded-full text-xs font-medium text-soil-muted hover:bg-cream hover:text-soil"
                               aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-outline px-3 py-1.5 text-xs">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="btn-outline cursor-not-allowed px-3 py-1.5 text-xs opacity-50" aria-disabled="true">
                    {!! __('pagination.next') !!}
                </span>
            @endif
        </div>
    </nav>
@endif
