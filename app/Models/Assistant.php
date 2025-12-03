<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $employee_number
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string $type
 * @property string|null $color
 * @property Carbon $hired_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read string $type_label
 * @property-read string $initials
 * @property-read string $formatted_number
 */
class Assistant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_number',
        'name',
        'email',
        'phone',
        'type',
        'color',
        'hired_at',
    ];

    protected function casts(): array
    {
        return [
            'employee_number' => 'integer',
            'hired_at' => 'datetime',
        ];
    }

    /**
     * Type labels mapping (English enum to Norwegian display).
     */
    public static array $typeLabels = [
        'primary' => 'Fast ansatt',
        'substitute' => 'Vikar',
        'oncall' => 'Tilkalling',
    ];

    /**
     * Get the Norwegian label for the type.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::$typeLabels[$this->type] ?? $this->type;
    }

    /**
     * Get initials from name.
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';

        foreach ($words as $word) {
            if (! empty($word)) {
                $initials .= mb_strtoupper(mb_substr($word, 0, 1));
            }
        }

        return $initials;
    }

    /**
     * Get formatted employee number (e.g., #001).
     */
    public function getFormattedNumberAttribute(): string
    {
        return '#'.str_pad((string) $this->employee_number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * @return HasMany<Shift, $this>
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }
}
