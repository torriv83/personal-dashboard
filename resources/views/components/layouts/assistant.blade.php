@php
    $assistant = request()->route('assistant');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1a1a1a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Tor - Oppgaver">

    <title>{{ $title ?? 'Oppgaver' }}</title>

    <!-- PWA Manifest -->
    @if($assistant)
        <link rel="manifest" href="{{ route('tasks.assistant.manifest', $assistant) }}">
    @endif

    <!-- Favicon / App Icons -->
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/tasks-icon-192x192.png">
    <link rel="apple-touch-icon" href="/icons/tasks-icon-192x192.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-background text-foreground">
    <div class="min-h-screen flex flex-col">
        {{ $slot }}
    </div>

    <!-- Toast notifications -->
    <x-toast />

    @livewireScriptConfig

    {{-- Handle 419 Page Expired without showing confirm dialog --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.hook('request', ({ fail }) => {
                fail(({ status, preventDefault }) => {
                    if (status === 419) {
                        preventDefault();
                        fetch(window.location.href)
                            .then(r => r.text())
                            .then(html => {
                                const match = html.match(/csrf-token" content="([^"]+)"/);
                                if (match) {
                                    document.querySelector('meta[name="csrf-token"]').content = match[1];
                                }
                                window.dispatchEvent(new CustomEvent('toast', {
                                    detail: { type: 'warning', message: 'Sesjonen utløp. Prøv igjen.' }
                                }));
                            })
                            .catch(() => window.location.reload());
                    }
                });
            });
        });
    </script>

    <!-- Register Service Worker -->
    @if($assistant)
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('{{ route('tasks.assistant.sw', $assistant) }}', {
                        scope: '/oppgaver/{{ $assistant->token }}'
                    }).then(function(registration) {
                        console.log('SW registered:', registration.scope);
                    }).catch(function(error) {
                        console.log('SW registration failed:', error);
                    });
                });
            }
        </script>
    @endif
</body>
</html>
