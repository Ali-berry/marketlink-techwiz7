{{-- Breeze ka guest layout (login, register, forgot password...) - poori photo, dark green overlay, beech mein glass card --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>MarketLink - Farm fresh, reserved ahead</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans">
    <div class="relative min-h-screen overflow-hidden bg-soil">
        {{-- home aur about wala photo + overlay, taake auth pages site ka hissa lagen --}}
        <img src="{{ asset('images/site/auth-evening-market.webp') }}" alt="" aria-hidden="true"
             class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0" style="background: linear-gradient(rgba(15,40,24,0.75), rgba(15,40,24,0.85));"></div>

        <div class="relative flex min-h-screen flex-col items-center px-4 py-10 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 rounded-full focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/70">
                <span class="icon-chip h-9 w-9 bg-leaf-500 text-lg text-white">
                    <iconify-icon icon="tabler:plant-2"></iconify-icon>
                </span>
                <span class="font-display text-xl font-semibold text-white [text-shadow:0_2px_12px_rgba(0,0,0,0.25)]">MarketLink</span>
            </a>

            {{-- site ka .glass card photo pe - .auth-card (app.css) andar text aur inputs light kar deta hai --}}
            <div class="flex w-full max-w-lg flex-1 flex-col justify-center py-10">
                <div class="glass auth-card p-6 sm:p-10">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
