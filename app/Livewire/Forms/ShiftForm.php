<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\Shift;
use Carbon\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ShiftForm extends Form
{
    public ?int $editingShiftId = null;

    #[Validate('required|exists:assistants,id', message: 'Velg en assistent')]
    public ?int $assistantId = null;

    #[Validate('required|date', message: 'Velg startdato')]
    public string $fromDate = '';

    public string $fromTime = '08:00';

    #[Validate('required|date|after_or_equal:fromDate', message: 'Sluttdato må være etter startdato')]
    public string $toDate = '';

    public string $toTime = '16:00';

    public bool $isUnavailable = false;

    public bool $isAllDay = false;

    public string $note = '';

    // Recurring fields (only for unavailable entries)
    public bool $isRecurring = false;

    public string $recurringInterval = 'weekly';

    public string $recurringEndType = 'count';

    public int $recurringCount = 4;

    public string $recurringEndDate = '';

    // Track if shift being edited is already recurring
    public bool $isExistingRecurring = false;

    /**
     * Populate form from an existing shift.
     */
    public function setShift(Shift $shift): void
    {
        $this->editingShiftId = $shift->id;
        $this->assistantId = $shift->assistant_id;
        $this->fromDate = $shift->starts_at->format('Y-m-d');
        $this->fromTime = $shift->starts_at->format('H:i');
        $this->toDate = $shift->ends_at->format('Y-m-d');
        $this->toTime = $shift->ends_at->format('H:i');
        $this->isUnavailable = $shift->is_unavailable;
        $this->isAllDay = $shift->is_all_day;
        $this->note = $shift->note ?? '';
        $this->isExistingRecurring = $shift->isRecurring();
    }

    /**
     * Reset form to default values.
     */
    public function resetForm(): void
    {
        $this->reset();
        $this->fromTime = '08:00';
        $this->toTime = '16:00';
        $this->recurringInterval = 'weekly';
        $this->recurringEndType = 'count';
        $this->recurringCount = 4;
    }

    /**
     * Get the starts_at Carbon instance.
     */
    public function getStartsAt(): Carbon
    {
        return $this->isAllDay
            ? Carbon::parse($this->fromDate)->startOfDay()
            : Carbon::parse($this->fromDate . ' ' . $this->fromTime);
    }

    /**
     * Get the ends_at Carbon instance.
     */
    public function getEndsAt(): Carbon
    {
        return $this->isAllDay
            ? Carbon::parse($this->toDate)->endOfDay()
            : Carbon::parse($this->toDate . ' ' . $this->toTime);
    }

    /**
     * Get form data as array for creating/updating shift.
     *
     * @return array<string, mixed>
     */
    public function toShiftData(): array
    {
        return [
            'assistant_id' => $this->assistantId,
            'starts_at' => $this->getStartsAt(),
            'ends_at' => $this->getEndsAt(),
            'is_unavailable' => $this->isUnavailable,
            'is_all_day' => $this->isAllDay,
            'note' => $this->note ?: null,
        ];
    }

    /**
     * Check if we are editing an existing shift.
     */
    public function isEditing(): bool
    {
        return $this->editingShiftId !== null;
    }
}
