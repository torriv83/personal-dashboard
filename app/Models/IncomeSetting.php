<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class IncomeSetting extends Model
{
    protected $fillable = [
        'monthly_gross',
        'monthly_net',
        'tax_table',
        'base_support',
    ];

    protected function casts(): array
    {
        return [
            'monthly_gross' => 'decimal:2',
            'monthly_net' => 'decimal:2',
            'base_support' => 'decimal:2',
        ];
    }

    /**
     * Get the singleton instance (creates one if none exists).
     */
    public static function instance(): self
    {
        return self::firstOrCreate([], [
            'monthly_gross' => 0,
            'monthly_net' => 0,
            'tax_table' => null,
            'base_support' => 0,
        ]);
    }

    /**
     * Calculate yearly gross income (includes tax-free base support).
     */
    protected function yearlyGross(): Attribute
    {
        return Attribute::make(
            get: fn (): float => ($this->monthly_gross + $this->base_support) * 12
        );
    }

    /**
     * Calculate yearly net income (includes tax-free base support).
     */
    protected function yearlyNet(): Attribute
    {
        return Attribute::make(
            get: fn (): float => ($this->monthly_net + $this->base_support) * 12
        );
    }

    /**
     * Calculate tax percentage.
     */
    protected function taxPercentage(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->monthly_gross <= 0
                ? 0
                : round((1 - ($this->monthly_net / $this->monthly_gross)) * 100, 1)
        );
    }
}
