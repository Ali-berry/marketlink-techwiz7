@php
    // public pages sab ke liye. Doosra column dekhne wale pe depend: guest ko "For farmers",
    // logged in user ko sirf apne role ke panel links - kisi doosre role ke pages kabhi nahi
    $exploreLinks = [
        ['label' => 'Home', 'href' => route('home')],
        ['label' => 'Markets', 'href' => route('customer.markets.index')],
        ['label' => 'Browse products', 'href' => route('customer.products.index')],
        ['label' => 'Community', 'href' => route('community.index')],
        ['label' => 'About', 'href' => route('about')],
        ['label' => 'Contact', 'href' => route('contact')],
    ];

    $signedInUser = auth()->user();

    [$roleColumnTitle, $roleColumnLinks] = match ($signedInUser?->role) {
        \App\Enums\UserRole::Customer => ['Your account', [
            ['label' => 'Dashboard', 'route' => 'customer.dashboard'],
            ['label' => 'My basket', 'route' => 'customer.cart.index'],
            ['label' => 'My orders', 'route' => 'customer.orders.index'],
            ['label' => 'Favourites', 'route' => 'customer.favourites.index'],
            ['label' => 'My reviews', 'route' => 'customer.reviews.index'],
        ]],
        \App\Enums\UserRole::Farmer => ['Your stall', [
            ['label' => 'Dashboard', 'route' => 'farmer.dashboard'],
            ['label' => 'Pre-orders', 'route' => 'farmer.orders.index'],
            ['label' => 'My products', 'route' => 'farmer.products.index'],
            ['label' => 'Pickup slots', 'route' => 'farmer.pickup-windows.index'],
            ['label' => 'Reviews', 'route' => 'farmer.reviews.index'],
            ['label' => 'Sales insights', 'route' => 'farmer.insights.index'],
            ['label' => 'Stall profile', 'route' => 'farmer.stall.edit'],
        ]],
        // admin tiers ki permissions alag hain, is liye sirf wahi pages jo ye admin khol sakta hai - 403 links nahi
        \App\Enums\UserRole::Admin => ['Admin', collect([
            ['label' => 'Dashboard', 'route' => 'admin.dashboard'],
            ['label' => 'Farmers', 'route' => 'admin.farmers.index', 'permission' => 'manage-farmers'],
            ['label' => 'Customers', 'route' => 'admin.customers.index', 'permission' => 'manage-customers'],
            ['label' => 'Markets', 'route' => 'admin.markets.index', 'permission' => 'manage-markets'],
            ['label' => 'Categories', 'route' => 'admin.categories.index', 'permission' => 'manage-categories'],
            ['label' => 'Moderation', 'route' => 'admin.moderation.index', 'permission' => 'moderate-content'],
            ['label' => 'Community posts', 'route' => 'admin.community.index', 'permission' => 'moderate-community-posts'],
            ['label' => 'Reports', 'route' => 'admin.reports.index', 'permission' => 'view-reports'],
            ['label' => 'Announcements', 'route' => 'admin.announcements.index', 'permission' => 'manage-announcements'],
        ])->filter(fn (array $adminLink) => ! isset($adminLink['permission']) || $signedInUser->can($adminLink['permission']))->values()->all()],
        default => ['For farmers', [
            ['label' => 'Register as a farmer', 'href' => route('register', ['account_type' => 'farmer'])],
            ['label' => 'How it works', 'href' => route('home').'#how-it-works'],
        ]],
    };
@endphp

{{-- solid green footer, upar .glass-dark ka frost - white text poora parha jaye --}}
<footer class="section-solid-green px-4 py-14 text-white sm:px-8">
    <div class="glass-dark mx-auto max-w-7xl overflow-hidden p-8 sm:p-12">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr]">
            <div>
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="icon-chip h-9 w-9 bg-leaf-500 text-lg text-white">
                        <iconify-icon icon="tabler:plant-2"></iconify-icon>
                    </span>
                    <span class="font-display text-xl font-semibold text-white">MarketLink</span>
                </a>
                <p class="mt-4 max-w-xs text-sm text-white/70">
                    MarketLink connects local farmers with customers who want to reserve fresh produce
                    ahead of market day, so nothing sits unsold and nothing is bought sight unseen.
                </p>

                <form class="mt-6 flex max-w-xs items-center gap-2" onsubmit="return false">
                    <label for="footer-email" class="sr-only">Email</label>
                    <input id="footer-email" type="email" placeholder="Your email"
                           class="w-full rounded-full border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-white/40 outline-none focus:border-leaf-400 focus:ring-1 focus:ring-leaf-400">
                    <button type="submit" class="btn-glass-orange shrink-0 px-4 py-2.5 text-sm">
                        <iconify-icon icon="tabler:send-2"></iconify-icon>
                    </button>
                </form>
                <p class="mt-2 text-xs text-white/40">Season updates only. No spam.</p>
            </div>

            <x-ui.sitemap-column title="Explore" :links="$exploreLinks" />
            <x-ui.sitemap-column :title="$roleColumnTitle" :links="$roleColumnLinks" />
        </div>

        <div class="mt-12 flex flex-col gap-4 border-t border-white/10 pt-8 text-sm text-white/60 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} MarketLink. All rights reserved.</p>
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                <a href="mailto:hello@marketlink.test" class="hover:text-white">hello@marketlink.test</a>
                <a href="tel:{{ config('marketlink.office_location.phone_dial') }}" class="hover:text-white">{{ config('marketlink.office_location.phone') }}</a>
                <span>{{ config('marketlink.office_location.city_label') }}</span>
            </div>
            <div class="flex items-center gap-2">
                @foreach (['brand-facebook', 'brand-instagram', 'brand-x'] as $icon)
                    <a href="#" class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/70 transition hover:border-leaf-400 hover:text-white" aria-label="{{ str($icon)->after('brand-')->headline() }}">
                        <iconify-icon icon="tabler:{{ $icon }}" class="text-sm"></iconify-icon>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</footer>
