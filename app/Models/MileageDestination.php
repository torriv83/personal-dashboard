<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $address
 * @property float|null $distance_km
 */
class MileageDestination extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'distance_km',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
        ];
    }
}
