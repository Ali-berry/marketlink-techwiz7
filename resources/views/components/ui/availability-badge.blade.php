@props(['availability'])
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium '.$availability->badgeClasses()]) }}>
    {{ $availability->label() }}
</span>
