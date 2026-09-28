@props(['title' => 'Dashboard'])

@php
    $signedInUser = auth()->user();
    $panelMenuFile = 'components.panel.menus.' . $signedInUser->role->value;

    // Admin ek UserRole hai magar Spatie ke kai tiers - sidebar mein dikhao kaunsa hai
    $panelLabel = $signedInUser->role === \App\Enums\UserRole::Admin
        ? match (true) {
            $signedInUser->hasRole('super-admin') => 'Super Admin',
            $signedInUser->hasRole('community-moderator') => 'Community Moderator',
            default => 'Support Admin',
        }
        : $signedInUser->role->label();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} - MarketLink</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="font-sans" x-data="{ mobileMenuOpen: false }">
    {{-- website wala blob background, halka, taake glass cards aur sidebar ke peeche kuch dikhe --}}
    <x-layouts.blob-background subtle />

    {{-- mobile menu ke peeche dark background --}}
    <div x-show="mobileMenuOpen" x-transition.opacity
         class="fixed inset-0 z-30 bg-soil/40 lg:hidden"
         @click="mobileMenuOpen = false" x-cloak></div>

    <aside class="panel-sidebar fixed inset-y-0 left-0 z-40 flex w-64 flex-col transition-transform lg:translate-x-0"
           :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'">

        <a href="{{ route('home') }}" class="flex items-center gap-2.5 px-6 py-6">
            <span class="icon-chip h-9 w-9 bg-leaf-500 text-lg text-white">
                <iconify-icon icon="tabler:plant-2"></iconify-icon>
            </span>
            <span class="font-display text-xl font-semibold text-leaf-700">MarketLink</span>
        </a>

        <p class="px-6 pb-2 text-xs font-medium text-soil-muted">{{ $panelLabel }} panel</p>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 pb-6">
            @include($panelMenuFile)
        </nav>

        <div class="border-t border-white/70 p-4">
            <div class="flex items-center gap-3">
                <span class="icon-chip h-10 w-10 bg-tomato-100 text-sm font-semibold text-tomato-800">
                    {{ strtoupper(substr($signedInUser->name, 0, 1)) }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium">{{ $signedInUser->name }}</p>
                    <p class="truncate text-xs text-soil-muted">{{ $signedInUser->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-full p-2 text-soil-muted hover:bg-white/70 hover:text-red-600" aria-label="Log out">
                        <iconify-icon icon="tabler:logout" class="text-lg"></iconify-icon>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="lg:pl-64">
        {{-- public navbar jaisa dark pill, patla - navigation sidebar mein, yahan sirf title aur account actions --}}
        <header class="sticky top-4 z-20 mx-4 flex items-center gap-4 rounded-full border border-white/10 bg-soil px-4 py-3 text-cream shadow-lg shadow-soil/10 sm:mx-8">
            <button type="button" class="rounded-full p-2 text-cream hover:bg-white/10 lg:hidden" @click="mobileMenuOpen = true" aria-label="Open menu">
                <iconify-icon icon="tabler:menu-2" class="text-xl"></iconify-icon>
            </button>

            <h1 class="flex-1 truncate text-lg font-semibold text-white">{{ $title }}</h1>

            <x-ui.notification-bell />

            <a href="{{ route('profile.edit') }}" class="hidden items-center gap-2 rounded-full px-3 py-1.5 text-sm text-cream/80 hover:bg-white/10 sm:flex">
                <iconify-icon icon="tabler:user-circle" class="text-lg"></iconify-icon>
                My profile
            </a>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-8">
            <x-ui.flash-messages />
            {{ $slot }}
        </main>
    </div>

    {{-- har role ka apna AI widget, jo user ka role ho --}}
    <x-dynamic-component :component="$signedInUser->role->value . '-agent-chat'" />

    <x-command-palette />

    @stack('scripts')
    @vite(['resources/js/agent-chat.js', 'resources/js/command-palette.js'])
</body>
</html>
