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
            self::Low => 'text-muted-foreground',
            self::Medium => 'text-yellow-400',
            self::High => 'text-red-400',
        };
    }

    public function bgColor(): string
    {
        return match ($this) {
            self::Low => 'bg-muted-foreground/10',
            self::Medium => 'bg-yellow-400/10',
            self::High => 'bg-red-400/10',
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
