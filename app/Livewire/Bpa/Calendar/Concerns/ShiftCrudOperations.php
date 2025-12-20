<?php

namespace App\Livewire\Bpa\Calendar\Concerns;

use App\Models\Assistant;
use App\Models\Shift;
use App\Services\BpaQuotaService;
use Carbon\Carbon;

/**
 * Handles core shift CRUD operations (create, read, update, delete, archive).
 *
 * Required properties from Livewire component:
 * - editingShiftId: ?int
 * - assistantId: ?int
 * - fromDate: string
 * - fromTime: string
 * - toDate: string
 * - toTime: string
 * - isUnavailable: bool
 * - isAllDay: bool
 * - note: string
 * - isRecurring: bool
 * - isExistingRecurring: bool
 * - showModal: bool
 * - showRecurringDialog: bool
 * - recurringAction: string
 * - showQuickCreate: bool
 * - quickCreateDate: string
 * - quickCreateTime: string
 * - quickCreateEndTime: ?string
 */
trait ShiftCrudOperations
{
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

            // Check if there are enough remaining hours (only for work shifts, not all-day)
            if (! $this->isAllDay) {
                $quotaService = app(BpaQuotaService::class);
                $quotaResult = $quotaService->validateShiftQuota($startsAt, $endsAt, $this->editingShiftId);

                if (! $quotaResult['valid']) {
                    $this->dispatch('toast', type: 'error', message: $quotaResult['error']);

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
        $quotaService = app(BpaQuotaService::class);
        $quotaResult = $quotaService->validateShiftQuota($startsAt, $endsAt);

        if (! $quotaResult['valid']) {
            $this->showQuickCreate = false;
            $this->quickCreateEndTime = null;
            $this->dispatch('toast', type: 'error', message: $quotaResult['error']);

            return;
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
