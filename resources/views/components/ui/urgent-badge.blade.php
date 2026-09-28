@props(['order'])
{{-- urgent order ka red pill - farmer ko pickup time ek nazar mein dikhe --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700']) }}>
    <iconify-icon icon="tabler:clock-bolt" aria-hidden="true"></iconify-icon>
    Urgent - by {{ $order->urgentPickupTimeText() }}
</span>
