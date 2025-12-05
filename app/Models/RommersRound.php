<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $player_id
 * @property int $round_number
 * @property int $level
 * @property int $score
 * @property bool $completed_level
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read RommersPlayer $player
 */
class RommersRound extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'player_id',
        'round_number',
        'level',
        'score',
        'completed_level',
    ];

    protected function casts(): array
    {
        return [
            'player_id' => 'integer',
            'round_number' => 'integer',
            'level' => 'integer',
            'score' => 'integer',
            'completed_level' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<RommersPlayer, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(RommersPlayer::class, 'player_id');
    }
}
