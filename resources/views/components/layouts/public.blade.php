@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title . ' - MarketLink' : 'MarketLink - Farm fresh, reserved ahead' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="font-sans">
    <x-layouts.blob-background />
    <x-layouts.public-navbar />

    <main>
        {{ $slot }}
    </main>

    <x-layouts.public-footer />

    <x-command-palette />

    @stack('scripts')
    @vite('resources/js/command-palette.js')
</body>
</html>
