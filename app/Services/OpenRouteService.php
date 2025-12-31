<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouteService
{
    private const GEOCODING_URL = 'https://api.openrouteservice.org/geocode/search';

    private const DIRECTIONS_URL = 'https://api.openrouteservice.org/v2/directions/driving-car';

    /**
     * Search for location suggestions.
     *
     * @return array<int, array{label: string, address: string}>
     */
    public function searchLocations(string $query, int $limit = 5): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        try {
            $apiKey = config('services.openrouteservice.key');

            if (! $apiKey) {
                return [];
            }

            $response = Http::accept('application/json')
                ->withHeaders(['Authorization' => $apiKey])
                ->get(self::GEOCODING_URL, [
                    'text' => $query,
                    'size' => $limit,
                ]);

            if (! $response->successful()) {
                return [];
            }

            $data = $response->json();
            $features = $data['features'] ?? [];

            return collect($features)->map(function ($feature) {
                $props = $feature['properties'] ?? [];

                return [
                    'label' => $props['label'] ?? $props['name'] ?? '',
                    'address' => $props['label'] ?? '',
                ];
            })->filter(fn ($item) => ! empty($item['label']))
                ->unique('label')
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            Log::warning('OpenRouteService: Search error', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Calculate distance between two addresses.
     *
     * @return float|null Distance in kilometers (one-way), or null if calculation fails
     */
    public function calculateDistance(string $fromAddress, string $toAddress): ?float
    {
        try {
            $fromCoordinates = $this->geocodeAddress($fromAddress);
            if (! $fromCoordinates) {
                Log::warning('OpenRouteService: Failed to geocode from address', ['address' => $fromAddress]);

                return null;
            }

            $toCoordinates = $this->geocodeAddress($toAddress);
            if (! $toCoordinates) {
                Log::warning('OpenRouteService: Failed to geocode to address', ['address' => $toAddress]);

                return null;
            }

            return $this->calculateRouteDistance($fromCoordinates, $toCoordinates);
        } catch (\Exception $e) {
            Log::error('OpenRouteService: Error calculating distance', [
                'from' => $fromAddress,
                'to' => $toAddress,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Geocode an address to coordinates.
     *
     * @return array{lon: float, lat: float}|null
     */
    private function geocodeAddress(string $address): ?array
    {
        try {
            $apiKey = config('services.openrouteservice.key');

            if (! $apiKey) {
                Log::error('OpenRouteService: API key not configured');

                return null;
            }

            $response = Http::accept('application/json')
                ->withHeaders(['Authorization' => $apiKey])
                ->get(self::GEOCODING_URL, [
                    'text' => $address,
                ]);

            if (! $response->successful()) {
                Log::warning('OpenRouteService: Geocoding request failed', [
                    'address' => $address,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json();
            $features = $data['features'] ?? [];

            if (empty($features)) {
                Log::warning('OpenRouteService: No geocoding results found', ['address' => $address]);

                return null;
            }

            $coordinates = $features[0]['geometry']['coordinates'] ?? null;

            if (! $coordinates || count($coordinates) < 2) {
                return null;
            }

            return [
                'lon' => (float) $coordinates[0],
                'lat' => (float) $coordinates[1],
            ];
        } catch (\Exception $e) {
            Log::error('OpenRouteService: Geocoding error', [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Calculate route distance between two coordinate pairs.
     *
     * @param  array{lon: float, lat: float}  $from
     * @param  array{lon: float, lat: float}  $to
     * @return float|null Distance in kilometers, or null if calculation fails
     */
    private function calculateRouteDistance(array $from, array $to): ?float
    {
        try {
            $apiKey = config('services.openrouteservice.key');

            if (! $apiKey) {
                Log::error('OpenRouteService: API key not configured');

                return null;
            }

            $response = Http::accept('application/geo+json')
                ->withHeaders(['Authorization' => $apiKey])
                ->get(self::DIRECTIONS_URL, [
                    'start' => "{$from['lon']},{$from['lat']}",
                    'end' => "{$to['lon']},{$to['lat']}",
                ]);

            if (! $response->successful()) {
                Log::warning('OpenRouteService: Directions request failed', [
                    'status' => $response->status(),
                    'from' => $from,
                    'to' => $to,
                ]);

                return null;
            }

            $data = $response->json();
            $routes = $data['features'] ?? [];

            if (empty($routes)) {
                Log::warning('OpenRouteService: No routes found');

                return null;
            }

            $distanceMeters = $routes[0]['properties']['segments'][0]['distance'] ?? null;

            if ($distanceMeters === null) {
                return null;
            }

            // Convert meters to kilometers
            return round($distanceMeters / 1000, 2);
        } catch (\Exception $e) {
            Log::error('OpenRouteService: Route calculation error', [
                'from' => $from,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
