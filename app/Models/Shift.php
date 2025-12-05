<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $assistant_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property int $duration_minutes
 * @property bool $is_unavailable
 * @property bool $is_all_day
 * @property bool $is_archived
 * @property string|null $recurring_group_id
 * @property string|null $note
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Assistant|null $assistant
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
        'is_archived',
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
            'is_archived' => 'boolean',
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
     * Get formatted duration (e.g., "4:30").
     */
    public function getFormattedDurationAttribute(): string
    {
        if ($this->is_all_day) {
            return 'Hele dagen';
        }

        $hours = intdiv($this->duration_minutes, 60);
        $minutes = $this->duration_minutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }

    /**
     * Get formatted time range (e.g., "08:00 - 12:30").
     */
    public function getTimeRangeAttribute(): string
    {
        if ($this->is_all_day) {
            return 'Hele dagen';
        }

        return $this->starts_at->format('H:i').' - '.$this->ends_at->format('H:i');
    }

    /**
     * Scope: Only active (non-archived) shifts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope: Only actual worked shifts (non-archived, non-unavailable).
     */
    public function scopeWorked($query)
    {
        return $query->where('is_archived', false)
            ->where('is_unavailable', false);
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
