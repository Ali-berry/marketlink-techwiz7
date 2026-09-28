@php
    // completed order mein kya kya review ho sakta hai: farmer, aur har product. Har ek ka review baqi hai ya ho chuka
    $reviewTargets = $reviewableOrders->flatMap(function ($order) use ($existingReviews) {
        $orderReviews = $existingReviews->get($order->id, collect());

        $farmerTarget = ['order' => $order, 'product' => null, 'review' => $orderReviews->firstWhere('product_id', null)];

        $productTargets = $order->items->whereNotNull('product_id')->unique('product_id')->filter(fn ($item) => $item->product)
            ->map(fn ($item) => ['order' => $order, 'product' => $item->product, 'review' => $orderReviews->firstWhere('product_id', $item->product_id)]);

        return collect([$farmerTarget])->concat($productTargets);
    });

    $stillToReviewByOrder = $reviewTargets->whereNull('review')->groupBy(fn ($target) => $target['order']->id);
    $writtenReviews = $reviewTargets->whereNotNull('review')->sortByDesc(fn ($target) => $target['review']->updated_at)->values();

    $activeTab = request('tab') === 'written' ? 'written' : 'to-review';
    $tabLabels = [
        'to-review' => 'To review ('.$stillToReviewByOrder->flatten(1)->count().')',
        'written' => 'My reviews ('.$writtenReviews->count().')',
    ];

    // validation fail wala form dobara khulta hai taake error dikhe
    $formKeyFor = fn ($order, $product) => 'order-'.$order->id.'-'.($product ? 'product-'.$product->id : 'farmer');
    $failedFormKey = old('order_id') ? 'order-'.old('order_id').'-'.(old('product_id') ? 'product-'.old('product_id') : 'farmer') : null;
@endphp

<x-layouts.panel title="My reviews">

    <div class="mb-6">
        <h2 class="text-2xl font-semibold">My reviews</h2>
        <p class="mt-1 text-sm text-soil-muted">Review the farmer and each product once an order is completed.</p>
    </div>

    <div class="tab-bar">
        @foreach ($tabLabels as $tabKey => $tabLabel)
            <a href="{{ route('customer.reviews.index', ['tab' => $tabKey === 'written' ? 'written' : null]) }}"
               @class(['tab-pill', 'is-active' => $activeTab === $tabKey])>{{ $tabLabel }}</a>
        @endforeach
    </div>

    {{-- poore page pe ek waqt mein ek form khula --}}
    <div x-data="{ openForm: @js($failedFormKey) }">

        @if ($activeTab === 'to-review')
            @if ($stillToReviewByOrder->isEmpty())
                <div class="card">
                    <x-ui.empty-state icon="tabler:star" title="You're all caught up"
                                       message="When an order is completed, the farmer and its products will show up here to review." />
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($stillToReviewByOrder as $targetsForOrder)
                        @php $order = $targetsForOrder->first()['order']; @endphp

                        <section class="card p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $order->farmer->coverImageUrl() }}" alt="{{ $order->farmer->stall_name }}"
                                         class="h-11 w-11 rounded-full border-2 border-white/70 object-cover object-[center_20%]">
                                    <div>
                                        <p class="font-medium text-soil">{{ $order->farmer->stall_name }}</p>
                                        <p class="text-xs text-soil-muted">{{ $order->order_number }} - picked up {{ $order->pickup_date->format('D j M Y') }}</p>
                                    </div>
                                </div>
                                <span class="rounded-full bg-tomato-500/10 px-3 py-1 text-xs font-medium text-tomato-700">
                                    {{ $targetsForOrder->count() }} to review
                                </span>
                            </div>

                            <ul class="mt-4 divide-y divide-white/60">
                                @foreach ($targetsForOrder as $target)
                                    @php $formKey = $formKeyFor($target['order'], $target['product']); @endphp
                                    <li class="py-3 first:pt-0 last:pb-0">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="flex items-center gap-2 text-sm text-soil">
                                                <iconify-icon icon="{{ $target['product'] ? 'tabler:basket' : 'tabler:building-store' }}" class="text-soil-muted"></iconify-icon>
                                                {{ $target['product'] ? $target['product']->name : 'The stall overall' }}
                                            </p>
                                            <button type="button" class="btn-outline shrink-0 px-4 py-1.5 text-xs"
                                                    x-show="openForm !== '{{ $formKey }}'" @click="openForm = '{{ $formKey }}'">
                                                <iconify-icon icon="tabler:pencil"></iconify-icon>
                                                Write review
                                            </button>
                                        </div>

                                        @include('customer.reviews._review-form', [
                                            'order' => $target['order'],
                                            'product' => $target['product'],
                                            'existingReview' => null,
                                            'formKey' => $formKey,
                                        ])
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                </div>
            @endif
        @else
            @if ($writtenReviews->isEmpty())
                <div class="card">
                    <x-ui.empty-state icon="tabler:message-star" title="No reviews yet"
                                       message="Reviews you write will be listed here, along with any replies from farmers." />
                </div>
            @else
                <div class="grid gap-4 lg:grid-cols-2">
                    @foreach ($writtenReviews as $target)
                        @php
                            $review = $target['review'];
                            $formKey = $formKeyFor($target['order'], $target['product']);
                        @endphp

                        <article class="card p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-medium text-soil">{{ $target['product'] ? $target['product']->name : $target['order']->farmer->stall_name }}</p>
                                    <p class="text-xs text-soil-muted">
                                        {{ $target['product'] ? $target['order']->farmer->stall_name.' - ' : 'Stall review - ' }}{{ $review->updated_at->format('j M Y') }}
                                    </p>
                                </div>
                                <x-ui.star-rating :rating="$review->rating" />
                            </div>

                            @if ($review->comment)
                                <p class="mt-3 text-sm text-soil">{{ $review->comment }}</p>
                            @endif

                            @if ($review->farmer_reply)
                                <div class="mt-3 rounded-xl border border-white/70 bg-white/50 p-3">
                                    <p class="text-xs font-medium text-leaf-700">Reply from {{ $target['order']->farmer->stall_name }}</p>
                                    <p class="mt-1 text-sm text-soil">{{ $review->farmer_reply }}</p>
                                </div>
                            @endif

                            <div class="mt-3 flex justify-end" x-show="openForm !== '{{ $formKey }}'">
                                <button type="button" class="btn-outline px-4 py-1.5 text-xs" @click="openForm = '{{ $formKey }}'">
                                    <iconify-icon icon="tabler:pencil"></iconify-icon>
                                    Edit
                                </button>
                            </div>

                            @include('customer.reviews._review-form', [
                                'order' => $target['order'],
                                'product' => $target['product'],
                                'existingReview' => $review,
                                'formKey' => $formKey,
                            ])
                        </article>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

</x-layouts.panel>
