<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Bpa;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bpa\AssistantResource;
use App\Http\Resources\Bpa\CalendarEventResource;
use App\Http\Resources\Bpa\ShiftResource;
use App\Models\Assistant;
use App\Models\Setting;
use App\Models\Shift;
use App\Services\CalendarYearService;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CalendarDataController extends Controller
{
    public function __construct(
        private readonly GoogleCalendarService $googleCalendarService,
        private readonly CalendarYearService $calendarYearService,
    ) {}

    /**
     * Get shifts for the given period and view type.
     * Returns both a flat list and a date-keyed map.
     *
     * GET /api/bpa/calendar/shifts
     */
    public function shifts(Request $request): JsonResponse
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'day' => ['sometimes', 'integer', 'min:1', 'max:31'],
            'view' => ['sometimes', 'in:month,week,day'],
        ]);

        $year = (int) $request->input('year');
        $month = (int) $request->input('month');
        $day = (int) $request->input('day', 1);
        $view = $request->input('view', 'month');

        [$startDate, $endDate, $cacheKey] = $this->resolveDateRangeAndCacheKey($year, $month, $day, $view, 'shifts');

        $shifts = Cache::remember($cacheKey, now()->addHours(24), function () use ($startDate, $endDate) {
            return Shift::query()
                ->with('assistant')
                ->where('starts_at', '<=', $endDate)
                ->where('ends_at', '>=', $startDate)
                ->orderBy('starts_at')
                ->get();
        });

        $byDateCacheKey = $cacheKey . '-by-date';
        $shiftsByDate = Cache::remember($byDateCacheKey, now()->addHours(24), function () use ($shifts, $startDate, $endDate) {
            return $this->groupShiftsByDate($shifts, $startDate, $endDate);
        });

        $shiftsResourced = ShiftResource::collection($shifts);

        $shiftsByDateResourced = collect($shiftsByDate)->map(
            fn ($dayShifts) => ShiftResource::collection($dayShifts)
        );

        return response()->json([
            'shifts' => $shiftsResourced,
            'shifts_by_date' => $shiftsByDateResourced,
        ]);
    }

    /**
     * Get external Google Calendar events for the given period.
     *
     * GET /api/bpa/calendar/external-events
     */
    public function externalEvents(Request $request): JsonResponse
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'day' => ['sometimes', 'integer', 'min:1', 'max:31'],
            'view' => ['sometimes', 'in:month,week,day'],
        ]);

        $year = (int) $request->input('year');
        $month = (int) $request->input('month');
        $day = (int) $request->input('day', 1);
        $view = $request->input('view', 'month');

        [$startDate, $endDate, $cacheKey] = $this->resolveDateRangeAndCacheKey($year, $month, $day, $view, 'external-events');

        $events = Cache::remember($cacheKey, now()->addHours(24), function () use ($startDate, $endDate) {
            return $this->googleCalendarService->getAllEvents($startDate, $endDate);
        });

        $byDateCacheKey = $cacheKey . '-by-date';
        $eventsByDate = Cache::remember($byDateCacheKey, now()->addHours(24), function () use ($events) {
            return $this->groupExternalEventsByDate($events);
        });

        $eventsResourced = CalendarEventResource::collection($events);

        $eventsByDateResourced = collect($eventsByDate)->map(
            fn ($dayEvents) => CalendarEventResource::collection(collect($dayEvents))
        );

        return response()->json([
            'events' => $eventsResourced,
            'events_by_date' => $eventsByDateResourced,
        ]);
    }

    /**
     * Get all active assistants.
     *
     * GET /api/bpa/calendar/assistants
     */
    public function assistants(): JsonResponse
    {
        $assistants = Cache::remember('bpa-assistants', now()->addHours(24), function () {
            return Assistant::query()
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get();
        });

        return response()->json([
            'assistants' => AssistantResource::collection($assistants),
        ]);
    }

    /**
     * Get remaining hours quota data for the given year.
     *
     * GET /api/bpa/calendar/remaining-hours
     */
    public function remainingHours(Request $request): JsonResponse
    {
        $request->validate([
            'year' => ['sometimes', 'integer', 'min:2000', 'max:2100'],
        ]);

        $year = (int) $request->input('year', Carbon::now()->year);

        $hoursPerWeek = Setting::getBpaHoursPerWeek();
        $yearlyQuotaMinutes = $hoursPerWeek * 52 * 60;

        $usedMinutes = (int) Shift::query()
            ->worked()
            ->forYear($year)
            ->sum('duration_minutes');

        $remainingMinutes = (int) ($yearlyQuotaMinutes - $usedMinutes);
        $percentageUsed = $yearlyQuotaMinutes > 0
            ? round(($usedMinutes / $yearlyQuotaMinutes) * 100, 1)
            : 0;

        return response()->json([
            'hours_per_week' => $hoursPerWeek,
            'total_minutes' => $yearlyQuotaMinutes,
            'used_minutes' => $usedMinutes,
            'remaining_minutes' => $remainingMinutes,
            'formatted_remaining' => $this->formatMinutes($remainingMinutes),
            'percentage_used' => $percentageUsed,
        ]);
    }

    /**
     * Get available years from shift data.
     *
     * GET /api/bpa/calendar/available-years
     */
    public function availableYears(): JsonResponse
    {
        $years = $this->calendarYearService->getAvailableYears();

        return response()->json(['years' => $years]);
    }

    /**
     * Get the calendar grid structure for a month.
     * Returns weeks with days including metadata.
     *
     * GET /api/bpa/calendar/days
     */
    public function calendarDays(Request $request): JsonResponse
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $year = (int) $request->input('year');
        $month = (int) $request->input('month');

        $days = $this->buildCalendarDays($year, $month);
        $weeks = array_chunk($days, 7);

        $result = array_map(function (array $week) {
            return [
                'weekNumber' => $week[0]['weekNumber'],
                'days' => $week,
            ];
        }, $weeks);

        return response()->json(['weeks' => $result]);
    }

    /**
     * Resolve the date range and cache key for the given view.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function resolveDateRangeAndCacheKey(
        int $year,
        int $month,
        int $day,
        string $view,
        string $type
    ): array {
        return match ($view) {
            'day' => [
                Carbon::create($year, $month, $day)->startOfDay(),
                Carbon::create($year, $month, $day)->endOfDay(),
                "calendar-day-{$type}-{$year}-{$month}-{$day}",
            ],
            'week' => [
                Carbon::create($year, $month, $day)->startOfWeek(Carbon::MONDAY),
                Carbon::create($year, $month, $day)->endOfWeek(Carbon::SUNDAY),
                "calendar-week-{$type}-{$year}-{$month}-{$day}",
            ],
            default => [
                Carbon::create($year, $month, 1)->startOfWeek(Carbon::MONDAY),
                Carbon::create($year, $month, 1)->endOfMonth()->endOfWeek(Carbon::SUNDAY),
                "calendar-month-{$type}-{$year}-{$month}",
            ],
        };
    }

    /**
     * Group shifts by date string.
     * Multi-day all-day absences are expanded to each day they span.
     *
     * @param  Collection<int, Shift>  $shifts
     * @return array<string, array<Shift>>
     */
    private function groupShiftsByDate(Collection $shifts, Carbon $visibleStart, Carbon $visibleEnd): array
    {
        $grouped = [];

        foreach ($shifts as $shift) {
            if ($shift->is_unavailable && $shift->is_all_day) {
                $startDate = $shift->starts_at->copy()->startOfDay();
                $endDate = $shift->ends_at->copy()->startOfDay();

                if ($startDate->format('Y-m-d') !== $endDate->format('Y-m-d')) {
                    $currentDate = $startDate->copy();

                    while ($currentDate->lte($endDate)) {
                        if ($currentDate->gte($visibleStart) && $currentDate->lte($visibleEnd)) {
                            $date = $currentDate->format('Y-m-d');
                            $grouped[$date] ??= [];
                            $grouped[$date][] = $shift;
                        }

                        $currentDate->addDay();
                    }

                    continue;
                }
            }

            $date = $shift->starts_at->format('Y-m-d');
            $grouped[$date] ??= [];
            $grouped[$date][] = $shift;
        }

        return $grouped;
    }

    /**
     * Group external events by date string.
     * Multi-day all-day events are expanded to each day they span.
     *
     * @param  Collection<int, \App\Services\CalendarEvent>  $events
     * @return array<string, array<\App\Services\CalendarEvent>>
     */
    private function groupExternalEventsByDate(Collection $events): array
    {
        $grouped = [];

        foreach ($events as $event) {
            if ($event->is_all_day) {
                $startDate = $event->starts_at->copy()->startOfDay();
                $endDate = $event->ends_at->copy()->startOfDay();

                if ($startDate->format('Y-m-d') !== $endDate->format('Y-m-d')) {
                    $currentDate = $startDate->copy();

                    while ($currentDate->lte($endDate)) {
                        $date = $currentDate->format('Y-m-d');
                        $grouped[$date] ??= [];
                        $grouped[$date][] = $event;
                        $currentDate->addDay();
                    }

                    continue;
                }
            }

            $date = $event->starts_at->format('Y-m-d');
            $grouped[$date] ??= [];
            $grouped[$date][] = $event;
        }

        return $grouped;
    }

    /**
     * Build the flat calendar days array for a given month.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildCalendarDays(int $year, int $month): array
    {
        $firstOfMonth = Carbon::create($year, $month, 1);
        $lastOfMonth = $firstOfMonth->copy()->endOfMonth();
        $startDate = $firstOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $endDate = $lastOfMonth->copy()->endOfWeek(Carbon::SUNDAY);

        $today = Carbon::now('Europe/Oslo')->format('Y-m-d');
        $days = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $days[] = [
                'date' => $current->format('Y-m-d'),
                'dayOfMonth' => $current->day,
                'isCurrentMonth' => $current->month === $month,
                'isToday' => $current->format('Y-m-d') === $today,
                'isWeekend' => $current->isWeekend(),
                'weekNumber' => $current->isoWeek(),
                'dayOfWeek' => $current->dayOfWeekIso,
            ];

            $current->addDay();
        }

        return $days;
    }

    /**
     * Format minutes into H:MM display string.
     */
    private function formatMinutes(int $minutes): string
    {
        $sign = $minutes < 0 ? '-' : '';
        $abs = abs($minutes);
        $hours = intdiv($abs, 60);
        $mins = $abs % 60;

        return sprintf('%s%d:%02d', $sign, $hours, $mins);
    }
}
