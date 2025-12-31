<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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
 * @property bool $send_monthly_report
 * @property string|null $token
 * @property-read string $type_label
 * @property-read string $initials
 * @property-read string $short_name
 * @property-read string $formatted_number
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Task> $tasks
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
        'send_monthly_report',
        'token',
    ];

    protected static function booted(): void
    {
        static::creating(function (Assistant $assistant): void {
            if (empty($assistant->token)) {
                $assistant->token = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'employee_number' => 'integer',
            'hired_at' => 'datetime',
            'send_monthly_report' => 'boolean',
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
    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => self::$typeLabels[$this->type] ?? $this->type
        );
    }

    /**
     * Get initials from name.
     */
    protected function initials(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $words = explode(' ', $this->name);
                $initials = '';

                foreach ($words as $word) {
                    if (! empty($word)) {
                        $initials .= mb_strtoupper(mb_substr($word, 0, 1));
                    }
                }

                return $initials;
            }
        );
    }

    /**
     * Get short name (first name + last initial, e.g., "Per H.").
     */
    protected function shortName(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $words = explode(' ', $this->name);

                if (count($words) === 1) {
                    return $this->name;
                }

                $firstName = $words[0];
                $lastInitial = mb_strtoupper(mb_substr(end($words), 0, 1));

                return "{$firstName} {$lastInitial}.";
            }
        );
    }

    /**
     * Get formatted employee number (e.g., #001).
     */
    protected function formattedNumber(): Attribute
    {
        return Attribute::make(
            get: fn (): string => '#' . str_pad((string) $this->employee_number, 3, '0', STR_PAD_LEFT)
        );
    }

    /**
     * @return HasMany<Shift, $this>
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the route key for the model (for token-based URLs).
     */
    public function getRouteKeyName(): string
    {
        return 'token';
    }

    /**
     * Regenerate the token for this assistant.
     */
    public function regenerateToken(): void
    {
        $this->token = (string) Str::uuid();
        $this->save();
    }
}
