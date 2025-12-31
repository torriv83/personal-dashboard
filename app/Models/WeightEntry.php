<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $recorded_at
 * @property float $weight
 * @property string|null $note
 */
class WeightEntry extends Model
{
    /** @use HasFactory<\Database\Factories\WeightEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'recorded_at',
        'weight',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'weight' => 'decimal:2',
        ];
    }
}
