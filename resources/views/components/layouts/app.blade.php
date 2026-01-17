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
            class="fixed inset-y-0 left-0 z-50 w-64 bg-card border-r border-border lg:z-0 lg:shrink-0"
        >
            <x-sidebar />
        </aside>

        <!-- Main content -->
        <div class="flex flex-col flex-1 min-w-0 lg:ml-64">
            <!-- Mobile header -->
            <header class="sticky top-0 z-30 flex items-center gap-4 bg-card border-b border-border px-4 py-3 lg:hidden">
                <button
                    @click="sidebarOpen = !sidebarOpen"
                    type="button"
                    class="text-foreground hover:text-accent transition-colors cursor-pointer"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <span class="sr-only">Åpne meny</span>
                </button>
                <h1 class="text-lg font-semibold flex-1">Personlig Dashboard</h1>
                @stack('topbar-actions')
            </header>

            <!-- Page content -->
            <main class="flex-1 pb-20 lg:pb-0">
                {{ $slot }}
            </main>
        </div>

        {{-- Mobile Bottom Navigation --}}
        @auth
            <x-bottom-nav />
        @endauth
    </div>

    {{-- Toast Notifications --}}
    <x-toast />

    {{-- Lock Screen (only for authenticated users) --}}
    @auth
        <livewire:lock-screen />
        <livewire:command-palette />
    @endauth

    @livewireScriptConfig

    {{-- Global Livewire event listeners --}}
    <script>
        document.addEventListener('livewire:init', () => {
            // Handle 419 Page Expired (CSRF token mismatch)
            // This happens when session expires while on lock screen or PWA in background
            // Using request hook with preventDefault() to stop Livewire's default confirm dialog
            Livewire.hook('request', ({ fail }) => {
                fail(({ status, preventDefault }) => {
                    if (status === 419) {
                        preventDefault();
                        window.location.reload();
                    }
                });
            });

            // Clear URL params when modals open via ?create=1
            Livewire.on('clear-url-params', () => {
                const url = new URL(window.location);
                url.searchParams.delete('create');
                window.history.replaceState({}, '', url);
            });
        });
    </script>

    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('SW registered'))
                    .catch(err => console.log('SW registration failed:', err));
            });
        }

        // Push Notifications Alpine Component
        function pushNotifications(vapidPublicKey) {
            return {
                supported: 'serviceWorker' in navigator && 'PushManager' in window,
                subscribed: false,
                loading: false,
                statusText: 'Laster...',

                async init() {
                    if (!this.supported) {
                        this.statusText = 'Ikke støttet i denne nettleseren';
                        return;
                    }

                    try {
                        const registration = await navigator.serviceWorker.ready;
                        const subscription = await registration.pushManager.getSubscription();
                        this.subscribed = !!subscription;
                        this.statusText = this.subscribed ? 'Push-varsler er aktivert' : 'Trykk på en toggle for å aktivere';
                    } catch (error) {
                        console.error('Push init error:', error);
                        this.statusText = 'Kunne ikke sjekke status';
                    }
                },

                async toggleWithSubscription(callback) {
                    if (!this.supported) return;

                    // If not subscribed, subscribe first
                    if (!this.subscribed) {
                        this.loading = true;
                        try {
                            const permission = await Notification.requestPermission();
                            if (permission !== 'granted') {
                                this.statusText = 'Tillatelse ble nektet';
                                this.loading = false;
                                return;
                            }

                            const registration = await navigator.serviceWorker.ready;
                            const subscription = await registration.pushManager.subscribe({
                                userVisibleOnly: true,
                                applicationServerKey: this.urlBase64ToUint8Array(vapidPublicKey)
                            });

                            const sub = subscription.toJSON();
                            await this.$wire.savePushSubscription(
                                sub.endpoint,
                                sub.keys?.p256dh || null,
                                sub.keys?.auth || null
                            );

                            this.subscribed = true;
                            this.statusText = 'Push-varsler er aktivert';
                        } catch (error) {
                            console.error('Push subscribe error:', error);
                            this.statusText = 'Kunne ikke aktivere varsler';
                            this.loading = false;
                            return;
                        }
                        this.loading = false;
                    }

                    // Now execute the toggle callback
                    callback();
                },

                urlBase64ToUint8Array(base64String) {
                    const padding = '='.repeat((4 - base64String.length % 4) % 4);
                    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                    const rawData = window.atob(base64);
                    return Uint8Array.from([...rawData].map(char => char.charCodeAt(0)));
                }
            };
        }
    </script>
</body>
</html>
