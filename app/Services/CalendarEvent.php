<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;

/**
 * Data Transfer Object for external calendar events.
 */
readonly class CalendarEvent
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public string $location,
        public Carbon $starts_at,
        public Carbon $ends_at,
        public bool $is_all_day,
        public string $calendar_key,
        public string $calendar_label,
        public string $color,
    ) {}

    /**
     * Get duration in minutes.
     */
    public function getDurationMinutes(): int
    {
        return (int) $this->starts_at->diffInMinutes($this->ends_at);
    }

    /**
     * Get formatted time range (e.g., "08:00 - 12:30").
     */
    public function getTimeRange(): string
    {
        if ($this->is_all_day) {
            return 'Hele dagen';
        }

        return $this->starts_at->format('H:i') . ' - ' . $this->ends_at->format('H:i');
    }

    /**
     * Check if this is a Manchester United calendar event.
     */
    public function isManUtd(): bool
    {
        return $this->calendar_key === 'manutd';
    }

    /**
     * Check if this is a private calendar event.
     */
    public function isPrivat(): bool
    {
        return $this->calendar_key === 'privat';
    }

    /**
     * Get a unique identifier for the event.
     */
    public function getUniqueKey(): string
    {
        return "gcal-{$this->calendar_key}-{$this->id}";
    }
}
