<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $assistant_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property int $duration_minutes
 * @property bool $is_unavailable
 * @property bool $is_all_day
 * @property string|null $recurring_group_id
 * @property string|null $note
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Assistant|null $assistant
 * @property-read string $assistant_name
 */
class Shift extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assistant_id',
        'starts_at',
        'ends_at',
        'duration_minutes',
        'is_unavailable',
        'is_all_day',
        'recurring_group_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'assistant_id' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'duration_minutes' => 'integer',
            'is_unavailable' => 'boolean',
            'is_all_day' => 'boolean',
        ];
    }

    /**
     * Calculate duration_minutes automatically when saving.
     */
    protected static function booted(): void
    {
        static::saving(function (Shift $shift) {
            if ($shift->is_all_day) {
                $shift->duration_minutes = 0;
            } elseif ($shift->starts_at && $shift->ends_at) {
                $shift->duration_minutes = (int) $shift->starts_at->diffInMinutes($shift->ends_at);
            }
        });
    }

    /**
     * @return BelongsTo<Assistant, $this>
     */
    public function assistant(): BelongsTo
    {
        return $this->belongsTo(Assistant::class);
    }

    /**
     * Get the assistant's name, or "Tidligere ansatt" if no assistant is assigned.
     */
    protected function assistantName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->assistant?->name ?? 'Tidligere ansatt'
        );
    }

    /**
     * Get formatted duration (e.g., "4:30").
     */
    protected function formattedDuration(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->is_all_day) {
                    return 'Hele dagen';
                }

                $hours = intdiv($this->duration_minutes, 60);
                $minutes = $this->duration_minutes % 60;

                return sprintf('%d:%02d', $hours, $minutes);
            }
        );
    }

    /**
     * Get formatted time range (e.g., "08:00 - 12:30").
     */
    protected function timeRange(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->is_all_day
                ? 'Hele dagen'
                : $this->starts_at->format('H:i').' - '.$this->ends_at->format('H:i')
        );
    }

    /**
     * Get compact time range for copying (e.g., "0800-1230").
     */
    protected function compactTimeRange(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->is_all_day
                ? 'Hele dagen'
                : $this->starts_at->format('Hi').'-'.$this->ends_at->format('Hi')
        );
    }

    /**
     * Scope: Only active (non-archived) shifts.
     * Note: SoftDeletes already excludes soft-deleted records by default.
     */
    public function scopeActive($query)
    {
        return $query;
    }

    /**
     * Scope: Only actual worked shifts (non-archived, non-unavailable).
     * Note: SoftDeletes already excludes soft-deleted (archived) records by default.
     */
    public function scopeWorked($query)
    {
        return $query->where('is_unavailable', false);
    }

    /**
     * Scope: Only unavailable entries.
     */
    public function scopeUnavailable($query)
    {
        return $query->where('is_unavailable', true);
    }

    /**
     * Scope: Shifts in the future.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>', now());
    }

    /**
     * Scope: Shifts for a specific year.
     *
     * Uses whereBetween instead of whereYear for index optimization.
     */
    public function scopeForYear($query, int $year)
    {
        return $query->whereBetween('starts_at', [
            "{$year}-01-01 00:00:00",
            "{$year}-12-31 23:59:59",
        ]);
    }

    /**
     * Scope: Shifts for a specific month.
     *
     * Uses whereBetween instead of whereYear/whereMonth for index optimization.
     */
    public function scopeForMonth($query, int $year, int $month)
    {
        $startOfMonth = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $endOfMonth = date('Y-m-t 23:59:59', strtotime($startOfMonth));

        return $query->whereBetween('starts_at', [$startOfMonth, $endOfMonth]);
    }

    /**
     * Scope: Shifts in the same recurring group.
     */
    public function scopeInRecurringGroup($query, string $groupId)
    {
        return $query->where('recurring_group_id', $groupId);
    }

    /**
     * Check if this shift is part of a recurring series.
     */
    public function isRecurring(): bool
    {
        return $this->recurring_group_id !== null;
    }

    /**
     * Get all shifts in the same recurring group.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Shift>
     */
    public function getRecurringGroupShifts(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->isRecurring()) {
            /** @var \Illuminate\Database\Eloquent\Collection<int, Shift> */
            return new \Illuminate\Database\Eloquent\Collection([$this]);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, Shift> */
        return static::query()
            ->inRecurringGroup($this->recurring_group_id)
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Get future shifts in the same recurring group (including this one if it's in the future).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Shift>
     */
    public function getFutureRecurringShifts(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->isRecurring()) {
            /** @var \Illuminate\Database\Eloquent\Collection<int, Shift> */
            return new \Illuminate\Database\Eloquent\Collection([$this]);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, Shift> */
        return static::query()
            ->inRecurringGroup($this->recurring_group_id)
            ->where('starts_at', '>=', $this->starts_at)
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Check if an assistant has overlapping unavailability for a given time period.
     *
     * @param  int|null  $excludeShiftId  Exclude this shift from check (for editing)
     * @return Shift|null Returns the conflicting unavailable shift if found
     */
    public static function findOverlappingUnavailability(
        int $assistantId,
        \Carbon\Carbon $startsAt,
        \Carbon\Carbon $endsAt,
        ?int $excludeShiftId = null
    ): ?self {
        return static::query()
            ->where('assistant_id', $assistantId)
            ->where('is_unavailable', true)
            ->when($excludeShiftId, fn ($q) => $q->where('id', '!=', $excludeShiftId))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->first();
    }
}
