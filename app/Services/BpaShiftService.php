<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Assistant;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BpaShiftService
{
    public function __construct(
        private readonly BpaQuotaService $quotaService,
    ) {}

    /**
     * Create a single shift after validating overlaps and quota.
     *
     * @return array{shift: Shift|null, error: string|null}
     */
    public function createShift(
        int $assistantId,
        Carbon $startsAt,
        Carbon $endsAt,
        bool $isUnavailable,
        bool $isAllDay,
        ?string $note = null,
        ?int $excludeShiftId = null
    ): array {
        if (! $isUnavailable) {
            $conflict = Shift::findOverlappingUnavailability($assistantId, $startsAt, $endsAt, $excludeShiftId);

            if ($conflict) {
                $assistant = Assistant::find($assistantId);
                $conflictTime = $conflict->is_all_day
                    ? $conflict->starts_at->format('d.m.Y') . ' (hele dagen)'
                    : $conflict->starts_at->format('d.m.Y H:i') . ' - ' . $conflict->ends_at->format('H:i');

                return [
                    'shift' => null,
                    'error' => "{$assistant?->name} er borte: {$conflictTime}",
                ];
            }

            if (! $isAllDay) {
                $quotaResult = $this->quotaService->validateShiftQuota($startsAt, $endsAt, $excludeShiftId);

                if (! $quotaResult['valid']) {
                    return [
                        'shift' => null,
                        'error' => $quotaResult['error'],
                    ];
                }
            }
        }

        $shift = Shift::create([
            'assistant_id' => $assistantId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_unavailable' => $isUnavailable,
            'is_all_day' => $isAllDay,
            'note' => $note ?: null,
        ]);

        return ['shift' => $shift, 'error' => null];
    }

    /**
     * Create recurring shifts for unavailability entries.
     *
     * @param  array<string, mixed>  $baseData
     * @return array{shifts: Collection<int, Shift>, count: int, error: string|null}
     */
    public function createRecurringShifts(
        array $baseData,
        string $recurringInterval,
        string $recurringEndType,
        int $recurringCount,
        ?string $recurringEndDate,
        bool $isAllDay,
        bool $skipFirstDate = false,
        ?int $existingShiftId = null
    ): array {
        $dates = $this->generateRecurringDates(
            $baseData['starts_at'],
            $recurringInterval,
            $recurringEndType,
            $recurringCount,
            $recurringEndDate
        );

        if ($skipFirstDate && ! empty($dates)) {
            array_shift($dates);
        }

        if (empty($dates)) {
            return ['shifts' => new Collection, 'count' => 0, 'error' => null];
        }

        $recurringGroupId = Str::uuid()->toString();
        $baseStartsAt = $baseData['starts_at'];
        $baseEndsAt = $baseData['ends_at'];
        $startTime = $baseStartsAt->format('H:i:s');
        $endTime = $baseEndsAt->format('H:i:s');

        // If editing an existing shift, also assign it the recurring group ID
        if ($skipFirstDate && $existingShiftId) {
            Shift::where('id', $existingShiftId)->update(['recurring_group_id' => $recurringGroupId]);
        }

        /** @var Collection<int, Shift> $shifts */
        $shifts = new Collection;

        foreach ($dates as $date) {
            $startsAt = $isAllDay
                ? Carbon::parse($date)->startOfDay()
                : Carbon::parse($date . ' ' . $startTime);

            $endsAt = $isAllDay
                ? Carbon::parse($date)->endOfDay()
                : Carbon::parse($date . ' ' . $endTime);

            $shift = Shift::create([
                ...$baseData,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'recurring_group_id' => $recurringGroupId,
            ]);

            $shifts->push($shift);
        }

        return ['shifts' => $shifts, 'count' => $shifts->count(), 'error' => null];
    }

    /**
     * Update a shift with recurring scope support.
     *
     * @param  array<string, mixed>  $data
     * @return array{count: int, error: string|null}
     */
    public function updateShift(
        Shift $shift,
        array $data,
        Carbon $startsAt,
        Carbon $endsAt,
        bool $isAllDay,
        string $scope = 'single'
    ): array {
        $updatedCount = match ($scope) {
            'single' => $this->updateSingleShift($shift, $data, $startsAt, $endsAt),
            'future' => $this->updateFutureShifts($shift, $data, $startsAt, $endsAt, $isAllDay),
            'all' => $this->updateAllRecurringShifts($shift, $data, $startsAt, $endsAt, $isAllDay),
            default => $this->updateSingleShift($shift, $data, $startsAt, $endsAt),
        };

        return ['count' => $updatedCount, 'error' => null];
    }

    /**
     * Delete a shift with recurring scope support.
     *
     * @return array{count: int}
     */
    public function deleteShift(Shift $shift, string $scope = 'single', bool $forceDelete = true): array
    {
        $deletedCount = match ($scope) {
            'single' => $this->deleteSingleShift($shift, $forceDelete),
            'future' => $this->deleteFutureShifts($shift, $forceDelete),
            'all' => $this->deleteAllRecurringShifts($shift, $forceDelete),
            default => $this->deleteSingleShift($shift, $forceDelete),
        };

        return ['count' => $deletedCount];
    }

    /**
     * Move a shift to a new date/time with recurring scope support.
     *
     * @return array{shift: Shift|null, count: int, error: string|null}
     */
    public function moveShift(
        Shift $shift,
        string $newDate,
        ?string $newTime,
        string $scope = 'single'
    ): array {
        $daysDiff = (int) Carbon::parse($shift->starts_at->format('Y-m-d'))
            ->diffInDays(Carbon::parse($newDate), false);

        [$count, $error, $movedShift] = match ($scope) {
            'single' => $this->moveSingleShiftInternal($shift, $newDate, $newTime),
            'future' => $this->moveFutureShiftsInternal($shift, $daysDiff, $newTime),
            'all' => $this->moveAllRecurringShiftsInternal($shift, $daysDiff, $newTime),
            default => $this->moveSingleShiftInternal($shift, $newDate, $newTime),
        };

        return ['shift' => $movedShift, 'count' => $count, 'error' => $error];
    }

    /**
     * Resize a shift (change duration by adjusting ends_at).
     *
     * @return array{shift: Shift|null, error: string|null}
     */
    public function resizeShift(Shift $shift, int $durationMinutes): array
    {
        if ($shift->is_all_day) {
            return ['shift' => null, 'error' => 'Kan ikke endre varighet på heldagsvakter'];
        }

        $durationMinutes = max(15, $durationMinutes);
        $newEndsAt = $shift->starts_at->copy()->addMinutes($durationMinutes);

        if (! $shift->is_unavailable) {
            $conflict = Shift::findOverlappingUnavailability(
                $shift->assistant_id,
                $shift->starts_at,
                $newEndsAt,
                $shift->id
            );

            if ($conflict) {
                $conflictTime = $conflict->is_all_day
                    ? $conflict->starts_at->format('d.m.Y') . ' (hele dagen)'
                    : $conflict->starts_at->format('d.m.Y H:i') . ' - ' . $conflict->ends_at->format('H:i');

                return [
                    'shift' => null,
                    'error' => "{$shift->assistant?->name} er borte: {$conflictTime}",
                ];
            }
        }

        $shift->update(['ends_at' => $newEndsAt]);
        $shift->refresh();

        return ['shift' => $shift, 'error' => null];
    }

    /**
     * Duplicate a shift to a target date.
     */
    public function duplicateShift(Shift $shift, ?string $targetDate = null): Shift
    {
        $targetDate ??= $shift->starts_at->format('Y-m-d');
        $daysDiff = Carbon::parse($shift->starts_at->format('Y-m-d'))->diffInDays(Carbon::parse($targetDate), false);

        return Shift::create([
            'assistant_id' => $shift->assistant_id,
            'starts_at' => $shift->starts_at->copy()->addDays($daysDiff),
            'ends_at' => $shift->ends_at->copy()->addDays($daysDiff),
            'is_unavailable' => $shift->is_unavailable,
            'is_all_day' => $shift->is_all_day,
            'note' => $shift->note,
        ]);
    }

    /**
     * Invalidate calendar caches for the given date context.
     */
    public function invalidateCalendarCache(int $year, int $month, int $day): void
    {
        // Invalidate month caches
        // Cache keys are built as: "{base-key}-by-date" where base = "calendar-{view}-{type}-{year}-{month}"
        Cache::forget("calendar-month-shifts-{$year}-{$month}");
        Cache::forget("calendar-month-shifts-{$year}-{$month}-by-date");
        Cache::forget("calendar-month-external-events-{$year}-{$month}");
        Cache::forget("calendar-month-external-events-{$year}-{$month}-by-date");

        // Invalidate week caches (current week and adjacent weeks)
        for ($d = $day - 7; $d <= $day + 7; $d++) {
            $date = Carbon::create($year, $month, 1)->addDays($d - 1);
            $cacheYear = $date->year;
            $cacheMonth = $date->month;
            $cacheDay = $date->day;

            Cache::forget("calendar-week-shifts-{$cacheYear}-{$cacheMonth}-{$cacheDay}");
            Cache::forget("calendar-week-shifts-{$cacheYear}-{$cacheMonth}-{$cacheDay}-by-date");
            Cache::forget("calendar-week-external-events-{$cacheYear}-{$cacheMonth}-{$cacheDay}");
            Cache::forget("calendar-week-external-events-{$cacheYear}-{$cacheMonth}-{$cacheDay}-by-date");
        }

        // Invalidate day caches (current day and adjacent days)
        for ($d = $day - 1; $d <= $day + 1; $d++) {
            $date = Carbon::create($year, $month, 1)->addDays($d - 1);
            $cacheYear = $date->year;
            $cacheMonth = $date->month;
            $cacheDay = $date->day;

            Cache::forget("calendar-day-shifts-{$cacheYear}-{$cacheMonth}-{$cacheDay}");
            Cache::forget("calendar-day-shifts-{$cacheYear}-{$cacheMonth}-{$cacheDay}-by-date");
            Cache::forget("calendar-day-external-events-{$cacheYear}-{$cacheMonth}-{$cacheDay}");
            Cache::forget("calendar-day-external-events-{$cacheYear}-{$cacheMonth}-{$cacheDay}-by-date");
        }
    }

    /**
     * Execute move on a single shift (removes from recurring group).
     *
     * @return array{0: int, 1: string|null, 2: Shift|null}
     */
    private function moveSingleShiftInternal(Shift $shift, string $newDate, ?string $newTime): array
    {
        // Remove from recurring group when moving single
        $shift->recurring_group_id = null;
        $result = $this->executeMoveShift($shift, $newDate, $newTime);

        if ($result['error']) {
            return [0, $result['error'], null];
        }

        $shift->refresh();

        return [1, null, $shift];
    }

    /**
     * Move future shifts in the recurring group.
     *
     * @return array{0: int, 1: string|null, 2: Shift|null}
     */
    private function moveFutureShiftsInternal(Shift $shift, int $daysDiff, ?string $newTime): array
    {
        if (! $shift->isRecurring()) {
            return $this->moveSingleShiftInternal($shift, $shift->starts_at->copy()->addDays($daysDiff)->format('Y-m-d'), $newTime);
        }

        $futureShifts = $shift->getFutureRecurringShifts();
        $count = $futureShifts->count();

        foreach ($futureShifts as $futureShift) {
            $newDate = $futureShift->starts_at->copy()->addDays($daysDiff)->format('Y-m-d');
            $this->executeMoveShift($futureShift, $newDate, $newTime);
        }

        $shift->refresh();

        return [$count, null, $shift];
    }

    /**
     * Move all shifts in the recurring group.
     *
     * @return array{0: int, 1: string|null, 2: Shift|null}
     */
    private function moveAllRecurringShiftsInternal(Shift $shift, int $daysDiff, ?string $newTime): array
    {
        if (! $shift->isRecurring()) {
            return $this->moveSingleShiftInternal($shift, $shift->starts_at->copy()->addDays($daysDiff)->format('Y-m-d'), $newTime);
        }

        $allShifts = $shift->getRecurringGroupShifts();
        $count = $allShifts->count();

        foreach ($allShifts as $groupShift) {
            $newDate = $groupShift->starts_at->copy()->addDays($daysDiff)->format('Y-m-d');
            $this->executeMoveShift($groupShift, $newDate, $newTime);
        }

        $shift->refresh();

        return [$count, null, $shift];
    }

    /**
     * Execute the actual move operation on a shift.
     *
     * @return array{error: string|null}
     */
    private function executeMoveShift(Shift $shift, string $newDate, ?string $newTime): array
    {
        $oldStart = $shift->starts_at;
        $oldEnd = $shift->ends_at;
        $duration = (int) $oldStart->diffInMinutes($oldEnd);

        $newStart = $newTime
            ? Carbon::parse($newDate . ' ' . $newTime)
            : Carbon::parse($newDate . ' ' . $oldStart->format('H:i'));

        $newEnd = $newStart->copy()->addMinutes($duration);

        if (! $shift->is_unavailable) {
            $conflict = Shift::findOverlappingUnavailability(
                $shift->assistant_id,
                $newStart,
                $newEnd,
                $shift->id
            );

            if ($conflict) {
                $conflictTime = $conflict->is_all_day
                    ? $conflict->starts_at->format('d.m.Y') . ' (hele dagen)'
                    : $conflict->starts_at->format('d.m.Y H:i') . ' - ' . $conflict->ends_at->format('H:i');

                return ['error' => "{$shift->assistant?->name} er borte: {$conflictTime}"];
            }
        }

        $shift->update([
            'starts_at' => $newStart,
            'ends_at' => $newEnd,
        ]);

        return ['error' => null];
    }

    /**
     * Update a single shift (removes from recurring group).
     */
    private function updateSingleShift(Shift $shift, array $data, Carbon $startsAt, Carbon $endsAt): int
    {
        $shift->update([
            ...$data,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'recurring_group_id' => null,
        ]);

        return 1;
    }

    /**
     * Update future shifts in the recurring group.
     */
    private function updateFutureShifts(Shift $shift, array $data, Carbon $startsAt, Carbon $endsAt, bool $isAllDay): int
    {
        if (! $shift->isRecurring()) {
            return $this->updateSingleShift($shift, $data, $startsAt, $endsAt);
        }

        $futureShifts = $shift->getFutureRecurringShifts();
        $count = $futureShifts->count();
        $newStartTime = $startsAt->format('H:i:s');
        $newEndTime = $endsAt->format('H:i:s');

        foreach ($futureShifts as $futureShift) {
            $shiftStartsAt = $isAllDay
                ? Carbon::parse($futureShift->starts_at->format('Y-m-d'))->startOfDay()
                : Carbon::parse($futureShift->starts_at->format('Y-m-d') . ' ' . $newStartTime);

            $shiftEndsAt = $isAllDay
                ? Carbon::parse($futureShift->ends_at->format('Y-m-d'))->endOfDay()
                : Carbon::parse($futureShift->ends_at->format('Y-m-d') . ' ' . $newEndTime);

            $futureShift->update([
                ...$data,
                'starts_at' => $shiftStartsAt,
                'ends_at' => $shiftEndsAt,
            ]);
        }

        return $count;
    }

    /**
     * Update all shifts in the recurring group.
     */
    private function updateAllRecurringShifts(Shift $shift, array $data, Carbon $startsAt, Carbon $endsAt, bool $isAllDay): int
    {
        if (! $shift->isRecurring()) {
            return $this->updateSingleShift($shift, $data, $startsAt, $endsAt);
        }

        $allShifts = $shift->getRecurringGroupShifts();
        $count = $allShifts->count();
        $newStartTime = $startsAt->format('H:i:s');
        $newEndTime = $endsAt->format('H:i:s');

        foreach ($allShifts as $groupShift) {
            $shiftStartsAt = $isAllDay
                ? Carbon::parse($groupShift->starts_at->format('Y-m-d'))->startOfDay()
                : Carbon::parse($groupShift->starts_at->format('Y-m-d') . ' ' . $newStartTime);

            $shiftEndsAt = $isAllDay
                ? Carbon::parse($groupShift->ends_at->format('Y-m-d'))->endOfDay()
                : Carbon::parse($groupShift->ends_at->format('Y-m-d') . ' ' . $newEndTime);

            $groupShift->update([
                ...$data,
                'starts_at' => $shiftStartsAt,
                'ends_at' => $shiftEndsAt,
            ]);
        }

        return $count;
    }

    /**
     * Delete a single shift.
     */
    private function deleteSingleShift(Shift $shift, bool $forceDelete): int
    {
        if ($forceDelete) {
            $shift->forceDelete();
        } else {
            $shift->delete();
        }

        return 1;
    }

    /**
     * Delete future shifts in the recurring group.
     */
    private function deleteFutureShifts(Shift $shift, bool $forceDelete): int
    {
        if (! $shift->isRecurring()) {
            return $this->deleteSingleShift($shift, $forceDelete);
        }

        $futureShifts = $shift->getFutureRecurringShifts();
        $count = $futureShifts->count();

        foreach ($futureShifts as $futureShift) {
            if ($forceDelete) {
                $futureShift->forceDelete();
            } else {
                $futureShift->delete();
            }
        }

        return $count;
    }

    /**
     * Delete all shifts in the recurring group.
     */
    private function deleteAllRecurringShifts(Shift $shift, bool $forceDelete): int
    {
        if (! $shift->isRecurring()) {
            return $this->deleteSingleShift($shift, $forceDelete);
        }

        $allShifts = $shift->getRecurringGroupShifts();
        $count = $allShifts->count();

        foreach ($allShifts as $groupShift) {
            if ($forceDelete) {
                $groupShift->forceDelete();
            } else {
                $groupShift->delete();
            }
        }

        return $count;
    }

    /**
     * Generate dates for recurring shifts.
     *
     * @return array<string>
     */
    private function generateRecurringDates(
        Carbon $startDate,
        string $interval,
        string $endType,
        int $count,
        ?string $endDate
    ): array {
        $dates = [];

        $maxIterations = $endType === 'count' ? $count : 52;
        $endDateCarbon = ($endType === 'date' && $endDate) ? Carbon::parse($endDate) : null;

        $current = $startDate->copy();

        for ($i = 0; $i < $maxIterations; $i++) {
            if ($endDateCarbon && $current->gt($endDateCarbon)) {
                break;
            }

            $dates[] = $current->format('Y-m-d');

            $current = match ($interval) {
                'weekly' => $current->copy()->addWeek(),
                'biweekly' => $current->copy()->addWeeks(2),
                'monthly' => $this->addMonthKeepingDay($current, $startDate->day),
                default => $current->copy()->addWeek(),
            };
        }

        return $dates;
    }

    /**
     * Add a month while keeping the original day of month (or last day if not available).
     */
    private function addMonthKeepingDay(Carbon $date, int $originalDay): Carbon
    {
        $next = $date->copy()->addMonth();
        $targetDay = min($originalDay, $next->daysInMonth);
        $next->day = $targetDay;

        return $next;
    }
}
