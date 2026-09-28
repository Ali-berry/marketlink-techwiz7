@props(['title', 'links'])

{{-- footer column - link ['label', 'href'] ya ['label', 'route']. Route na bana ho to grey text --}}
<div>
    <p class="text-sm font-semibold text-white">{{ $title }}</p>
    <ul class="mt-4 space-y-2.5 text-sm text-white/70">
        @foreach ($links as $link)
            <li>
                @if (isset($link['route']))
                    @if (Route::has($link['route']))
                        <a href="{{ route($link['route']) }}" class="hover:text-white">{{ $link['label'] }}</a>
                    @else
                        <span class="text-white/40">{{ $link['label'] }}</span>
                    @endif
                @else
                    <a href="{{ $link['href'] }}" class="hover:text-white">{{ $link['label'] }}</a>
                @endif
            </li>
        @endforeach
    </ul>
</div>
