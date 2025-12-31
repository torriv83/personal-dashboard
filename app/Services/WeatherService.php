<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WeatherService
{
    private const BASE_URL = 'https://api.met.no/weatherapi/locationforecast/2.0/compact';

    private const CACHE_KEY = 'weather.data';

    private const CACHE_TTL = 1800; // 30 minutes

    /**
     * Default location (Halden, Norway).
     */
    private const DEFAULT_LAT = 59.1229;

    private const DEFAULT_LON = 11.3875;

    private const DEFAULT_LOCATION_NAME = 'Halden';

    /**
     * Check if weather is configured (has location set).
     */
    public function isConfigured(): bool
    {
        return Setting::get('weather_enabled', true);
    }

    /**
     * Get the configured latitude.
     */
    public function getLatitude(): float
    {
        return (float) Setting::get('weather_latitude', self::DEFAULT_LAT);
    }

    /**
     * Get the configured longitude.
     */
    public function getLongitude(): float
    {
        return (float) Setting::get('weather_longitude', self::DEFAULT_LON);
    }

    /**
     * Get the configured location name.
     */
    public function getLocationName(): string
    {
        return Setting::get('weather_location_name', self::DEFAULT_LOCATION_NAME);
    }

    /**
     * Get current weather data.
     *
     * @return array{
     *     temperature: float,
     *     symbol: string,
     *     description: string,
     *     wind_speed: float,
     *     precipitation: float,
     *     location: string,
     *     updated_at: string
     * }|null
     */
    public function getCurrentWeather(): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $lat = $this->getLatitude();
        $lon = $this->getLongitude();

        return Cache::remember(self::CACHE_KEY . ".$lat.$lon", self::CACHE_TTL, function () use ($lat, $lon) {
            return $this->fetchWeather($lat, $lon);
        });
    }

    /**
     * Fetch weather from Met.no API.
     */
    private function fetchWeather(float $lat, float $lon): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'PersonalDashboard/1.0 github.com/personal-dashboard',
            ])
                ->accept('application/json')
                ->get(self::BASE_URL, [
                    'lat' => round($lat, 4),
                    'lon' => round($lon, 4),
                ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();
            $current = $data['properties']['timeseries'][0] ?? null;

            if (! $current) {
                return null;
            }

            $instant = $current['data']['instant']['details'] ?? [];
            $next1Hour = $current['data']['next_1_hours'] ?? [];

            $symbolCode = $next1Hour['summary']['symbol_code'] ?? 'cloudy';
            $precipitation = $next1Hour['details']['precipitation_amount'] ?? 0;

            return [
                'temperature' => round($instant['air_temperature'] ?? 0),
                'symbol' => $symbolCode,
                'description' => $this->getSymbolDescription($symbolCode),
                'wind_speed' => round($instant['wind_speed'] ?? 0, 1),
                'precipitation' => $precipitation,
                'location' => $this->getLocationName(),
                'updated_at' => now()->format('H:i'),
            ];
        } catch (\Exception $e) {
            report($e);

            return null;
        }
    }

    /**
     * Clear weather cache.
     */
    public function clearCache(): void
    {
        $lat = $this->getLatitude();
        $lon = $this->getLongitude();
        Cache::forget(self::CACHE_KEY . ".$lat.$lon");
        Cache::forget(self::CACHE_KEY . ".forecast.$lat.$lon");
        Cache::forget(self::CACHE_KEY . ".hourly.$lat.$lon");
    }

    /**
     * Get hourly forecast for today.
     *
     * @return array<int, array{
     *     time: string,
     *     hour: string,
     *     temperature: float,
     *     symbol: string,
     *     description: string,
     *     precipitation: float,
     *     wind_speed: float
     * }>
     */
    public function getHourlyForecast(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $lat = $this->getLatitude();
        $lon = $this->getLongitude();

        return Cache::remember(self::CACHE_KEY . ".hourly.$lat.$lon", self::CACHE_TTL, function () use ($lat, $lon) {
            return $this->fetchHourlyForecast($lat, $lon);
        });
    }

    /**
     * Fetch hourly forecast from Met.no API.
     */
    private function fetchHourlyForecast(float $lat, float $lon): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'PersonalDashboard/1.0 github.com/personal-dashboard',
            ])
                ->accept('application/json')
                ->get(self::BASE_URL, [
                    'lat' => round($lat, 4),
                    'lon' => round($lon, 4),
                ]);

            if (! $response->successful()) {
                return [];
            }

            $data = $response->json();
            $timeseries = $data['properties']['timeseries'] ?? [];

            if (empty($timeseries)) {
                return [];
            }

            $today = now()->format('Y-m-d');
            $hourlyData = [];

            foreach ($timeseries as $entry) {
                $time = \Carbon\Carbon::parse($entry['time'])->setTimezone(config('app.timezone'));
                $dateKey = $time->format('Y-m-d');

                // Only include today's hours
                if ($dateKey !== $today) {
                    continue;
                }

                // Skip past hours
                if ($time->lt(now()->startOfHour())) {
                    continue;
                }

                $instant = $entry['data']['instant']['details'] ?? [];
                $next1Hour = $entry['data']['next_1_hours'] ?? [];

                $symbol = $next1Hour['summary']['symbol_code'] ?? 'cloudy';
                $precipitation = $next1Hour['details']['precipitation_amount'] ?? 0;

                $hourlyData[] = [
                    'time' => $time->format('Y-m-d H:i'),
                    'hour' => $time->format('H:i'),
                    'temperature' => round($instant['air_temperature'] ?? 0),
                    'symbol' => $symbol,
                    'description' => $this->getSymbolDescription($symbol),
                    'precipitation' => round($precipitation, 1),
                    'wind_speed' => round($instant['wind_speed'] ?? 0, 1),
                ];
            }

            return $hourlyData;
        } catch (\Exception $e) {
            report($e);

            return [];
        }
    }

    /**
     * Get weekly forecast data.
     *
     * @return array<int, array{
     *     date: string,
     *     day_name: string,
     *     day_short: string,
     *     symbol: string,
     *     description: string,
     *     temp_high: float,
     *     temp_low: float,
     *     precipitation: float,
     *     wind_speed: float
     * }>
     */
    public function getWeeklyForecast(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $lat = $this->getLatitude();
        $lon = $this->getLongitude();

        return Cache::remember(self::CACHE_KEY . ".forecast.$lat.$lon", self::CACHE_TTL, function () use ($lat, $lon) {
            return $this->fetchWeeklyForecast($lat, $lon);
        });
    }

    /**
     * Fetch weekly forecast from Met.no API.
     */
    private function fetchWeeklyForecast(float $lat, float $lon): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'PersonalDashboard/1.0 github.com/personal-dashboard',
            ])
                ->accept('application/json')
                ->get(self::BASE_URL, [
                    'lat' => round($lat, 4),
                    'lon' => round($lon, 4),
                ]);

            if (! $response->successful()) {
                return [];
            }

            $data = $response->json();
            $timeseries = $data['properties']['timeseries'] ?? [];

            if (empty($timeseries)) {
                return [];
            }

            // Group by date and extract daily data
            $dailyData = [];
            foreach ($timeseries as $entry) {
                $time = \Carbon\Carbon::parse($entry['time'])->setTimezone(config('app.timezone'));
                $dateKey = $time->format('Y-m-d');

                // Skip today - we already show current weather
                if ($dateKey === now()->format('Y-m-d')) {
                    continue;
                }

                // Only take next 7 days
                if (count($dailyData) >= 7 && ! isset($dailyData[$dateKey])) {
                    break;
                }

                $instant = $entry['data']['instant']['details'] ?? [];
                $next6Hours = $entry['data']['next_6_hours'] ?? [];
                $next1Hour = $entry['data']['next_1_hours'] ?? [];

                $temp = $instant['air_temperature'] ?? null;
                $wind = $instant['wind_speed'] ?? 0;
                $precip = $next6Hours['details']['precipitation_amount']
                    ?? $next1Hour['details']['precipitation_amount']
                    ?? 0;
                $symbol = $next6Hours['summary']['symbol_code']
                    ?? $next1Hour['summary']['symbol_code']
                    ?? 'cloudy';

                if (! isset($dailyData[$dateKey])) {
                    $dailyData[$dateKey] = [
                        'date' => $dateKey,
                        'day_name' => $time->translatedFormat('l'),
                        'day_short' => $time->translatedFormat('D'),
                        'temps' => [],
                        'symbols' => [],
                        'precipitation' => 0,
                        'wind_speeds' => [],
                    ];
                }

                if ($temp !== null) {
                    $dailyData[$dateKey]['temps'][] = $temp;
                }
                $dailyData[$dateKey]['wind_speeds'][] = $wind;
                $dailyData[$dateKey]['precipitation'] += $precip;

                // Prefer midday symbol (12:00) for the day's icon
                $hour = $time->hour;
                if ($hour >= 10 && $hour <= 14) {
                    $dailyData[$dateKey]['symbols'][] = $symbol;
                }
            }

            // Process and format the daily data
            $forecast = [];
            foreach ($dailyData as $day) {
                if (empty($day['temps'])) {
                    continue;
                }

                $symbol = $day['symbols'][0] ?? 'cloudy';

                $forecast[] = [
                    'date' => $day['date'],
                    'day_name' => $day['day_name'],
                    'day_short' => $day['day_short'],
                    'symbol' => $symbol,
                    'description' => $this->getSymbolDescription($symbol),
                    'temp_high' => round(max($day['temps'])),
                    'temp_low' => round(min($day['temps'])),
                    'precipitation' => round($day['precipitation'], 1),
                    'wind_speed' => round(array_sum($day['wind_speeds']) / count($day['wind_speeds']), 1),
                ];
            }

            return array_slice($forecast, 0, 7);
        } catch (\Exception $e) {
            report($e);

            return [];
        }
    }

    /**
     * Get Norwegian description for weather symbol code.
     */
    private function getSymbolDescription(string $symbolCode): string
    {
        // Remove _day/_night suffix for matching
        $baseCode = preg_replace('/_(day|night)$/', '', $symbolCode);

        return match ($baseCode) {
            'clearsky' => 'Klarvær',
            'fair' => 'Lettskyet',
            'partlycloudy' => 'Delvis skyet',
            'cloudy' => 'Skyet',
            'lightrainshowers' => 'Lette regnbyger',
            'rainshowers' => 'Regnbyger',
            'heavyrainshowers' => 'Kraftige regnbyger',
            'lightrainshowersandthunder' => 'Lette regnbyger og torden',
            'rainshowersandthunder' => 'Regnbyger og torden',
            'heavyrainshowersandthunder' => 'Kraftige regnbyger og torden',
            'lightsleetshowers' => 'Lette sluddbyger',
            'sleetshowers' => 'Sluddbyger',
            'heavysleetshowers' => 'Kraftige sluddbyger',
            'lightssleetshowersandthunder' => 'Lette sluddbyger og torden',
            'sleetshowersandthunder' => 'Sluddbyger og torden',
            'heavysleetshowersandthunder' => 'Kraftige sluddbyger og torden',
            'lightsnowshowers' => 'Lette snøbyger',
            'snowshowers' => 'Snøbyger',
            'heavysnowshowers' => 'Kraftige snøbyger',
            'lightssnowshowersandthunder' => 'Lette snøbyger og torden',
            'snowshowersandthunder' => 'Snøbyger og torden',
            'heavysnowshowersandthunder' => 'Kraftige snøbyger og torden',
            'lightrain' => 'Lett regn',
            'rain' => 'Regn',
            'heavyrain' => 'Kraftig regn',
            'lightrainandthunder' => 'Lett regn og torden',
            'rainandthunder' => 'Regn og torden',
            'heavyrainandthunder' => 'Kraftig regn og torden',
            'lightsleet' => 'Lett sludd',
            'sleet' => 'Sludd',
            'heavysleet' => 'Kraftig sludd',
            'lightsleetandthunder' => 'Lett sludd og torden',
            'sleetandthunder' => 'Sludd og torden',
            'heavysleetandthunder' => 'Kraftig sludd og torden',
            'lightsnow' => 'Lett snø',
            'snow' => 'Snø',
            'heavysnow' => 'Kraftig snø',
            'lightsnowandthunder' => 'Lett snø og torden',
            'snowandthunder' => 'Snø og torden',
            'heavysnowandthunder' => 'Kraftig snø og torden',
            'fog' => 'Tåke',
            default => 'Ukjent',
        };
    }

    /**
     * Search for a location using Nominatim (OpenStreetMap).
     *
     * @return array{name: string, lat: float, lon: float}|null
     */
    public function searchLocation(string $query): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'PersonalDashboard/1.0 github.com/personal-dashboard',
            ])
                ->accept('application/json')
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'no', // Prioritize Norway
                ]);

            if (! $response->successful()) {
                return null;
            }

            $results = $response->json();

            if (empty($results)) {
                return null;
            }

            $result = $results[0];

            return [
                'name' => $this->formatLocationName($result['display_name']),
                'lat' => (float) $result['lat'],
                'lon' => (float) $result['lon'],
            ];
        } catch (\Exception $e) {
            report($e);

            return null;
        }
    }

    /**
     * Format location name to be shorter and nicer.
     */
    private function formatLocationName(string $displayName): string
    {
        $parts = explode(',', $displayName);

        // Return first part (usually city/town name)
        return trim($parts[0]);
    }

    /**
     * Get weather icon component for a symbol code.
     */
    public function getIconSvg(string $symbolCode): string
    {
        $baseCode = preg_replace('/_(day|night)$/', '', $symbolCode);
        $isNight = str_ends_with($symbolCode, '_night');

        return match (true) {
            str_contains($baseCode, 'thunder') => 'thunder',
            str_contains($baseCode, 'snow') => 'snow',
            str_contains($baseCode, 'sleet') => 'sleet',
            str_contains($baseCode, 'rain') => 'rain',
            $baseCode === 'fog' => 'fog',
            $baseCode === 'cloudy' => 'cloudy',
            $baseCode === 'partlycloudy' => $isNight ? 'partlycloudy-night' : 'partlycloudy',
            $baseCode === 'fair' => $isNight ? 'fair-night' : 'fair',
            $baseCode === 'clearsky' => $isNight ? 'clearsky-night' : 'clearsky',
            default => 'cloudy',
        };
    }
}
