@props(['name' => 'rating', 'value' => 0])

{{-- star click se hidden rating input set hota hai, hover pe preview --}}
<div x-data="{ rating: {{ (int) $value }}, hoveredStar: 0 }" @mouseleave="hoveredStar = 0"
     class="flex items-center gap-1" role="radiogroup" aria-label="Rating">
    <input type="hidden" name="{{ $name }}" :value="rating">
    @for ($star = 1; $star <= 5; $star++)
        <button type="button" @click="rating = {{ $star }}" @mouseenter="hoveredStar = {{ $star }}"
                class="rounded-full text-2xl transition hover:scale-110 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-tomato-300"
                :class="{{ $star }} <= (hoveredStar || rating) ? 'text-amber-500' : 'text-soil-muted/25'"
                role="radio" :aria-checked="(rating === {{ $star }}).toString()"
                aria-label="Rate {{ $star }} out of 5">
            <iconify-icon icon="tabler:star-filled"></iconify-icon>
        </button>
    @endfor
</div>
