<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CalendarYearService
{
    private const HISTORICAL_CACHE_KEY = 'calendar.years.historical';

    private const CURRENT_YEAR_CACHE_KEY = 'calendar.years.current';

    /**
     * Get all available years from shifts, with intelligent caching.
     *
     * Strategy:
     * - Historical years (< currentYear): cached forever
     * - Current year: always included (users need to see current year even without shifts)
     * - Uses whereBetween per year for index optimization
     *
     * @return array<int>
     */
    public function getAvailableYears(): array
    {
        $currentYear = Carbon::now('Europe/Oslo')->year;

        // Get historical years (< currentYear) from forever cache
        $historicalYears = $this->getHistoricalYears($currentYear);

        // Always include current year (users need to see it even if no shifts exist yet)
        $allYears = array_unique(array_merge($historicalYears, [$currentYear]));
        sort($allYears);

        return $allYears;
    }

    /**
     * Get all years before the current year from cache.
     * These are cached forever since they never change.
     *
     * @return array<int>
     */
    private function getHistoricalYears(int $currentYear): array
    {
        return Cache::rememberForever(self::HISTORICAL_CACHE_KEY, function () use ($currentYear) {
            // Get the earliest shift to determine start year
            $earliestShift = Shift::query()
                ->withTrashed()
                ->orderBy('starts_at')
                ->first();

            if (! $earliestShift) {
                return [];
            }

            $startYear = $earliestShift->starts_at->year;
            $years = [];

            // Query each year from earliest to (currentYear - 1)
            for ($year = $startYear; $year < $currentYear; $year++) {
                if ($this->yearHasShiftsUncached($year)) {
                    $years[] = $year;
                }
            }

            return $years;
        });
    }

    /**
     * Check if a specific year has any shifts using whereBetween.
     * This is index-friendly and includes soft-deleted shifts.
     */
    private function yearHasShiftsUncached(int $year): bool
    {
        $startDate = Carbon::createFromDate($year, 1, 1, 'Europe/Oslo')->startOfYear();
        $endDate = Carbon::createFromDate($year, 12, 31, 'Europe/Oslo')->endOfYear();

        return Shift::query()
            ->withTrashed()
            ->whereBetween('starts_at', [$startDate, $endDate])
            ->exists();
    }

    /**
     * Invalidate all year caches when shifts are created or deleted.
     * Called from Shift model event listeners.
     */
    public function invalidateYearCache(): void
    {
        Cache::forget(self::HISTORICAL_CACHE_KEY);
        Cache::forget(self::CURRENT_YEAR_CACHE_KEY);
    }

    /**
     * Invalidate only current year cache.
     * Note: Currently not used since current year is always included.
     * Reserved for future optimization if current year caching is needed.
     */
    public function invalidateCurrentYearCache(): void
    {
        Cache::forget(self::CURRENT_YEAR_CACHE_KEY);
    }
}
