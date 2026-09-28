@props([
    'action',
    'active',
    'icon' => 'tabler:heart',
    'iconActive' => 'tabler:heart-filled',
    'activeLabel' => 'Saved',
    'inactiveLabel' => 'Save',
    // public pages blob background pe hain, wahan solid white outline flat lagti hai
    'glass' => false,
])

{{-- ek hi POST on / off karta hai - "toggle" endpoint na ho to add, ho to remove --}}
<form method="POST" action="{{ $action }}">
    @csrf
    {{ $slot }}
    <button type="submit" @class([
        'btn',
        'bg-tomato-500 text-white hover:bg-tomato-600 focus-visible:ring-tomato-400' => $active,
        'btn-outline' => ! $active && ! $glass,
        'btn-glass-outline' => ! $active && $glass,
    ])>
        <iconify-icon icon="{{ $active ? $iconActive : $icon }}"></iconify-icon>
        {{ $active ? $activeLabel : $inactiveLabel }}
    </button>
</form>
