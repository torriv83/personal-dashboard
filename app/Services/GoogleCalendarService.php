<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Sabre\VObject\Reader;

class GoogleCalendarService
{
    /**
     * Get events from all configured Google calendars.
     *
     * @return Collection<int, CalendarEvent>
     */
    public function getAllEvents(Carbon $start, Carbon $end): Collection
    {
        $events = collect();

        foreach (config('calendar.google_calendars', []) as $key => $calendar) {
            if (empty($calendar['url'])) {
                continue;
            }

            $calendarEvents = $this->getEvents($key, $start, $end);
            $events = $events->merge($calendarEvents);
        }

        return $events->sortBy('starts_at')->values();
    }

    /**
     * Get events from a specific Google calendar.
     *
     * @return Collection<int, CalendarEvent>
     */
    public function getEvents(string $calendarKey, Carbon $start, Carbon $end): Collection
    {
        $config = config("calendar.google_calendars.{$calendarKey}");

        if (! $config || empty($config['url'])) {
            return collect();
        }

        $cacheKey = "google_calendar_{$calendarKey}";
        $cacheTtl = $config['cache_ttl'] ?? 3600;

        $icalData = Cache::remember($cacheKey, $cacheTtl, function () use ($config, $calendarKey) {
            return $this->fetchIcalFeed($config['url'], $calendarKey);
        });

        if (! $icalData) {
            return collect();
        }

        return $this->parseIcalData($icalData, $calendarKey, $config, $start, $end);
    }

    /**
     * Fetch the iCal feed from URL.
     */
    protected function fetchIcalFeed(string $url, string $calendarKey): ?string
    {
        try {
            $response = Http::timeout(30)->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            Log::warning("Failed to fetch Google Calendar feed [{$calendarKey}]", [
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Error fetching Google Calendar feed [{$calendarKey}]", [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Parse iCal data into CalendarEvent objects.
     *
     * @return Collection<int, CalendarEvent>
     */
    protected function parseIcalData(
        string $icalData,
        string $calendarKey,
        array $config,
        Carbon $start,
        Carbon $end
    ): Collection {
        $events = collect();

        try {
            $vcalendar = Reader::read($icalData);

            if (! $vcalendar || ! isset($vcalendar->VEVENT)) {
                return $events;
            }

            foreach ($vcalendar->VEVENT as $vevent) {
                $event = $this->parseEvent($vevent, $calendarKey, $config);

                if (! $event) {
                    continue;
                }

                // Filter by date range
                if ($event->starts_at->lte($end) && $event->ends_at->gte($start)) {
                    $events->push($event);
                }
            }
        } catch (\Exception $e) {
            Log::error("Error parsing iCal data [{$calendarKey}]", [
                'error' => $e->getMessage(),
            ]);
        }

        return $events;
    }

    /**
     * Parse a single VEVENT into a CalendarEvent.
     */
    protected function parseEvent($vevent, string $calendarKey, array $config): ?CalendarEvent
    {
        try {
            $dtstart = $vevent->DTSTART;
            $dtend = $vevent->DTEND;

            if (! $dtstart) {
                return null;
            }

            // Determine if it's an all-day event
            $isAllDay = isset($dtstart['VALUE']) && (string) $dtstart['VALUE'] === 'DATE';

            // Parse start date/time
            $startsAt = $this->parseDateTime($dtstart, $isAllDay);

            // Parse end date/time
            if ($dtend) {
                $endsAt = $this->parseDateTime($dtend, $isAllDay);
                // For all-day events, the end date is exclusive, so subtract a day
                if ($isAllDay) {
                    $endsAt = $endsAt->subDay();
                }
            } else {
                // If no end time, assume same as start (for all-day events) or +1 hour
                $endsAt = $isAllDay ? $startsAt->copy() : $startsAt->copy()->addHour();
            }

            return new CalendarEvent(
                id: (string) ($vevent->UID ?? uniqid()),
                title: (string) ($vevent->SUMMARY ?? 'Ingen tittel'),
                description: (string) ($vevent->DESCRIPTION ?? ''),
                location: (string) ($vevent->LOCATION ?? ''),
                starts_at: $startsAt,
                ends_at: $endsAt,
                is_all_day: $isAllDay,
                calendar_key: $calendarKey,
                calendar_label: $config['label'] ?? $calendarKey,
                color: $config['color'] ?? '#6b7280',
            );
        } catch (\Exception $e) {
            Log::warning("Failed to parse calendar event [{$calendarKey}]", [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Parse a DTSTART/DTEND value to Carbon.
     */
    protected function parseDateTime($dt, bool $isAllDay): Carbon
    {
        $dateTime = $dt->getDateTime();

        if ($isAllDay) {
            return Carbon::parse($dateTime->format('Y-m-d'), 'Europe/Oslo')->startOfDay();
        }

        // Convert to Oslo timezone
        return Carbon::parse($dateTime)->setTimezone('Europe/Oslo');
    }

    /**
     * Clear cache for a specific calendar or all calendars.
     */
    public function clearCache(?string $calendarKey = null): void
    {
        if ($calendarKey) {
            Cache::forget("google_calendar_{$calendarKey}");
        } else {
            foreach (array_keys(config('calendar.google_calendars', [])) as $key) {
                Cache::forget("google_calendar_{$key}");
            }
        }
    }
}
