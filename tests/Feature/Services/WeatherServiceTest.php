<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Services\WeatherService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    Setting::set('weather_enabled', true);
    Setting::set('weather_latitude', 59.1229);
    Setting::set('weather_longitude', 11.3875);
    Setting::set('weather_location_name', 'Halden');
});

test('getCurrentWeather returns Norwegian description for clearsky symbol', function () {
    Http::fake([
        'api.met.no/weatherapi/locationforecast/2.0/compact*' => Http::response([
            'properties' => [
                'timeseries' => [
                    [
                        'time' => now()->toIso8601String(),
                        'data' => [
                            'instant' => [
                                'details' => [
                                    'air_temperature' => 15.5,
                                    'wind_speed' => 3.2,
                                ],
                            ],
                            'next_1_hours' => [
                                'summary' => ['symbol_code' => 'clearsky_day'],
                                'details' => ['precipitation_amount' => 0],
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $service = new WeatherService;
    $weather = $service->getCurrentWeather();

    expect($weather)->not->toBeNull();
    expect($weather['symbol'])->toBe('clearsky_day');
    expect($weather['description'])->toBe('Klarvær');
});

test('getCurrentWeather returns Norwegian description for rain symbol', function () {
    Http::fake([
        'api.met.no/weatherapi/locationforecast/2.0/compact*' => Http::response([
            'properties' => [
                'timeseries' => [
                    [
                        'time' => now()->toIso8601String(),
                        'data' => [
                            'instant' => [
                                'details' => [
                                    'air_temperature' => 10.5,
                                    'wind_speed' => 5.5,
                                ],
                            ],
                            'next_1_hours' => [
                                'summary' => ['symbol_code' => 'rain'],
                                'details' => ['precipitation_amount' => 2.5],
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $service = new WeatherService;
    $weather = $service->getCurrentWeather();

    expect($weather)->not->toBeNull();
    expect($weather['description'])->toBe('Regn');
});

test('getCurrentWeather returns Norwegian description for snow symbol', function () {
    Http::fake([
        'api.met.no/weatherapi/locationforecast/2.0/compact*' => Http::response([
            'properties' => [
                'timeseries' => [
                    [
                        'time' => now()->toIso8601String(),
                        'data' => [
                            'instant' => [
                                'details' => [
                                    'air_temperature' => -2.0,
                                    'wind_speed' => 4.0,
                                ],
                            ],
                            'next_1_hours' => [
                                'summary' => ['symbol_code' => 'snow'],
                                'details' => ['precipitation_amount' => 1.5],
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $service = new WeatherService;
    $weather = $service->getCurrentWeather();

    expect($weather)->not->toBeNull();
    expect($weather['description'])->toBe('Snø');
});

test('getCurrentWeather strips _day suffix and returns correct translation', function () {
    Http::fake([
        'api.met.no/weatherapi/locationforecast/2.0/compact*' => Http::response([
            'properties' => [
                'timeseries' => [
                    [
                        'time' => now()->toIso8601String(),
                        'data' => [
                            'instant' => [
                                'details' => [
                                    'air_temperature' => 18.0,
                                    'wind_speed' => 2.5,
                                ],
                            ],
                            'next_1_hours' => [
                                'summary' => ['symbol_code' => 'partlycloudy_day'],
                                'details' => ['precipitation_amount' => 0],
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $service = new WeatherService;
    $weather = $service->getCurrentWeather();

    expect($weather)->not->toBeNull();
    expect($weather['symbol'])->toBe('partlycloudy_day');
    expect($weather['description'])->toBe('Delvis skyet');
});

test('getCurrentWeather strips _night suffix and returns correct translation', function () {
    Http::fake([
        'api.met.no/weatherapi/locationforecast/2.0/compact*' => Http::response([
            'properties' => [
                'timeseries' => [
                    [
                        'time' => now()->toIso8601String(),
                        'data' => [
                            'instant' => [
                                'details' => [
                                    'air_temperature' => 8.0,
                                    'wind_speed' => 3.0,
                                ],
                            ],
                            'next_1_hours' => [
                                'summary' => ['symbol_code' => 'fair_night'],
                                'details' => ['precipitation_amount' => 0],
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $service = new WeatherService;
    $weather = $service->getCurrentWeather();

    expect($weather)->not->toBeNull();
    expect($weather['symbol'])->toBe('fair_night');
    expect($weather['description'])->toBe('Lettskyet');
});

test('getCurrentWeather returns fallback for unknown symbol code', function () {
    Http::fake([
        'api.met.no/weatherapi/locationforecast/2.0/compact*' => Http::response([
            'properties' => [
                'timeseries' => [
                    [
                        'time' => now()->toIso8601String(),
                        'data' => [
                            'instant' => [
                                'details' => [
                                    'air_temperature' => 12.0,
                                    'wind_speed' => 4.5,
                                ],
                            ],
                            'next_1_hours' => [
                                'summary' => ['symbol_code' => 'unknown_weather_code'],
                                'details' => ['precipitation_amount' => 0],
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $service = new WeatherService;
    $weather = $service->getCurrentWeather();

    expect($weather)->not->toBeNull();
    expect($weather['symbol'])->toBe('unknown_weather_code');
    expect($weather['description'])->toBe('Ukjent');
});

test('getHourlyForecast returns Norwegian descriptions with day suffix handling', function () {
    $now = now();
    $hour1 = $now->copy()->addHour();
    $hour2 = $now->copy()->addHours(2);

    Http::fake([
        'api.met.no/weatherapi/locationforecast/2.0/compact*' => Http::response([
            'properties' => [
                'timeseries' => [
                    [
                        'time' => $hour1->toIso8601String(),
                        'data' => [
                            'instant' => [
                                'details' => [
                                    'air_temperature' => 15.0,
                                    'wind_speed' => 3.0,
                                ],
                            ],
                            'next_1_hours' => [
                                'summary' => ['symbol_code' => 'cloudy'],
                                'details' => ['precipitation_amount' => 0],
                            ],
                        ],
                    ],
                    [
                        'time' => $hour2->toIso8601String(),
                        'data' => [
                            'instant' => [
                                'details' => [
                                    'air_temperature' => 16.0,
                                    'wind_speed' => 3.5,
                                ],
                            ],
                            'next_1_hours' => [
                                'summary' => ['symbol_code' => 'lightrainshowers_day'],
                                'details' => ['precipitation_amount' => 0.5],
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $service = new WeatherService;
    $forecast = $service->getHourlyForecast();

    expect($forecast)->toBeArray();
    expect($forecast[0]['description'])->toBe('Skyet');
    expect($forecast[1]['description'])->toBe('Lette regnbyger');
});

test('getWeeklyForecast returns Norwegian descriptions with night suffix handling', function () {
    $tomorrow = now()->addDay()->setHour(12);

    Http::fake([
        'api.met.no/weatherapi/locationforecast/2.0/compact*' => Http::response([
            'properties' => [
                'timeseries' => [
                    [
                        'time' => $tomorrow->toIso8601String(),
                        'data' => [
                            'instant' => [
                                'details' => [
                                    'air_temperature' => 10.0,
                                    'wind_speed' => 5.0,
                                ],
                            ],
                            'next_6_hours' => [
                                'summary' => ['symbol_code' => 'rainshowers_night'],
                                'details' => ['precipitation_amount' => 3.0],
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $service = new WeatherService;
    $forecast = $service->getWeeklyForecast();

    expect($forecast)->toBeArray();
    if (count($forecast) > 0) {
        expect($forecast[0]['description'])->toBe('Regnbyger');
    }
});

test('translations work for all common weather symbols', function (string $symbolCode, string $expectedDescription) {
    Http::fake([
        'api.met.no/weatherapi/locationforecast/2.0/compact*' => Http::response([
            'properties' => [
                'timeseries' => [
                    [
                        'time' => now()->toIso8601String(),
                        'data' => [
                            'instant' => [
                                'details' => [
                                    'air_temperature' => 12.0,
                                    'wind_speed' => 4.0,
                                ],
                            ],
                            'next_1_hours' => [
                                'summary' => ['symbol_code' => $symbolCode],
                                'details' => ['precipitation_amount' => 0],
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $service = new WeatherService;
    $weather = $service->getCurrentWeather();

    expect($weather['description'])->toBe($expectedDescription);
})->with([
    ['clearsky_day', 'Klarvær'],
    ['fair_night', 'Lettskyet'],
    ['partlycloudy', 'Delvis skyet'],
    ['cloudy', 'Skyet'],
    ['fog', 'Tåke'],
    ['lightrain', 'Lett regn'],
    ['rain', 'Regn'],
    ['heavyrain', 'Kraftig regn'],
    ['lightsnow', 'Lett snø'],
    ['snow', 'Snø'],
    ['heavysnow', 'Kraftig snø'],
    ['sleet', 'Sludd'],
    ['lightrainshowers_day', 'Lette regnbyger'],
    ['rainshowers_night', 'Regnbyger'],
    ['heavyrainshowers', 'Kraftige regnbyger'],
]);

test('service returns null when not configured', function () {
    Setting::set('weather_enabled', false);

    $service = new WeatherService;
    $weather = $service->getCurrentWeather();

    expect($weather)->toBeNull();
});

test('clearCache clears all weather cache keys', function () {
    $lat = 59.1229;
    $lon = 11.3875;

    Cache::put("weather.data.{$lat}.{$lon}", ['test'], 300);
    Cache::put("weather.data.forecast.{$lat}.{$lon}", ['test'], 300);
    Cache::put("weather.data.hourly.{$lat}.{$lon}", ['test'], 300);

    expect(Cache::has("weather.data.{$lat}.{$lon}"))->toBeTrue();

    $service = new WeatherService;
    $service->clearCache();

    expect(Cache::has("weather.data.{$lat}.{$lon}"))->toBeFalse();
    expect(Cache::has("weather.data.forecast.{$lat}.{$lon}"))->toBeFalse();
    expect(Cache::has("weather.data.hourly.{$lat}.{$lon}"))->toBeFalse();
});

test('API errors return null gracefully', function () {
    Http::fake([
        '*' => Http::response(null, 500),
    ]);

    $service = new WeatherService;

    expect($service->getCurrentWeather())->toBeNull();
    expect($service->getHourlyForecast())->toBe([]);
    expect($service->getWeeklyForecast())->toBe([]);
});
