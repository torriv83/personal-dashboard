<?php

namespace App\Enums;

enum WishlistStatus: string
{
    case Waiting = 'waiting';
    case Saving = 'saving';
    case Saved = 'saved';
    case Purchased = 'purchased';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Venter',
            self::Saving => 'Begynt å spare',
            self::Saved => 'Spart',
            self::Purchased => 'Kjøpt',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Waiting => 'text-muted-foreground',
            self::Saving => 'text-yellow-400',
            self::Saved, self::Purchased => 'text-accent',
        };
    }

    public function bgColor(): string
    {
        return match ($this) {
            self::Waiting => 'bg-muted-foreground/10',
            self::Saving => 'bg-yellow-400/10',
            self::Saved, self::Purchased => 'bg-accent/10',
        };
    }

    public function isCompleted(): bool
    {
        return in_array($this, [self::Saved, self::Purchased]);
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
