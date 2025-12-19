<?php

namespace App\Livewire\Bpa\Calendar\Concerns;

use App\Models\Assistant;
use App\Models\Setting;
use App\Models\Shift;
use Carbon\Carbon;

trait HandlesShiftCrud
{
    /**
     * Open the modal for creating a new shift.
     */
    public function openModal(?string $date = null, ?string $time = null, ?int $assistantId = null, ?string $endTime = null, bool $isUnavailable = false): void
    {
        $this->resetForm();
        $this->showModal = true;

        if ($date) {
            $this->fromDate = $date;
            $this->toDate = $date;
        } else {
            $today = Carbon::now('Europe/Oslo')->format('Y-m-d');
            $this->fromDate = $today;
            $this->toDate = $today;
        }

        if ($time) {
            $this->fromTime = $time;

            if ($endTime) {
                $this->toTime = $endTime;
            } else {
                // Set end time 1 hour after start
                $parts = explode(':', $time);
                $endHour = min((int) $parts[0] + 1, 23);
                $this->toTime = sprintf('%02d:%s', $endHour, $parts[1] ?? '00');
            }
        }

        if ($assistantId) {
            $this->assistantId = $assistantId;
        }

        if ($isUnavailable) {
            $this->isUnavailable = true;
        }
    }

    /**
     * Open the modal for editing an existing shift.
     */
    public function editShift(int $shiftId): void
    {
        $shift = Shift::findOrFail($shiftId);

        $this->editingShiftId = $shiftId;
        $this->assistantId = $shift->assistant_id;
        $this->fromDate = $shift->starts_at->format('Y-m-d');
        $this->fromTime = $shift->starts_at->format('H:i');
        $this->toDate = $shift->ends_at->format('Y-m-d');
        $this->toTime = $shift->ends_at->format('H:i');
        $this->isUnavailable = $shift->is_unavailable;
        $this->isAllDay = $shift->is_all_day;
        $this->note = $shift->note ?? '';
        $this->isExistingRecurring = $shift->isRecurring();
        $this->showModal = true;
    }

    /**
     * Close the modal and reset form.
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    /**
     * Reset form to default values.
     */
    public function resetForm(): void
    {
        $this->editingShiftId = null;
        $this->assistantId = null;
        $this->fromDate = '';
        $this->fromTime = '08:00';
        $this->toDate = '';
        $this->toTime = '16:00';
        $this->isUnavailable = false;
        $this->isAllDay = false;
        $this->note = '';
        $this->isRecurring = false;
        $this->recurringInterval = 'weekly';
        $this->recurringEndType = 'count';
        $this->recurringCount = 4;
        $this->recurringEndDate = '';
        $this->showRecurringDialog = false;
        $this->recurringAction = '';
        $this->recurringScope = 'single';
        $this->isExistingRecurring = false;
        $this->pendingMoveDate = null;
        $this->pendingMoveTime = null;
    }

    /**
     * Save the shift (create or update).
     */
    public function saveShift(bool $createAnother = false): void
    {
        $this->validate([
            'assistantId' => 'required|exists:assistants,id',
            'fromDate' => 'required|date',
            'toDate' => 'required|date|after_or_equal:fromDate',
            'fromTime' => 'required_unless:isAllDay,true',
            'toTime' => 'required_unless:isAllDay,true',
        ], [
            'assistantId.required' => 'Velg en assistent',
            'assistantId.exists' => 'Ugyldig assistent',
            'fromDate.required' => 'Velg startdato',
            'toDate.required' => 'Velg sluttdato',
            'toDate.after_or_equal' => 'Sluttdato må være etter startdato',
        ]);

        $startsAt = $this->isAllDay
            ? Carbon::parse($this->fromDate)->startOfDay()
            : Carbon::parse($this->fromDate.' '.$this->fromTime);

        $endsAt = $this->isAllDay
            ? Carbon::parse($this->toDate)->endOfDay()
            : Carbon::parse($this->toDate.' '.$this->toTime);

        // Check for overlapping unavailability (only for work shifts, not unavailable entries)
        if (! $this->isUnavailable) {
            $conflict = Shift::findOverlappingUnavailability(
                $this->assistantId,
                $startsAt,
                $endsAt,
                $this->editingShiftId
            );

            if ($conflict) {
                $assistant = Assistant::find($this->assistantId);
                $conflictTime = $conflict->is_all_day
                    ? $conflict->starts_at->format('d.m.Y').' (hele dagen)'
                    : $conflict->starts_at->format('d.m.Y H:i').' - '.$conflict->ends_at->format('H:i');

                $this->dispatch('toast', type: 'error', message: "{$assistant->name} er borte: {$conflictTime}");

                return;
            }

            // Check if there are enough remaining hours (only for work shifts)
            $shiftDurationMinutes = $this->isAllDay ? 0 : (int) $startsAt->diffInMinutes($endsAt);

            if ($shiftDurationMinutes > 0) {
                $currentYear = Carbon::now()->year;
                $hoursPerWeek = Setting::getBpaHoursPerWeek();
                $yearlyQuotaMinutes = $hoursPerWeek * 52 * 60;

                // Calculate current usage (excluding this shift if editing)
                $usedMinutes = Shift::query()
                    ->worked()
                    ->forYear($currentYear)
                    ->when($this->editingShiftId, fn ($q) => $q->where('id', '!=', $this->editingShiftId))
                    ->sum('duration_minutes');

                $remainingMinutes = $yearlyQuotaMinutes - $usedMinutes;

                if ($shiftDurationMinutes > $remainingMinutes) {
                    $remainingHours = intdiv((int) $remainingMinutes, 60);
                    $remainingMins = (int) $remainingMinutes % 60;
                    $shiftHours = intdiv($shiftDurationMinutes, 60);
                    $shiftMins = $shiftDurationMinutes % 60;

                    $remainingFormatted = sprintf('%d:%02d', $remainingHours, $remainingMins);
                    $shiftFormatted = sprintf('%d:%02d', $shiftHours, $shiftMins);

                    $this->dispatch('toast', type: 'error', message: "Kan ikke registrere {$shiftFormatted} - kun {$remainingFormatted} timer igjen av vedtaket");

                    return;
                }
            }
        }

        $data = [
            'assistant_id' => $this->assistantId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_unavailable' => $this->isUnavailable,
            'is_all_day' => $this->isAllDay,
            'note' => $this->note ?: null,
        ];

        if ($this->editingShiftId) {
            $shift = Shift::findOrFail($this->editingShiftId);
            $shift->update($data);

            // If editing and recurring is enabled, create new recurring entries (excluding current date)
            if ($this->isUnavailable && $this->isRecurring) {
                $this->createRecurringShifts($data, skipFirstDate: true);
                $this->dispatch('toast', type: 'success', message: 'Vakten ble oppdatert og gjentakende oppføringer opprettet');
            } else {
                $this->dispatch('toast', type: 'success', message: 'Vakten ble oppdatert');
            }
        } else {
            // Handle recurring unavailability
            if ($this->isUnavailable && $this->isRecurring) {
                $this->createRecurringShifts($data);
            } else {
                Shift::create($data);
                $this->dispatch('toast', type: 'success', message: 'Vakten ble opprettet');
            }
        }

        // Clear computed property cache
        unset($this->shifts, $this->shiftsByDate, $this->remainingHoursData);

        if ($createAnother) {
            $this->resetForm();
            $this->showModal = true;
            // Keep the same date for convenience
            $this->fromDate = $data['starts_at']->format('Y-m-d');
            $this->toDate = $data['starts_at']->format('Y-m-d');
        } else {
            $this->closeModal();
        }
    }

    /**
     * Initiate delete - check if recurring and show dialog if needed.
     */
    public function deleteShift(?int $shiftId = null): void
    {
        if ($shiftId) {
            $this->editingShiftId = $shiftId;
        }

        if (! $this->editingShiftId) {
            return;
        }

        $shift = Shift::findOrFail($this->editingShiftId);

        if ($shift->isRecurring()) {
            $this->recurringAction = 'delete';
            $this->showRecurringDialog = true;
        } else {
            $this->confirmDeleteShift('single');
        }
    }

    /**
     * Actually delete the shift(s) based on scope.
     */
    public function confirmDeleteShift(string $scope): void
    {
        if (! $this->editingShiftId) {
            return;
        }

        $shift = Shift::findOrFail($this->editingShiftId);

        $deletedCount = match ($scope) {
            'single' => $this->deleteSingleShift($shift),
            'future' => $this->deleteFutureShifts($shift),
            'all' => $this->deleteAllRecurringShifts($shift),
            default => $this->deleteSingleShift($shift),
        };

        // Clear computed property cache
        unset($this->shifts, $this->shiftsByDate);

        $this->showRecurringDialog = false;
        $this->closeModal();

        $message = $deletedCount > 1
            ? "{$deletedCount} oppføringer ble slettet"
            : 'Oppføringen ble slettet';

        $this->dispatch('toast', type: 'success', message: $message);
    }

    private function deleteSingleShift(Shift $shift): int
    {
        $shift->forceDelete();

        return 1;
    }

    private function deleteFutureShifts(Shift $shift): int
    {
        if (! $shift->isRecurring()) {
            return $this->deleteSingleShift($shift);
        }

        $futureShifts = $shift->getFutureRecurringShifts();
        $count = $futureShifts->count();

        foreach ($futureShifts as $futureShift) {
            $futureShift->forceDelete();
        }

        return $count;
    }

    private function deleteAllRecurringShifts(Shift $shift): int
    {
        if (! $shift->isRecurring()) {
            return $this->deleteSingleShift($shift);
        }

        $allShifts = $shift->getRecurringGroupShifts();
        $count = $allShifts->count();

        foreach ($allShifts as $groupShift) {
            $groupShift->forceDelete();
        }

        return $count;
    }

    /**
     * Initiate archive - check if recurring and show dialog if needed.
     */
    public function archiveShift(?int $shiftId = null): void
    {
        if ($shiftId) {
            $this->editingShiftId = $shiftId;
        }

        if (! $this->editingShiftId) {
            return;
        }

        $shift = Shift::findOrFail($this->editingShiftId);

        if ($shift->isRecurring()) {
            $this->recurringAction = 'archive';
            $this->showRecurringDialog = true;
        } else {
            $this->confirmArchiveShift('single');
        }
    }

    /**
     * Actually archive the shift(s) based on scope (soft delete).
     */
    public function confirmArchiveShift(string $scope): void
    {
        if (! $this->editingShiftId) {
            return;
        }

        $shift = Shift::findOrFail($this->editingShiftId);

        $archivedCount = match ($scope) {
            'single' => $this->archiveSingleShift($shift),
            'future' => $this->archiveFutureShifts($shift),
            'all' => $this->archiveAllRecurringShifts($shift),
            default => $this->archiveSingleShift($shift),
        };

        // Clear computed property cache
        unset($this->shifts, $this->shiftsByDate);

        $this->showRecurringDialog = false;
        $this->closeModal();

        $message = $archivedCount > 1
            ? "{$archivedCount} oppføringer ble arkivert"
            : 'Oppføringen ble arkivert';

        $this->dispatch('toast', type: 'success', message: $message);
    }

    private function archiveSingleShift(Shift $shift): int
    {
        $shift->delete(); // Soft delete

        return 1;
    }

    private function archiveFutureShifts(Shift $shift): int
    {
        if (! $shift->isRecurring()) {
            return $this->archiveSingleShift($shift);
        }

        $futureShifts = $shift->getFutureRecurringShifts();
        $count = $futureShifts->count();

        foreach ($futureShifts as $futureShift) {
            $futureShift->delete(); // Soft delete
        }

        return $count;
    }

    private function archiveAllRecurringShifts(Shift $shift): int
    {
        if (! $shift->isRecurring()) {
            return $this->archiveSingleShift($shift);
        }

        $allShifts = $shift->getRecurringGroupShifts();
        $count = $allShifts->count();

        foreach ($allShifts as $groupShift) {
            $groupShift->delete(); // Soft delete
        }

        return $count;
    }

    /**
     * Close the recurring action dialog.
     */
    public function closeRecurringDialog(): void
    {
        $this->showRecurringDialog = false;
        $this->recurringAction = '';
        $this->recurringScope = 'single';
    }

    /**
     * Move a shift to a new date/time (for drag & drop).
     */
    public function moveShift(int $shiftId, string $newDate, ?string $newTime = null): void
    {
        $shift = Shift::findOrFail($shiftId);

        // If recurring, show dialog to ask about scope
        if ($shift->isRecurring()) {
            $this->editingShiftId = $shiftId;
            $this->pendingMoveDate = $newDate;
            $this->pendingMoveTime = $newTime;
            $this->recurringAction = 'move';
            $this->showRecurringDialog = true;

            return;
        }

        // Non-recurring: move directly
        $this->executeMoveShift($shift, $newDate, $newTime);

        // Clear computed property cache
        unset($this->shifts, $this->shiftsByDate);
        $this->dispatch('toast', type: 'success', message: 'Vakten ble flyttet');
    }

    /**
     * Execute the actual move operation on a shift.
     */
    private function executeMoveShift(Shift $shift, string $newDate, ?string $newTime = null): void
    {
        $oldStart = $shift->starts_at;
        $oldEnd = $shift->ends_at;
        $duration = $oldStart->diffInMinutes($oldEnd);

        if ($newTime) {
            $newStart = Carbon::parse($newDate.' '.$newTime);
        } else {
            // Keep same time, just change date
            $newStart = Carbon::parse($newDate.' '.$oldStart->format('H:i'));
        }

        $newEnd = $newStart->copy()->addMinutes($duration);

        // Check for overlapping unavailability (only for work shifts)
        if (! $shift->is_unavailable) {
            $conflict = Shift::findOverlappingUnavailability(
                $shift->assistant_id,
                $newStart,
                $newEnd,
                $shift->id
            );

            if ($conflict) {
                $conflictTime = $conflict->is_all_day
                    ? $conflict->starts_at->format('d.m.Y').' (hele dagen)'
                    : $conflict->starts_at->format('d.m.Y H:i').' - '.$conflict->ends_at->format('H:i');

                $this->dispatch('toast', type: 'error', message: "{$shift->assistant->name} er borte: {$conflictTime}");

                return;
            }
        }

        $shift->update([
            'starts_at' => $newStart,
            'ends_at' => $newEnd,
        ]);
    }

    /**
     * Confirm and execute move for recurring shifts based on scope.
     */
    public function confirmMoveRecurring(string $scope): void
    {
        if (! $this->editingShiftId || ! $this->pendingMoveDate) {
            return;
        }

        $shift = Shift::findOrFail($this->editingShiftId);
        $newDate = $this->pendingMoveDate;
        $newTime = $this->pendingMoveTime;

        // Calculate the day difference for moving the series
        $daysDiff = (int) Carbon::parse($shift->starts_at->format('Y-m-d'))
            ->diffInDays(Carbon::parse($newDate), false);

        $movedCount = match ($scope) {
            'single' => $this->moveSingleShift($shift, $newDate, $newTime),
            'future' => $this->moveFutureShifts($shift, $daysDiff, $newTime),
            'all' => $this->moveAllRecurringShifts($shift, $daysDiff, $newTime),
            default => $this->moveSingleShift($shift, $newDate, $newTime),
        };

        // Clear computed property cache
        unset($this->shifts, $this->shiftsByDate);

        $this->showRecurringDialog = false;
        $this->pendingMoveDate = null;
        $this->pendingMoveTime = null;
        $this->editingShiftId = null;

        $message = $movedCount > 1
            ? "{$movedCount} oppføringer ble flyttet"
            : 'Oppføringen ble flyttet';

        $this->dispatch('toast', type: 'success', message: $message);
    }

    private function moveSingleShift(Shift $shift, string $newDate, ?string $newTime): int
    {
        // When moving single, remove from recurring group
        $shift->recurring_group_id = null;
        $this->executeMoveShift($shift, $newDate, $newTime);

        return 1;
    }

    private function moveFutureShifts(Shift $shift, int $daysDiff, ?string $newTime): int
    {
        if (! $shift->isRecurring()) {
            return $this->moveSingleShift($shift, $this->pendingMoveDate, $newTime);
        }

        $futureShifts = $shift->getFutureRecurringShifts();
        $count = $futureShifts->count();

        foreach ($futureShifts as $futureShift) {
            $newDate = $futureShift->starts_at->copy()->addDays($daysDiff)->format('Y-m-d');
            $this->executeMoveShift($futureShift, $newDate, $newTime);
        }

        return $count;
    }

    private function moveAllRecurringShifts(Shift $shift, int $daysDiff, ?string $newTime): int
    {
        if (! $shift->isRecurring()) {
            return $this->moveSingleShift($shift, $this->pendingMoveDate, $newTime);
        }

        $allShifts = $shift->getRecurringGroupShifts();
        $count = $allShifts->count();

        foreach ($allShifts as $groupShift) {
            $newDate = $groupShift->starts_at->copy()->addDays($daysDiff)->format('Y-m-d');
            $this->executeMoveShift($groupShift, $newDate, $newTime);
        }

        return $count;
    }

    /**
     * Create a shift from sidebar drag & drop - opens modal for recurring options.
     */
    public function createShiftFromDrag(int $assistantId, string $date, ?string $time = null): void
    {
        $startsAt = $time
            ? Carbon::parse($date.' '.$time)
            : Carbon::parse($date)->setTime(8, 0);

        $endsAt = $startsAt->copy()->addHours(3); // Default 3 hour shift

        // Check for overlapping unavailability
        $conflict = Shift::findOverlappingUnavailability($assistantId, $startsAt, $endsAt);

        if ($conflict) {
            $assistant = Assistant::find($assistantId);
            $conflictTime = $conflict->is_all_day
                ? $conflict->starts_at->format('d.m.Y').' (hele dagen)'
                : $conflict->starts_at->format('d.m.Y H:i').' - '.$conflict->ends_at->format('H:i');

            $this->dispatch('toast', type: 'error', message: "{$assistant->name} er borte: {$conflictTime}");

            return;
        }

        // Open modal with pre-filled data instead of creating directly
        $this->openModal(
            $date,
            $startsAt->format('H:i'),
            $assistantId,
            $endsAt->format('H:i')
        );
    }

    /**
     * Resize a shift (change end time).
     */
    public function resizeShift(int $shiftId, int $newDurationMinutes): void
    {
        $shift = Shift::findOrFail($shiftId);
        if ($shift->is_all_day) {
            return;
        }

        // Minimum 15 minutes
        $newDurationMinutes = max(15, $newDurationMinutes);

        $newEndsAt = $shift->starts_at->copy()->addMinutes($newDurationMinutes);

        // Check for overlapping unavailability (only for work shifts)
        if (! $shift->is_unavailable) {
            $conflict = Shift::findOverlappingUnavailability(
                $shift->assistant_id,
                $shift->starts_at,
                $newEndsAt,
                $shiftId
            );

            if ($conflict) {
                $conflictTime = $conflict->is_all_day
                    ? $conflict->starts_at->format('d.m.Y').' (hele dagen)'
                    : $conflict->starts_at->format('d.m.Y H:i').' - '.$conflict->ends_at->format('H:i');

                $this->dispatch('toast', type: 'error', message: "{$shift->assistant->name} er borte: {$conflictTime}");

                return;
            }
        }

        $shift->update([
            'ends_at' => $newEndsAt,
        ]);

        // Clear computed property cache
        unset($this->shifts, $this->shiftsByDate);
        $this->dispatch('toast', type: 'success', message: 'Vakten ble endret');
    }

    /**
     * Open quick create popup.
     */
    public function openQuickCreate(string $date, string $time, ?string $endTime = null, int $x = 0, int $y = 0): void
    {
        $this->quickCreateDate = $date;
        $this->quickCreateTime = $time;
        $this->quickCreateEndTime = $endTime;
        $this->quickCreateX = $x;
        $this->quickCreateY = $y;
        $this->showQuickCreate = true;
    }

    /**
     * Close quick create popup.
     */
    public function closeQuickCreate(): void
    {
        $this->showQuickCreate = false;
        $this->quickCreateEndTime = null;
    }

    /**
     * Quick create shift from double-click or drag-to-create.
     */
    public function quickCreateShift(int $assistantId): void
    {
        $startsAt = Carbon::parse($this->quickCreateDate.' '.$this->quickCreateTime);

        // Use custom end time if provided (from drag-to-create), otherwise default to 3 hours
        if ($this->quickCreateEndTime) {
            $endsAt = Carbon::parse($this->quickCreateDate.' '.$this->quickCreateEndTime);
        } else {
            $endsAt = $startsAt->copy()->addHours(3);
        }

        // Check if there are enough remaining hours
        $shiftDurationMinutes = (int) $startsAt->diffInMinutes($endsAt);

        if ($shiftDurationMinutes > 0) {
            $currentYear = Carbon::now()->year;
            $hoursPerWeek = Setting::getBpaHoursPerWeek();
            $yearlyQuotaMinutes = $hoursPerWeek * 52 * 60;

            $usedMinutes = Shift::query()
                ->worked()
                ->forYear($currentYear)
                ->sum('duration_minutes');

            $remainingMinutes = $yearlyQuotaMinutes - $usedMinutes;

            if ($shiftDurationMinutes > $remainingMinutes) {
                $remainingHours = intdiv((int) $remainingMinutes, 60);
                $remainingMins = (int) $remainingMinutes % 60;
                $shiftHours = intdiv($shiftDurationMinutes, 60);
                $shiftMins = $shiftDurationMinutes % 60;

                $remainingFormatted = sprintf('%d:%02d', $remainingHours, $remainingMins);
                $shiftFormatted = sprintf('%d:%02d', $shiftHours, $shiftMins);

                $this->showQuickCreate = false;
                $this->quickCreateEndTime = null;
                $this->dispatch('toast', type: 'error', message: "Kan ikke registrere {$shiftFormatted} - kun {$remainingFormatted} timer igjen av vedtaket");

                return;
            }
        }

        Shift::create([
            'assistant_id' => $assistantId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_unavailable' => false,
            'is_all_day' => false,
        ]);

        $this->showQuickCreate = false;
        $this->quickCreateEndTime = null;

        // Clear computed property cache
        unset($this->shifts, $this->shiftsByDate, $this->remainingHoursData);
        $this->dispatch('toast', type: 'success', message: 'Vakten ble opprettet');
    }

    /**
     * Duplicate a shift to another date.
     */
    public function duplicateShift(int $shiftId, string $targetDate): void
    {
        $shift = Shift::findOrFail($shiftId);

        $daysDiff = Carbon::parse($shift->starts_at->format('Y-m-d'))->diffInDays(Carbon::parse($targetDate), false);

        Shift::create([
            'assistant_id' => $shift->assistant_id,
            'starts_at' => $shift->starts_at->copy()->addDays($daysDiff),
            'ends_at' => $shift->ends_at->copy()->addDays($daysDiff),
            'is_unavailable' => $shift->is_unavailable,
            'is_all_day' => $shift->is_all_day,
            'note' => $shift->note,
        ]);

        // Clear computed property cache
        unset($this->shifts, $this->shiftsByDate);
        $this->dispatch('toast', type: 'success', message: 'Vakten ble duplisert');
    }

    /**
     * Open modal with shift data for duplication (clone).
     */
    public function duplicateShiftWithModal(int $shiftId): void
    {
        $shift = Shift::findOrFail($shiftId);

        $this->resetForm();
        $this->assistantId = $shift->assistant_id;
        $this->fromDate = $shift->starts_at->format('Y-m-d');
        $this->fromTime = $shift->starts_at->format('H:i');
        $this->toDate = $shift->ends_at->format('Y-m-d');
        $this->toTime = $shift->ends_at->format('H:i');
        $this->isUnavailable = $shift->is_unavailable;
        $this->isAllDay = $shift->is_all_day;
        $this->note = $shift->note ?? '';
        $this->showModal = true;
        // Note: editingShiftId is NOT set, so saving will create a new shift
    }

    /**
     * Create an all-day absence from multi-day selection in month view.
     */
    public function createAbsenceFromSelection(int $assistantId, string $fromDate, string $toDate): void
    {
        $startsAt = Carbon::parse($fromDate)->startOfDay();
        $endsAt = Carbon::parse($toDate)->endOfDay();

        Shift::create([
            'assistant_id' => $assistantId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_unavailable' => true,
            'is_all_day' => true,
        ]);

        // Clear computed property cache
        unset($this->shifts, $this->shiftsByDate);

        // Calculate number of days for message
        $days = (int) Carbon::parse($fromDate)->diffInDays(Carbon::parse($toDate)) + 1;
        $assistant = Assistant::find($assistantId);
        $dayText = $days === 1 ? 'dag' : 'dager';

        $this->dispatch('toast', type: 'success', message: "Fravær for {$assistant->name} ({$days} {$dayText}) ble opprettet");
    }
}
