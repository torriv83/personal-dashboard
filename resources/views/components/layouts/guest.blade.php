<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Logg inn' }} - Personlig Dashboard</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/icons/icon-512x512.png">

    <!-- PWA -->
    <link rel="manifest" href="{{ $manifest ?? '/manifest.json' }}">
    <meta name="theme-color" content="#1a1a1a">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-background text-foreground">
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        <!-- Logo/Title -->
        <div class="mb-8 text-center">
            <div class="flex items-center justify-center gap-3 mb-2">
                <img src="/icons/icon-192x192.png" alt="Dashboard" class="w-10 h-10 rounded-lg">
                <h1 class="text-2xl font-bold text-foreground">Personlig Dashboard</h1>
            </div>
            <p class="text-muted text-sm">Ditt personlige verktøy for hverdagen</p>
        </div>

        <!-- Content -->
        {{ $slot }}

        <!-- Footer -->
        <div class="mt-8 text-center text-muted-foreground text-xs">
            &copy; {{ date('Y') }} Personlig Dashboard
        </div>
    </div>

    {{-- Toast Notifications --}}
    <x-toast />

    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('SW registered'))
                    .catch(err => console.log('SW registration failed:', err));
            });
        }
    </script>
    @livewireScriptConfig
</body>
</html>
