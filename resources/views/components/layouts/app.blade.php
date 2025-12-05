<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Personlig Dashboard' }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/icons/icon-512x512.png">

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1a1a1a">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-background text-foreground">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen flex">
        <!-- Mobile sidebar overlay -->
        <div
            x-show="sidebarOpen"
            @click="sidebarOpen = false"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-40 bg-black/50 lg:hidden"
            style="display: none;"
        ></div>

        <!-- Sidebar -->
        <aside
            x-show="sidebarOpen || window.innerWidth >= 1024"
            @click.away="window.innerWidth < 1024 && (sidebarOpen = false)"
            x-transition:enter="transition ease-in-out duration-300 transform"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in-out duration-300 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-card border-r border-border lg:relative lg:translate-x-0 lg:z-0 lg:shrink-0"
        >
            <x-sidebar />
        </aside>

        <!-- Main content -->
        <div class="flex flex-col flex-1 min-w-0">
            <!-- Mobile header -->
            <header class="sticky top-0 z-30 flex items-center gap-4 bg-card border-b border-border px-4 py-3 lg:hidden">
                <button
                    @click="sidebarOpen = !sidebarOpen"
                    type="button"
                    class="text-foreground hover:text-accent transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <span class="sr-only">Åpne meny</span>
                </button>
                <h1 class="text-lg font-semibold">Personlig Dashboard</h1>
            </header>

            <!-- Page content -->
            <main class="flex-1 p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Toast Notifications --}}
    <x-toast />

    {{-- Lock Screen (only for authenticated users) --}}
    @auth
        <livewire:lock-screen />
    @endauth

    @livewireScriptConfig

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
</body>
</html>
