<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Calendar iCal Feeds
    |--------------------------------------------------------------------------
    |
    | Configure external Google Calendar feeds to display in the calendar.
    | Each calendar has a URL and cache TTL (time-to-live in seconds).
    |
    */

    'google_calendars' => [
        'privat' => [
            'url' => env('GOOGLE_PRIVAT'),
            'cache_ttl' => 3600, // 1 time
            'label' => 'Privat',
            'color' => '#6b7280', // gray-500
        ],
        'manutd' => [
            'url' => env('GOOGLE_MANUTD'),
            'cache_ttl' => 604800, // 1 uke
            'label' => 'Manchester United',
            'color' => '#dc2626', // red-600
        ],
    ],

];
