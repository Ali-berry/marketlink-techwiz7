<x-layouts.panel title="Reviews">

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold">Reviews</h2>
            <p class="mt-1 text-sm text-soil-muted">What customers are saying about your stall and products.</p>
        </div>
        <div class="card flex items-center gap-3 px-5 py-3">
            <span class="icon-chip bg-amber-50 text-amber-600">
                <iconify-icon icon="tabler:star-filled"></iconify-icon>
            </span>
            <div>
                <p class="font-display text-xl font-semibold">{{ $farmer->averageRating() ?? '—' }}</p>
                <p class="text-xs text-soil-muted">average rating</p>
            </div>
        </div>
    </div>

    @if ($reviews->isEmpty())
        <div class="card">
            <x-ui.empty-state icon="tabler:message-star" title="No reviews yet" message="Reviews from customers will show up here." />
        </div>
    @else
        <div class="space-y-5">
            @foreach ($reviews as $review)
                <div class="card" x-data="{ replying: {{ $review->farmer_reply ? 'false' : 'true' }} }">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-medium">{{ $review->customer->name }}</p>
                            <p class="text-xs text-soil-muted">
                                {{ $review->created_at->format('D j M Y') }}
                                @if ($review->isAboutProduct())
                                    &middot; about {{ $review->product->name ?? 'a product' }}
                                @endif
                            </p>
                        </div>
                        <div class="flex text-amber-500">
                            @for ($star = 1; $star <= 5; $star++)
                                <iconify-icon icon="{{ $star <= $review->rating ? 'tabler:star-filled' : 'tabler:star' }}"></iconify-icon>
                            @endfor
                        </div>
                    </div>

                    @if ($review->comment)
                        <p class="mt-3 text-sm text-soil-muted">{{ $review->comment }}</p>
                    @endif

                    <div class="mt-4 border-t border-cream-dark pt-4">
                        @if ($review->farmer_reply)
                            <div x-show="! replying">
                                <p class="text-xs font-medium text-soil-muted">Your reply</p>
                                <p class="mt-1 text-sm">{{ $review->farmer_reply }}</p>
                                <button type="button" class="mt-2 text-xs font-medium text-leaf-600 hover:text-leaf-800" @click="replying = true">
                                    Edit reply
                                </button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('farmer.reviews.reply', $review) }}"
                              x-show="replying" x-cloak class="{{ $review->farmer_reply ? 'mt-3' : '' }}">
                            @csrf
                            <label class="form-label" for="farmer_reply_{{ $review->id }}">
                                {{ $review->farmer_reply ? 'Edit your reply' : 'Reply to this review' }}
                            </label>
                            <textarea id="farmer_reply_{{ $review->id }}" name="farmer_reply" rows="2" class="form-input">{{ old('farmer_reply', $review->farmer_reply) }}</textarea>

                            <div class="mt-2 flex justify-end gap-2">
                                @if ($review->farmer_reply)
                                    <button type="button" class="btn-outline px-3 py-1.5 text-xs" @click="replying = false">Cancel</button>
                                @endif
                                <button type="submit" class="btn-primary px-3 py-1.5 text-xs">Save reply</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $reviews->links() }}
        </div>
    @endif

</x-layouts.panel>
