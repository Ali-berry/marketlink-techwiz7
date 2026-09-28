@props(['routeName', 'icon', 'badge' => null])

{{-- current page pe green. Route abhi bana na ho to crash ki jagah grey dikhta hai --}}
@if (Route::has($routeName))
    @php
        // "farmer.products.index" "farmer.products.edit" pe bhi active rahe
        $routeGroup = \Illuminate\Support\Str::beforeLast($routeName, '.');
        $isCurrentPage = request()->routeIs($routeName) || (str_ends_with($routeName, '.index') && request()->routeIs($routeGroup . '.*'));
    @endphp

    <a href="{{ route($routeName) }}"
       @class([
           'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition',
           'bg-leaf-500/15 font-medium text-leaf-800 shadow-sm' => $isCurrentPage,
           'text-soil-muted hover:bg-white/70 hover:text-soil' => ! $isCurrentPage,
       ])>
        <iconify-icon icon="{{ $icon }}" class="text-lg"></iconify-icon>
        {{ $slot }}
        @if ($badge)
            <span class="ml-auto rounded-full bg-tomato-500 px-2 py-0.5 text-xs font-semibold text-white">{{ $badge }}</span>
        @endif
    </a>
@else
    <span class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-soil-muted/50">
        <iconify-icon icon="{{ $icon }}" class="text-lg"></iconify-icon>
        {{ $slot }}
    </span>
@endif
