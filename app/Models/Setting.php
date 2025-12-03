<?php

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
}
