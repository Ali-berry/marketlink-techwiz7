<x-layouts.panel title="Moderation">

    <div class="mb-6">
        <h2 class="text-2xl font-semibold">Moderation</h2>
        <p class="mt-1 text-sm text-soil-muted">Hide anything that breaks the rules. Farmers see the reason you give.</p>
    </div>

    <div class="tab-bar">
        <a href="{{ route('admin.moderation.index', ['tab' => 'products']) }}"
           @class(['tab-pill', 'is-active' => $activeTab === 'products'])>
            Products
        </a>
        <a href="{{ route('admin.moderation.index', ['tab' => 'reviews']) }}"
           @class(['tab-pill', 'is-active' => $activeTab === 'reviews'])>
            Reviews
        </a>
    </div>

    <form method="GET" action="{{ route('admin.moderation.index') }}" class="card mb-6 flex flex-wrap items-end gap-4 p-5">
        <input type="hidden" name="tab" value="{{ $activeTab }}">
        <div class="flex-1">
            <label class="form-label" for="search">Search</label>
            <input id="search" type="text" name="search" value="{{ request('search') }}" class="form-input"
                   placeholder="{{ $activeTab === 'products' ? 'Product name' : 'Review comment' }}">
        </div>
        <button type="submit" class="btn-primary">Search</button>
        @if (request()->filled('search'))
            <a href="{{ route('admin.moderation.index', ['tab' => $activeTab]) }}" class="btn-outline">Clear</a>
        @endif
    </form>

    @if ($activeTab === 'products')
        @if ($products->isEmpty())
            <div class="card"><x-ui.empty-state icon="tabler:carrot" title="No products found" /></div>
        @else
            <div class="space-y-4">
                @foreach ($products as $product)
                    <div class="card flex flex-wrap items-center gap-4">
                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="h-14 w-14 rounded-xl object-cover">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ $product->name }}</p>
                            <p class="text-sm text-soil-muted">{{ $product->farmer->stall_name }}</p>
                            @if ($product->is_hidden_by_admin && $product->hidden_reason)
                                <p class="mt-1 text-xs text-red-600">Hidden: {{ $product->hidden_reason }}</p>
                            @endif
                        </div>

                        @if ($product->is_hidden_by_admin)
                            <form method="POST" action="{{ route('admin.moderation.products.unhide', $product) }}">
                                @csrf
                                <button type="submit" class="btn-outline">Unhide</button>
                            </form>
                        @else
                            <x-ui.confirm-dialog title="Hide this product?"
                                                  body="{{ $product->name }} will disappear from customers immediately."
                                                  :action="route('admin.moderation.products.hide', $product)" confirm-label="Hide product">
                                <x-slot:trigger>
                                    <span class="btn-danger cursor-pointer">Hide</span>
                                </x-slot:trigger>
                                <x-slot:fields>
                                    <label class="form-label" for="reason_product_{{ $product->id }}">Reason</label>
                                    <textarea id="reason_product_{{ $product->id }}" name="reason" rows="2" class="form-input" required></textarea>
                                </x-slot:fields>
                            </x-ui.confirm-dialog>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="mt-6">{{ $products->links() }}</div>
        @endif
    @else
        @if ($reviews->isEmpty())
            <div class="card"><x-ui.empty-state icon="tabler:message-star" title="No reviews found" /></div>
        @else
            <div class="space-y-4">
                @foreach ($reviews as $review)
                    <div class="card">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-medium">{{ $review->customer->name }} &rarr; {{ $review->farmer->stall_name }}</p>
                                @if ($review->product)
                                    <p class="text-xs text-soil-muted">About {{ $review->product->name }}</p>
                                @endif
                            </div>
                            <x-ui.star-rating :rating="$review->rating" />
                        </div>

                        @if ($review->comment)
                            <p class="mt-2 text-sm text-soil-muted">{{ $review->comment }}</p>
                        @endif

                        @if ($review->is_hidden_by_admin && $review->hidden_reason)
                            <p class="mt-2 text-xs text-red-600">Hidden: {{ $review->hidden_reason }}</p>
                        @endif

                        <div class="mt-3">
                            @if ($review->is_hidden_by_admin)
                                <form method="POST" action="{{ route('admin.moderation.reviews.unhide', $review) }}">
                                    @csrf
                                    <button type="submit" class="btn-outline">Unhide</button>
                                </form>
                            @else
                                <x-ui.confirm-dialog title="Hide this review?"
                                                      body="It will no longer show to customers."
                                                      :action="route('admin.moderation.reviews.hide', $review)" confirm-label="Hide review">
                                    <x-slot:trigger>
                                        <span class="btn-danger cursor-pointer">Hide</span>
                                    </x-slot:trigger>
                                    <x-slot:fields>
                                        <label class="form-label" for="reason_review_{{ $review->id }}">Reason</label>
                                        <textarea id="reason_review_{{ $review->id }}" name="reason" rows="2" class="form-input" required></textarea>
                                    </x-slot:fields>
                                </x-ui.confirm-dialog>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-6">{{ $reviews->links() }}</div>
        @endif
    @endif

</x-layouts.panel>
