<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AssistantPwaController extends Controller
{
    /**
     * Generate dynamic manifest.json for assistant PWA.
     */
    public function manifest(Assistant $assistant): JsonResponse
    {
        $startUrl = route('tasks.assistant', $assistant);
        $scope = '/oppgaver/'.$assistant->token;

        $firstName = explode(' ', $assistant->name)[0];

        $manifest = [
            'id' => 'tor-oppgaver-'.$assistant->token,
            'name' => $firstName.' - Oppgaver',
            'short_name' => $firstName.' - Oppgaver',
            'description' => 'Oppgaveliste for assistenter',
            'start_url' => $startUrl,
            'scope' => $scope,
            'display' => 'standalone',
            'background_color' => '#1a1a1a',
            'theme_color' => '#1a1a1a',
            'orientation' => 'any',
            'icons' => [
                [
                    'src' => '/icons/tasks-icon-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
                [
                    'src' => '/icons/tasks-icon-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
        ];

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json');
    }

    /**
     * Serve the service worker for assistant PWA.
     */
    public function serviceWorker(Assistant $assistant): Response
    {
        $scope = '/oppgaver/'.$assistant->token;

        $swContent = <<<'JS'
const CACHE_NAME = 'tasks-assistant-v1';

// Install - skip waiting immediately
self.addEventListener('install', (event) => {
    self.skipWaiting();
});

// Activate - clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys
                    .filter((key) => key.startsWith('tasks-assistant-') && key !== CACHE_NAME)
                    .map((key) => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

// Fetch - network first for HTML, cache first for assets
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Only handle GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // For static assets (css, js, images), use cache-first
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/')) {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                return cached || fetch(event.request).then((response) => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, clone);
                    });
                    return response;
                });
            })
        );
        return;
    }

    // For everything else (HTML, Livewire), use network-first
    event.respondWith(
        fetch(event.request).catch(() => {
            return caches.match(event.request);
        })
    );
});

// Push notification handling
self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    const data = event.data.json();
    const options = {
        body: data.body || '',
        icon: '/icons/icon-192x192.png',
        badge: '/icons/icon-72x72.png',
        tag: data.tag || 'default',
        data: {
            url: data.url || '/'
        },
        vibrate: [200, 100, 200],
        requireInteraction: data.requireInteraction || false
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'Varsel', options)
    );
});

// Handle notification click
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                for (const client of clientList) {
                    if (client.url.includes(self.location.origin) && 'focus' in client) {
                        client.navigate(url);
                        return client.focus();
                    }
                }
                return clients.openWindow(url);
            })
    );
});
JS;

        return response($swContent)
            ->header('Content-Type', 'application/javascript')
            ->header('Service-Worker-Allowed', $scope);
    }
}
