<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property \Carbon\Carbon $valid_to
 * @property int $daysLeft
 * @property string $status
 */
class Prescription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'valid_to',
    ];

    protected function casts(): array
    {
        return [
            'valid_to' => 'datetime',
        ];
    }
}
