<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Lav',
            self::Medium => 'Middels',
            self::High => 'Høy',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'text-green-400',
            self::Medium => 'text-orange-400',
            self::High => 'text-red-400',
        };
    }

    public function bgColor(): string
    {
        return match ($this) {
            self::Low => 'bg-green-400/10',
            self::Medium => 'bg-orange-400/10',
            self::High => 'bg-red-400/10',
        };
    }

    /**
     * Solid background color for small indicators like dots.
     */
    public function dotColor(): string
    {
        return match ($this) {
            self::Low => 'bg-green-500',
            self::Medium => 'bg-orange-500',
            self::High => 'bg-red-500',
        };
    }

    /**
     * Sort order for priority (lower = higher priority).
     */
    public function sortOrder(): int
    {
        return match ($this) {
            self::High => 1,
            self::Medium => 2,
            self::Low => 3,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $priority) => [$priority->value => $priority->label()])
            ->toArray();
    }
}
