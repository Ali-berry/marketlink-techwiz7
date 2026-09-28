<x-layouts.public>

    {{-- a. hero slider - har slide absolute stacked, home-hero-slider.js har 3 second is-active badalta hai
         aur app.css animate karta hai. Photo load ho rahi ho to dark green background dikhta hai --}}
    <section class="relative isolate flex min-h-[560px] items-center overflow-hidden bg-leaf-900" data-hero-slider>
        <div class="hero-slide is-active absolute inset-0" data-slide>
            <img src="{{ asset('images/site/hero-produce-stall.webp') }}" alt="" class="h-full w-full object-cover">
        </div>
        <div class="hero-slide absolute inset-0" data-slide>
            <img src="{{ asset('images/site/hero-farmer-at-stall.webp') }}" alt="" class="h-full w-full object-cover">
        </div>
        <div class="hero-slide absolute inset-0" data-slide>
            <img src="{{ asset('images/site/hero-market-street.webp') }}" alt="" class="h-full w-full object-cover">
        </div>
        <div class="hero-slide absolute inset-0" data-slide>
            <img src="{{ asset('images/site/hero-farmer-order-list.webp') }}" alt="" class="h-full w-full object-cover">
        </div>
        {{-- dark green gradient, neeche zyada gehra - bright photos pe bhi white heading parhi jaye --}}
        <div class="absolute inset-0 z-[3] bg-[linear-gradient(rgba(15,40,24,0.35),rgba(15,40,24,0.65))]"></div>

        {{-- har slide ki copy image ke saath badalti hai. Charon blocks ek grid cell mein, taake hero ki height na hile --}}
        <div class="relative z-[4] mx-auto grid max-w-3xl px-4 py-24 text-center text-white sm:px-8">
            <div data-slide-content class="hero-copy is-active [grid-area:1/1]">
                <h1 data-slide-part="heading" class="font-display text-4xl font-semibold leading-tight text-white [text-shadow:0_2px_12px_rgba(0,0,0,0.25)] sm:text-5xl">
                    Reserve this week's harvest before it ever reaches the stall.
                </h1>
                <p data-slide-part="text" class="mx-auto mt-5 max-w-xl text-lg text-white/85 [text-shadow:0_2px_12px_rgba(0,0,0,0.25)]">
                    Browse what local farmers are bringing to market, pre-order a pickup slot, and collect it fresh - no early morning queue needed.
                </p>
                <div data-slide-part="buttons" class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="#fresh-this-week" class="btn-glass-orange px-6 py-3 text-base">Browse this week's stock</a>
                    <a href="{{ route('customer.markets.index') }}" class="btn-glass px-6 py-3 text-base">
                        Find a market near you
                    </a>
                </div>
            </div>

            <div data-slide-content class="hero-copy [grid-area:1/1]">
                <h1 data-slide-part="heading" class="font-display text-4xl font-semibold leading-tight text-white [text-shadow:0_2px_12px_rgba(0,0,0,0.25)] sm:text-5xl">
                    See what's fresh this week, before you leave home.
                </h1>
                <p data-slide-part="text" class="mx-auto mt-5 max-w-xl text-lg text-white/85 [text-shadow:0_2px_12px_rgba(0,0,0,0.25)]">
                    Real stock counts from real stalls - browse produce, dairy, honey and bakes from farmers near you.
                </p>
                <div data-slide-part="buttons" class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route('customer.products.index') }}" class="btn-glass-orange px-6 py-3 text-base">Browse products</a>
                    <a href="#how-it-works" class="btn-glass px-6 py-3 text-base">
                        How it works
                    </a>
                </div>
            </div>

            <div data-slide-content class="hero-copy [grid-area:1/1]">
                <h1 data-slide-part="heading" class="font-display text-4xl font-semibold leading-tight text-white [text-shadow:0_2px_12px_rgba(0,0,0,0.25)] sm:text-5xl">
                    Find a farmers market running near you.
                </h1>
                <p data-slide-part="text" class="mx-auto mt-5 max-w-xl text-lg text-white/85 [text-shadow:0_2px_12px_rgba(0,0,0,0.25)]">
                    Weekend bazaars, Sunday harvest markets, weekday stalls - see who's there and when before you go.
                </p>
                <div data-slide-part="buttons" class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route('customer.markets.index') }}" class="btn-glass-orange px-6 py-3 text-base">See all markets</a>
                    <a href="#how-it-works" class="btn-glass px-6 py-3 text-base">
                        How it works
                    </a>
                </div>
            </div>

            <div data-slide-content class="hero-copy [grid-area:1/1]">
                <h1 data-slide-part="heading" class="font-display text-4xl font-semibold leading-tight text-white [text-shadow:0_2px_12px_rgba(0,0,0,0.25)] sm:text-5xl">
                    Selling at a market? Bring your stall online.
                </h1>
                <p data-slide-part="text" class="mx-auto mt-5 max-w-xl text-lg text-white/85 [text-shadow:0_2px_12px_rgba(0,0,0,0.25)]">
                    List what you're bringing each week, let customers reserve it ahead of time, and spend market day serving people who already know what they want.
                </p>
                <div data-slide-part="buttons" class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route('register') }}" class="btn-glass-orange px-6 py-3 text-base">Register as a farmer</a>
                    <a href="#how-it-works" class="btn-glass px-6 py-3 text-base">
                        How it works
                    </a>
                </div>
            </div>
        </div>

        {{-- arrows - gol glass buttons --}}
        <button type="button" data-slide-prev aria-label="Previous slide"
                class="glass absolute left-4 top-1/2 z-[5] hidden h-11 w-11 -translate-y-1/2 items-center justify-center !rounded-full text-white transition hover:scale-105 hover:bg-white/20 sm:flex">
            <iconify-icon icon="tabler:chevron-left" class="text-xl"></iconify-icon>
        </button>
        <button type="button" data-slide-next aria-label="Next slide"
                class="glass absolute right-4 top-1/2 z-[5] hidden h-11 w-11 -translate-y-1/2 items-center justify-center !rounded-full text-white transition hover:scale-105 hover:bg-white/20 sm:flex">
            <iconify-icon icon="tabler:chevron-right" class="text-xl"></iconify-icon>
        </button>

        {{-- dots - glass pill track, active dot slide ke saath bharta hai --}}
        <div class="glass absolute inset-x-0 bottom-6 z-[5] mx-auto flex w-fit items-center gap-2 !rounded-full px-3 py-2" data-slide-dots></div>
    </section>

    {{-- b. stats band - hero ke upar ~40px chadha hua, solid green fill --}}
    <section class="section-solid-green relative z-10 py-14">
        <div class="mx-auto -mt-10 max-w-7xl px-4 sm:px-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-ui.stat-card label="Approved farmers" :value="$platformStats['approvedFarmers']" icon="tabler:tractor" tone="leaf" glass />
                <x-ui.stat-card label="Active markets" :value="$platformStats['activeMarkets']" icon="tabler:map-2" tone="tomato" glass />
                <x-ui.stat-card label="Products available now" :value="$platformStats['productsAvailable']" icon="tabler:basket" tone="amber" glass />
                <x-ui.stat-card label="Pickups completed" :value="$platformStats['pickupsCompleted']" icon="tabler:circle-check" tone="sky" glass />
            </div>
        </div>
    </section>

    {{-- c. categories - bas blob background --}}
    <section id="categories" class="py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="font-display text-3xl font-semibold">Shop by category</h2>
                <p class="mt-2 text-soil-muted">Everything on MarketLink comes straight from the farmer who grew or made it.</p>
            </div>

            <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                @php
                    // hover pe har category ka apna glow - seeded category naam se match, naya column nahi
                    // (spec mein hi "green glow for Vegetables" likha hai)
                    $categoryGlowClass = fn (string $name) => match (true) {
                        str_contains($name, 'Vegetable'), str_contains($name, 'Herb') => 'hover:shadow-[0_8px_32px_rgba(31,77,44,0.35)]',
                        str_contains($name, 'Fruit') => 'hover:shadow-[0_8px_32px_rgba(232,121,47,0.35)]',
                        str_contains($name, 'Dairy') => 'hover:shadow-[0_8px_32px_rgba(59,130,246,0.3)]',
                        str_contains($name, 'Honey') => 'hover:shadow-[0_8px_32px_rgba(217,119,6,0.35)]',
                        default => 'hover:shadow-[0_8px_32px_rgba(31,77,44,0.25)]',
                    };
                @endphp
                @foreach ($categoriesWithCounts as $category)
                    <div class="glass glass-tint-green flex flex-col items-center p-5 text-center transition hover:-translate-y-1.5 {{ $categoryGlowClass($category->name) }}">
                        <span class="glass-icon-chip h-14 w-14 text-2xl text-leaf-600">
                            <iconify-icon icon="{{ $category->icon }}"></iconify-icon>
                        </span>
                        <p class="mt-3 text-sm font-medium text-soil">{{ $category->name }}</p>
                        <p class="mt-1 text-xs text-soil-muted">
                            {{ $category->available_products_count }} {{ \Illuminate\Support\Str::plural('product', $category->available_products_count) }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- d. MarketLink banner - platform ke baare mein green panel, teen glass tags aur "How it works" ka orange link --}}
    <section class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-8">
            <div class="section-solid-green relative overflow-hidden rounded-[28px] px-6 py-10 text-white sm:px-12">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-display text-2xl font-semibold text-white sm:text-3xl">Fresh from the farm, reserved before market day</h2>
                        <p class="mt-3 max-w-xl text-leaf-100">Pick your produce online, choose a pickup slot, and pay the farmer at the stall. No queues, no sold-out surprises.</p>

                        <div class="mt-5 flex flex-wrap gap-2">
                            @foreach (['Reserve ahead', 'Pay at pickup', 'Local farmers only'] as $bannerTag)
                                <span class="glass-dark rounded-full px-3.5 py-1.5 text-xs font-medium text-white">{{ $bannerTag }}</span>
                            @endforeach
                        </div>
                    </div>

                    <a href="#how-it-works" class="inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold text-tomato-300 transition hover:text-tomato-200">
                        See how it works
                        <iconify-icon icon="tabler:arrow-right"></iconify-icon>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- e. Fresh this week - glass cards --}}
    <section id="fresh-this-week" class="py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="max-w-2xl">
                    <h2 class="font-display text-3xl font-semibold">Fresh this week</h2>
                    <p class="mt-2 text-soil-muted">The newest items farmers have added, ready to reserve.</p>
                </div>
                <a href="{{ route('customer.products.index', ['sort' => 'newest']) }}" class="link-orange">
                    View all products
                    <iconify-icon icon="tabler:arrow-right"></iconify-icon>
                </a>
            </div>

            <div class="mt-10 grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($freshProducts as $product)
                    <x-ui.product-card :product="$product" />
                @endforeach
            </div>
        </div>
    </section>

    {{-- f. How it works --}}
    <section id="how-it-works" class="py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="font-display text-3xl font-semibold">How it works</h2>
                <p class="mt-2 text-soil-muted">Three steps between you and fresh produce, no surprises at the stall.</p>
            </div>

            {{-- desktop pe teeno steps ko jodne wali dashed line - sirf decoration, cards ke peeche --}}
            <div class="relative mt-10 grid gap-6 sm:grid-cols-3">
                <div class="absolute inset-x-0 top-12 z-0 hidden border-t-2 border-dashed border-leaf-600/25 sm:block"></div>

                <div class="glass relative z-10 p-6 text-center transition hover:-translate-y-1.5 hover:shadow-xl">
                    <span class="glass-icon-chip mx-auto h-14 w-14 text-2xl text-leaf-600">
                        <iconify-icon icon="tabler:search"></iconify-icon>
                    </span>
                    <p class="mt-4 font-display text-lg font-semibold text-soil">1. Browse</p>
                    <p class="mt-2 text-sm text-soil-muted">See what farmers at your nearest market are bringing this week, with real stock counts.</p>
                </div>
                <div class="glass relative z-10 p-6 text-center transition hover:-translate-y-1.5 hover:shadow-xl">
                    <span class="glass-icon-chip mx-auto h-14 w-14 text-2xl text-tomato-600">
                        <iconify-icon icon="tabler:calendar-time"></iconify-icon>
                    </span>
                    <p class="mt-4 font-display text-lg font-semibold text-soil">2. Pre-order a pickup slot</p>
                    <p class="mt-2 text-sm text-soil-muted">Reserve your items and choose a pickup time so the farmer sets them aside for you.</p>
                </div>
                <div class="glass relative z-10 p-6 text-center transition hover:-translate-y-1.5 hover:shadow-xl">
                    <span class="glass-icon-chip mx-auto h-14 w-14 text-2xl text-sky-700">
                        <iconify-icon icon="tabler:hand-stop"></iconify-icon>
                    </span>
                    <p class="mt-4 font-display text-lg font-semibold text-soil">3. Collect and pay at the stall</p>
                    <p class="mt-2 text-sm text-soil-muted">Walk up at your slot, pay the farmer directly, and take your reserved produce home.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- g. Top farmers this week - TopFarmersThisWeek chunta hai aur poore hafte cache rehta hai --}}
    @if ($topFarmers->isNotEmpty())
        <section class="py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="font-display text-3xl font-semibold">Top farmers this week</h2>
                    <p class="mt-2 text-soil-muted">Picked every week from the stalls with the most completed orders and the best reviews.</p>
                </div>

                <div class="mt-10 grid gap-6 sm:grid-cols-3">
                    @foreach ($topFarmers as $topFarmer)
                        <a href="{{ route('customer.farmers.show', $topFarmer['farmer']) }}"
                           class="glass group block overflow-hidden p-0 transition hover:-translate-y-1 hover:shadow-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-leaf-400">
                            {{-- photo ke gird glass frame, badges upar --}}
                            <div class="relative border-b border-white/25 p-2">
                                <img src="{{ $topFarmer['farmer']->coverImageUrl() }}" alt="{{ $topFarmer['farmer']->stall_name }}" class="h-44 w-full rounded-2xl object-cover object-[center_20%]">

                                <div class="absolute left-4 top-4 flex flex-wrap gap-1.5">
                                    @if ($topFarmer['badge'])
                                        <span class="badge-orange shadow-md">{{ $topFarmer['badge'] }}</span>
                                    @endif
                                    @if ($topFarmer['isNewStall'])
                                        <span class="rounded-full bg-leaf-500 px-3 py-1 text-xs font-semibold text-white shadow-md">New stall</span>
                                    @endif
                                </div>
                            </div>

                            <div class="p-5">
                                <p class="font-display text-lg font-semibold text-soil group-hover:text-leaf-700">{{ $topFarmer['farmer']->stall_name }}</p>
                                <p class="mt-2 text-sm text-soil-muted">{{ \Illuminate\Support\Str::limit($topFarmer['farmer']->bio, 100) }}</p>

                                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-white/30 pt-3 text-sm">
                                    <span class="flex items-center gap-1.5">
                                        @if ($topFarmer['averageRating'])
                                            <x-ui.star-rating :rating="$topFarmer['averageRating']" />
                                            <span class="text-soil-muted"><span class="font-medium text-soil">{{ number_format($topFarmer['averageRating'], 1) }}</span> average rating</span>
                                        @else
                                            <span class="text-soil-muted">No reviews yet</span>
                                        @endif
                                    </span>
                                    @if ($topFarmer['ordersLabel'])
                                        <span class="font-medium text-tomato-600">{{ $topFarmer['ordersLabel'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- h. farmers ke liye green band --}}
    <section class="section-solid-green relative overflow-hidden py-16 text-white">
        <div class="relative mx-auto flex max-w-4xl flex-col items-center gap-6 px-4 text-center sm:px-8">
            <span class="glass-icon-chip h-14 w-14 text-2xl text-white">
                <iconify-icon icon="tabler:tractor"></iconify-icon>
            </span>
            <h2 class="font-display text-3xl font-semibold text-white">Selling at a farmers market? Bring your stall online.</h2>
            <p class="max-w-2xl text-leaf-100">
                List what you're bringing each week, let customers reserve it ahead of time, and spend market day serving people who already know what they want.
            </p>
            <a href="{{ route('register') }}" class="btn-glass-orange px-6 py-3 text-base">Register as a farmer</a>
        </div>
    </section>

    @push('scripts')
        @vite('resources/js/home-hero-slider.js')
    @endpush

</x-layouts.public>
