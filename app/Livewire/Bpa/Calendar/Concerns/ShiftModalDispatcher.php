<?php

namespace App\Livewire\Bpa\Calendar\Concerns;

use Carbon\Carbon;

/**
 * Handles shift modal opening/closing and quick create popup.
 *
 * Required properties from Livewire component:
 * - showModal: bool
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
 * - recurringInterval: string
 * - recurringEndType: string
 * - recurringCount: int
 * - recurringEndDate: string
 * - showRecurringDialog: bool
 * - recurringAction: string
 * - recurringScope: string
 * - isExistingRecurring: bool
 * - pendingMoveDate: ?string
 * - pendingMoveTime: ?string
 * - showQuickCreate: bool
 * - quickCreateDate: string
 * - quickCreateTime: string
 * - quickCreateEndTime: ?string
 * - quickCreateX: int
 * - quickCreateY: int
 */
trait ShiftModalDispatcher
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
}
