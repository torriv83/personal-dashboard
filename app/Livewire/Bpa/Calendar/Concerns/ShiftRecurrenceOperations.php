<?php

namespace App\Livewire\Bpa\Calendar\Concerns;

use App\Models\Shift;
use Carbon\Carbon;

/**
 * Handles recurring shift operations (delete/archive/move for future and all).
 *
 * Required properties from Livewire component:
 * - editingShiftId: ?int
 * - showRecurringDialog: bool
 * - recurringAction: string
 * - recurringScope: string
 * - pendingMoveDate: ?string
 * - pendingMoveTime: ?string
 */
trait ShiftRecurrenceOperations
{
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

    /**
     * Delete a single shift (force delete).
     */
    private function deleteSingleShift(Shift $shift): int
    {
        $shift->forceDelete();

        return 1;
    }

    /**
     * Delete future shifts in recurring group (including current).
     */
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

    /**
     * Delete all shifts in recurring group.
     */
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

    /**
     * Archive a single shift (soft delete).
     */
    private function archiveSingleShift(Shift $shift): int
    {
        $shift->delete();

        return 1;
    }

    /**
     * Archive future shifts in recurring group (soft delete).
     */
    private function archiveFutureShifts(Shift $shift): int
    {
        if (! $shift->isRecurring()) {
            return $this->archiveSingleShift($shift);
        }

        $futureShifts = $shift->getFutureRecurringShifts();
        $count = $futureShifts->count();

        foreach ($futureShifts as $futureShift) {
            $futureShift->delete();
        }

        return $count;
    }

    /**
     * Archive all shifts in recurring group (soft delete).
     */
    private function archiveAllRecurringShifts(Shift $shift): int
    {
        if (! $shift->isRecurring()) {
            return $this->archiveSingleShift($shift);
        }

        $allShifts = $shift->getRecurringGroupShifts();
        $count = $allShifts->count();

        foreach ($allShifts as $groupShift) {
            $groupShift->delete();
        }

        return $count;
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

    /**
     * Move a single shift (removes from recurring group).
     */
    private function moveSingleShift(Shift $shift, string $newDate, ?string $newTime): int
    {
        // When moving single, remove from recurring group
        $shift->recurring_group_id = null;
        $this->executeMoveShift($shift, $newDate, $newTime);

        return 1;
    }

    /**
     * Move future shifts in recurring group.
     */
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

    /**
     * Move all shifts in recurring group.
     */
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
}
