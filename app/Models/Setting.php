<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Get BPA hours per week.
     */
    public static function getBpaHoursPerWeek(): float
    {
        return (float) self::get('bpa_hours_per_week', 0);
    }

    /**
     * Set BPA hours per week.
     */
    public static function setBpaHoursPerWeek(float $hours): void
    {
        self::set('bpa_hours_per_week', $hours);
    }

    /**
     * Get BPA hourly rate.
     */
    public static function getBpaHourlyRate(): float
    {
        return (float) self::get('bpa_hourly_rate', 225.40);
    }

    /**
     * Set BPA hourly rate.
     */
    public static function setBpaHourlyRate(float $rate): void
    {
        self::set('bpa_hourly_rate', $rate);
    }

    /**
     * Get frikort limit for current year.
     */
    public static function getFrikortLimit(?int $year = null): float
    {
        $year = $year ?? now()->year;

        return (float) self::get("frikort_limit_{$year}", 3000);
    }

    /**
     * Set frikort limit for a specific year.
     */
    public static function setFrikortLimit(float $limit, ?int $year = null): void
    {
        $year = $year ?? now()->year;
        self::set("frikort_limit_{$year}", $limit);
    }
}
