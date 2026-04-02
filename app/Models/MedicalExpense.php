<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $amount
 * @property \Illuminate\Support\Carbon $expense_date
 * @property string|null $note
 * @property int $year
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class MedicalExpense extends Model
{
    /** @use HasFactory<\Database\Factories\MedicalExpenseFactory> */
    use HasFactory;

    protected $fillable = [
        'amount',
        'expense_date',
        'note',
        'year',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    /**
     * Scope a query to only include expenses from current year.
     */
    public function scopeCurrentYear($query)
    {
        return $query->where('year', now()->year);
    }

    /**
     * Scope a query to only include expenses from a specific year.
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }
}
