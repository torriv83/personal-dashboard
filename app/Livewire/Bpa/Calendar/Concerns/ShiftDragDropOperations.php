<?php

declare(strict_types=1);

namespace App\Livewire\Bpa\Calendar\Concerns;

use App\Models\Assistant;
use App\Models\Shift;
use Carbon\Carbon;

/**
 * Handles shift drag & drop operations (move, resize, create from drag).
 *
 * Required properties from Livewire component:
 * - editingShiftId: ?int
 * - showRecurringDialog: bool
 * - recurringAction: string
 * - pendingMoveDate: ?string
 * - pendingMoveTime: ?string
 */
trait ShiftDragDropOperations
{
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
        $this->invalidateCalendarCache();
        $this->dispatch('toast', type: 'success', message: 'Vakten ble flyttet');
    }

    /**
     * Execute the actual move operation on a shift.
     */
    public function executeMoveShift(Shift $shift, string $newDate, ?string $newTime = null): void
    {
        $oldStart = $shift->starts_at;
        $oldEnd = $shift->ends_at;
        $duration = $oldStart->diffInMinutes($oldEnd);

        if ($newTime) {
            $newStart = Carbon::parse($newDate . ' ' . $newTime);
        } else {
            // Keep same time, just change date
            $newStart = Carbon::parse($newDate . ' ' . $oldStart->format('H:i'));
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
                    ? $conflict->starts_at->format('d.m.Y') . ' (hele dagen)'
                    : $conflict->starts_at->format('d.m.Y H:i') . ' - ' . $conflict->ends_at->format('H:i');

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
     * Create a shift from sidebar drag & drop - opens modal for recurring options.
     */
    public function createShiftFromDrag(int $assistantId, string $date, ?string $time = null): void
    {
        $startsAt = $time
            ? Carbon::parse($date . ' ' . $time)
            : Carbon::parse($date)->setTime(8, 0);

        $endsAt = $startsAt->copy()->addHours(3); // Default 3 hour shift

        // Check for overlapping unavailability
        $conflict = Shift::findOverlappingUnavailability($assistantId, $startsAt, $endsAt);

        if ($conflict) {
            $assistant = Assistant::find($assistantId);
            $conflictTime = $conflict->is_all_day
                ? $conflict->starts_at->format('d.m.Y') . ' (hele dagen)'
                : $conflict->starts_at->format('d.m.Y H:i') . ' - ' . $conflict->ends_at->format('H:i');

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
                    ? $conflict->starts_at->format('d.m.Y') . ' (hele dagen)'
                    : $conflict->starts_at->format('d.m.Y H:i') . ' - ' . $conflict->ends_at->format('H:i');

                $this->dispatch('toast', type: 'error', message: "{$shift->assistant->name} er borte: {$conflictTime}");

                return;
            }
        }

        $shift->update([
            'ends_at' => $newEndsAt,
        ]);

        // Clear computed property cache
        $this->invalidateCalendarCache();
        $this->dispatch('toast', type: 'success', message: 'Vakten ble endret');
    }
}
