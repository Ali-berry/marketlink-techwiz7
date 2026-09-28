@props(['status'])
<span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ $status->badgeClasses() }}">
    {{ $status->label() }}
</span>
