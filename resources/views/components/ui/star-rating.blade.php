@props(['rating'])

<div {{ $attributes->merge(['class' => 'flex text-amber-500']) }}>
    @for ($star = 1; $star <= 5; $star++)
        <iconify-icon icon="{{ $star <= round($rating) ? 'tabler:star-filled' : 'tabler:star' }}"></iconify-icon>
    @endfor
</div>
