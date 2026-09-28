{{-- card ke andar khulne wala review form. $order, $product (farmer review pe null), $existingReview (naye pe null)
     aur $formKey (page ek waqt mein ek form khula rakhta hai) --}}
@php
    // old input sirf usi form ka jo submit hua tha
    $isFormThatFailed = old('order_id') == $order->id && old('product_id') == ($product?->id);
    $startingRating = $isFormThatFailed ? (int) old('rating') : ($existingReview->rating ?? 0);
    $startingComment = $isFormThatFailed ? old('comment') : ($existingReview->comment ?? '');
@endphp

<form x-show="openForm === '{{ $formKey }}'" x-cloak x-transition.opacity
      method="POST" action="{{ route('customer.reviews.store') }}"
      class="mt-3 space-y-3 rounded-xl border border-white/70 bg-white/50 p-4">
    @csrf
    <input type="hidden" name="order_id" value="{{ $order->id }}">
    @if ($product)
        <input type="hidden" name="product_id" value="{{ $product->id }}">
    @endif

    <div>
        <p class="text-sm font-medium text-soil">Your rating</p>
        <x-ui.star-picker :value="$startingRating" />
        @if ($isFormThatFailed)
            <x-input-error :messages="$errors->get('rating')" class="mt-1" />
        @endif
    </div>

    <div>
        <label class="form-label" for="comment-{{ $formKey }}">Comment <span class="font-normal text-soil-muted">(optional)</span></label>
        <textarea id="comment-{{ $formKey }}" name="comment" rows="2" class="form-input"
                  placeholder="What did you think?">{{ $startingComment }}</textarea>
        @if ($isFormThatFailed)
            <x-input-error :messages="$errors->get('comment')" class="mt-1" />
        @endif
    </div>

    <div class="flex items-center justify-end gap-2">
        <button type="button" class="btn-outline px-4 py-1.5 text-xs" @click="openForm = null">Cancel</button>
        <button type="submit" class="btn-glass-orange px-4 py-1.5 text-xs">
            {{ $existingReview ? 'Update review' : 'Submit review' }}
        </button>
    </div>
</form>
