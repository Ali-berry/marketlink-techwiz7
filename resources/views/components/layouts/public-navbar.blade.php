@php
    $signedInUser = auth()->user();
@endphp

{{-- dark rounded pill navbar - logo left, links center, actions right, hamare soil / leaf / tomato colors --}}
<header class="sticky top-4 z-40 px-4" x-data="{ mobileMenuOpen: false }">
    <div class="nav-glass mx-auto flex max-w-5xl items-center justify-between gap-4 rounded-full px-6 py-3 text-cream">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5">
            <span class="icon-chip h-9 w-9 bg-leaf-500 text-lg text-white">
                <iconify-icon icon="tabler:plant-2"></iconify-icon>
            </span>
            <span class="font-display text-lg font-semibold text-white">MarketLink</span>
        </a>

        <nav class="hidden items-center gap-1 text-sm font-medium text-cream/70 lg:flex">
            <a href="{{ route('customer.markets.index') }}" class="rounded-full px-4 py-2 transition-colors hover:bg-white/5 hover:text-tomato-300">Markets</a>
            <a href="{{ route('customer.farmers.index') }}" class="rounded-full px-4 py-2 transition-colors hover:bg-white/5 hover:text-tomato-300">Farmers</a>
            <a href="{{ route('community.index') }}" class="rounded-full px-4 py-2 transition-colors hover:bg-white/5 hover:text-tomato-300">Community</a>
            <a href="{{ route('about') }}" class="rounded-full px-4 py-2 transition-colors hover:bg-white/5 hover:text-tomato-300">About</a>
            <a href="{{ route('contact') }}" class="rounded-full px-4 py-2 transition-colors hover:bg-white/5 hover:text-tomato-300">Contact</a>
        </nav>

        <div class="hidden items-center gap-2 lg:flex">
            @if ($signedInUser)
                <a href="{{ route($signedInUser->dashboardRouteName()) }}" class="btn-glass-orange px-4 py-2 text-sm">Go to my dashboard</a>
            @else
                <a href="{{ route('login') }}" class="rounded-full border border-white/20 px-4 py-2 text-sm font-medium text-cream transition hover:bg-white/10">Log in</a>
                <a href="{{ route('register') }}" class="btn-glass-orange px-4 py-2 text-sm">Join MarketLink</a>
            @endif
        </div>

        <button type="button" class="rounded-full p-2 text-cream hover:bg-white/10 lg:hidden" @click="mobileMenuOpen = true" aria-label="Open menu">
            <iconify-icon icon="tabler:menu-2" class="text-2xl"></iconify-icon>
        </button>
    </div>

    {{-- mobile menu - desktop wale links aur account ke do buttons --}}
    <div x-show="mobileMenuOpen" x-transition.opacity
         class="fixed inset-0 z-50 bg-soil/60 lg:hidden"
         @click="mobileMenuOpen = false" x-cloak></div>

    <div x-show="mobileMenuOpen" x-transition
         class="nav-glass fixed inset-y-0 right-0 z-50 w-72 max-w-[85vw] overflow-y-auto rounded-none p-6 text-cream shadow-xl lg:hidden"
         x-cloak>
        <div class="flex items-center justify-between">
            <span class="font-display text-lg font-semibold text-white">Menu</span>
            <button type="button" class="rounded-full p-2 hover:bg-white/10" @click="mobileMenuOpen = false" aria-label="Close menu">
                <iconify-icon icon="tabler:x" class="text-xl"></iconify-icon>
            </button>
        </div>

        <nav class="mt-6 flex flex-col gap-1 text-sm font-medium text-cream/80">
            <a href="{{ route('customer.markets.index') }}" class="rounded-xl px-3 py-2.5 hover:bg-white/5 hover:text-tomato-300" @click="mobileMenuOpen = false">Markets</a>
            <a href="{{ route('customer.farmers.index') }}" class="rounded-xl px-3 py-2.5 hover:bg-white/5 hover:text-tomato-300" @click="mobileMenuOpen = false">Farmers</a>
            <a href="{{ route('community.index') }}" class="rounded-xl px-3 py-2.5 hover:bg-white/5 hover:text-tomato-300" @click="mobileMenuOpen = false">Community</a>
            <a href="{{ route('about') }}" class="rounded-xl px-3 py-2.5 hover:bg-white/5 hover:text-tomato-300" @click="mobileMenuOpen = false">About</a>
            <a href="{{ route('contact') }}" class="rounded-xl px-3 py-2.5 hover:bg-white/5 hover:text-tomato-300" @click="mobileMenuOpen = false">Contact</a>
        </nav>

        <div class="mt-6 flex flex-col gap-3 border-t border-white/10 pt-6">
            @if ($signedInUser)
                <a href="{{ route($signedInUser->dashboardRouteName()) }}" class="btn-accent w-full">Go to my dashboard</a>
            @else
                <a href="{{ route('login') }}" class="w-full rounded-full border border-white/20 px-4 py-2.5 text-center text-sm font-medium text-cream hover:bg-white/10">Log in</a>
                <a href="{{ route('register') }}" class="btn-accent w-full">Join MarketLink</a>
            @endif
        </div>
    </div>
</header>
