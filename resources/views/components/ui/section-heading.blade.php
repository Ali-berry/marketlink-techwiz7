@props(['title', 'linkText' => null, 'linkRoute' => null])

<div class="mb-4 flex items-center justify-between gap-4">
    <h2 class="text-base font-semibold">{{ $title }}</h2>
    @if ($linkText && $linkRoute && Route::has($linkRoute))
        <a href="{{ route($linkRoute) }}" class="text-sm font-medium text-leaf-600 hover:text-leaf-800">{{ $linkText }}</a>
    @endif
</div>
