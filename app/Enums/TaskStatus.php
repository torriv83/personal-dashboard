<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ikke fullført',
            self::Completed => 'Fullført',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'text-muted-foreground',
            self::Completed => 'text-accent',
        };
    }

    public function bgColor(): string
    {
        return match ($this) {
            self::Pending => 'bg-muted-foreground/10',
            self::Completed => 'bg-accent/10',
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->toArray();
    }
}
