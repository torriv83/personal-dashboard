<?php

namespace App\Livewire\Bpa\Calendar\Concerns;

use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Str;

trait HandlesRecurringShifts
{
    /**
     * Get preview dates for recurring unavailability.
     *
     * @return array<string>
     */
    public function getRecurringPreviewDates(): array
    {
        if (! $this->isRecurring || ! $this->fromDate) {
            return [];
        }

        $dates = [];
        $startDate = Carbon::parse($this->fromDate);

        $count = match ($this->recurringEndType) {
            'count' => $this->recurringCount,
            'date' => 52, // Max 1 year
            default => 4,
        };

        $endDate = $this->recurringEndType === 'date' && $this->recurringEndDate
            ? Carbon::parse($this->recurringEndDate)
            : null;

        $current = $startDate->copy();

        for ($i = 0; $i < $count; $i++) {
            if ($endDate && $current->gt($endDate)) {
                break;
            }

            $dates[] = $current->format('Y-m-d');

            $current = match ($this->recurringInterval) {
                'weekly' => $current->copy()->addWeek(),
                'biweekly' => $current->copy()->addWeeks(2),
                'monthly' => $this->addMonthKeepingDay($current, $startDate->day),
                default => $current->copy()->addWeek(),
            };
        }

        return $dates;
    }

    /**
     * Add a month while keeping the same day of month (or last day if not available).
     */
    private function addMonthKeepingDay(Carbon $date, int $originalDay): Carbon
    {
        $next = $date->copy()->addMonth();
        $daysInMonth = $next->daysInMonth;

        // If original day was higher than days in this month, use last day
        $targetDay = min($originalDay, $daysInMonth);
        $next->day = $targetDay;

        return $next;
    }

    /**
     * Create multiple shifts for recurring unavailability.
     *
     * @param  array<string, mixed>  $baseData
     */
    private function createRecurringShifts(array $baseData): void
    {
        $dates = $this->getRecurringPreviewDates();

        if (empty($dates)) {
            Shift::create($baseData);
            $this->dispatch('toast', type: 'success', message: 'Vakten ble opprettet');

            return;
        }

        $recurringGroupId = Str::uuid()->toString();
        $baseStartsAt = $baseData['starts_at'];
        $baseEndsAt = $baseData['ends_at'];
        $startTime = $baseStartsAt->format('H:i:s');
        $endTime = $baseEndsAt->format('H:i:s');

        foreach ($dates as $date) {
            $startsAt = $this->isAllDay
                ? Carbon::parse($date)->startOfDay()
                : Carbon::parse($date.' '.$startTime);

            $endsAt = $this->isAllDay
                ? Carbon::parse($date)->endOfDay()
                : Carbon::parse($date.' '.$endTime);

            Shift::create([
                ...$baseData,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'recurring_group_id' => $recurringGroupId,
            ]);
        }

        $count = count($dates);
        $this->dispatch('toast', type: 'success', message: "{$count} utilgjengelig-oppføringer ble opprettet");
    }

    /**
     * Initiate edit - check if recurring and show dialog if needed.
     */
    public function initiateEditRecurring(): void
    {
        if (! $this->editingShiftId) {
            return;
        }

        $shift = Shift::findOrFail($this->editingShiftId);

        if ($shift->isRecurring()) {
            $this->recurringAction = 'edit';
            $this->showRecurringDialog = true;
        }
        // If not recurring, just proceed with normal edit (already in modal)
    }

    /**
     * Confirm and apply edit to recurring shifts based on scope.
     */
    public function confirmEditRecurring(string $scope): void
    {
        if (! $this->editingShiftId) {
            return;
        }

        $this->validate([
            'assistantId' => 'required|exists:assistants,id',
            'fromDate' => 'required|date',
            'toDate' => 'required|date|after_or_equal:fromDate',
            'fromTime' => 'required_unless:isAllDay,true',
            'toTime' => 'required_unless:isAllDay,true',
        ]);

        $shift = Shift::findOrFail($this->editingShiftId);

        $startsAt = $this->isAllDay
            ? Carbon::parse($this->fromDate)->startOfDay()
            : Carbon::parse($this->fromDate.' '.$this->fromTime);

        $endsAt = $this->isAllDay
            ? Carbon::parse($this->toDate)->endOfDay()
            : Carbon::parse($this->toDate.' '.$this->toTime);

        $data = [
            'assistant_id' => $this->assistantId,
            'is_unavailable' => $this->isUnavailable,
            'is_all_day' => $this->isAllDay,
            'note' => $this->note ?: null,
        ];

        $updatedCount = match ($scope) {
            'single' => $this->updateSingleShift($shift, $data, $startsAt, $endsAt),
            'future' => $this->updateFutureShifts($shift, $data, $startsAt, $endsAt),
            'all' => $this->updateAllRecurringShifts($shift, $data, $startsAt, $endsAt),
            default => $this->updateSingleShift($shift, $data, $startsAt, $endsAt),
        };

        // Clear computed property cache
        unset($this->shifts, $this->shiftsByDate);

        $this->showRecurringDialog = false;
        $this->closeModal();

        $message = $updatedCount > 1
            ? "{$updatedCount} oppføringer ble oppdatert"
            : 'Oppføringen ble oppdatert';

        $this->dispatch('toast', type: 'success', message: $message);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function updateSingleShift(Shift $shift, array $data, Carbon $startsAt, Carbon $endsAt): int
    {
        // When updating single, remove from recurring group
        $shift->update([
            ...$data,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'recurring_group_id' => null,
        ]);

        return 1;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function updateFutureShifts(Shift $shift, array $data, Carbon $startsAt, Carbon $endsAt): int
    {
        if (! $shift->isRecurring()) {
            return $this->updateSingleShift($shift, $data, $startsAt, $endsAt);
        }

        $futureShifts = $shift->getFutureRecurringShifts();
        $count = $futureShifts->count();

        // Calculate time difference from original
        $newStartTime = $startsAt->format('H:i:s');
        $newEndTime = $endsAt->format('H:i:s');

        foreach ($futureShifts as $futureShift) {
            $shiftStartsAt = $this->isAllDay
                ? Carbon::parse($futureShift->starts_at->format('Y-m-d'))->startOfDay()
                : Carbon::parse($futureShift->starts_at->format('Y-m-d').' '.$newStartTime);

            $shiftEndsAt = $this->isAllDay
                ? Carbon::parse($futureShift->ends_at->format('Y-m-d'))->endOfDay()
                : Carbon::parse($futureShift->ends_at->format('Y-m-d').' '.$newEndTime);

            $futureShift->update([
                ...$data,
                'starts_at' => $shiftStartsAt,
                'ends_at' => $shiftEndsAt,
            ]);
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function updateAllRecurringShifts(Shift $shift, array $data, Carbon $startsAt, Carbon $endsAt): int
    {
        if (! $shift->isRecurring()) {
            return $this->updateSingleShift($shift, $data, $startsAt, $endsAt);
        }

        $allShifts = $shift->getRecurringGroupShifts();
        $count = $allShifts->count();

        $newStartTime = $startsAt->format('H:i:s');
        $newEndTime = $endsAt->format('H:i:s');

        foreach ($allShifts as $groupShift) {
            $shiftStartsAt = $this->isAllDay
                ? Carbon::parse($groupShift->starts_at->format('Y-m-d'))->startOfDay()
                : Carbon::parse($groupShift->starts_at->format('Y-m-d').' '.$newStartTime);

            $shiftEndsAt = $this->isAllDay
                ? Carbon::parse($groupShift->ends_at->format('Y-m-d'))->endOfDay()
                : Carbon::parse($groupShift->ends_at->format('Y-m-d').' '.$newEndTime);

            $groupShift->update([
                ...$data,
                'starts_at' => $shiftStartsAt,
                'ends_at' => $shiftEndsAt,
            ]);
        }

        return $count;
    }
}
