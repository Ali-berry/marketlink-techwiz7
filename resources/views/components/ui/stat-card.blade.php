@props(['label', 'value', 'icon', 'tone' => 'leaf', 'hint' => null, 'glass' => false])

@php
    // do look: glass="true" home page ka green stats band (light text), default panel dashboards ka .card.
    // icon chip dono mein glass, tone sirf icon ka rang badalta hai
    $iconColour = [
        'leaf' => 'text-leaf-600',
        'tomato' => 'text-tomato-600',
        'amber' => 'text-amber-700',
        'sky' => 'text-sky-700',
    ][$tone];
@endphp

<div @class([
    'flex items-start gap-4 p-5 transition',
    'glass hover:-translate-y-1 hover:shadow-xl' => $glass,
    'card hover:-translate-y-0.5 hover:shadow-xl' => ! $glass,
])>
    <span class="glass-icon-chip {{ $iconColour }}">
        <iconify-icon icon="{{ $icon }}"></iconify-icon>
    </span>
    <div class="min-w-0">
        {{-- glass mode sirf green stats band pe aata hai - light label aur halka orange number (tomato-600 green pe muddy) --}}
        <p @class(['text-sm', 'text-white/85' => $glass, 'text-soil-muted' => ! $glass])>{{ $label }}</p>
        <p @class(['mt-1 font-display font-semibold', 'text-3xl text-tomato-300' => $glass, 'text-2xl text-tomato-600' => ! $glass])>{{ $value }}</p>
        @if ($hint)
            <p @class(['mt-1 text-xs', 'text-white/70' => $glass, 'text-soil-muted' => ! $glass])>{{ $hint }}</p>
        @endif
    </div>
</div>
