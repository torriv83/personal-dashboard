<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Delt ønskeliste' }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/icons/icon-512x512.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-background text-foreground">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="border-b border-white/10 bg-card/50 backdrop-blur-sm">
            <div class="max-w-4xl mx-auto px-4 py-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-accent/20 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                    <span class="text-sm text-muted">Delt ønskeliste</span>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="border-t border-white/10 py-4">
            <div class="max-w-4xl mx-auto px-4 text-center text-muted-foreground text-xs">
                Laget med kjærlighet
            </div>
        </footer>
    </div>

    @livewireScriptConfig
</body>
</html>
