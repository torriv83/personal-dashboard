<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $game_id
 * @property string $name
 * @property int $current_level
 * @property int $total_score
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read RommersGame $game
 * @property-read \Illuminate\Database\Eloquent\Collection<int, RommersRound> $rounds
 */
class RommersPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'name',
        'current_level',
        'total_score',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'game_id' => 'integer',
            'current_level' => 'integer',
            'total_score' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<RommersGame, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(RommersGame::class, 'game_id');
    }

    /**
     * @return HasMany<RommersRound, $this>
     */
    public function rounds(): HasMany
    {
        return $this->hasMany(RommersRound::class, 'player_id')->orderBy('round_number');
    }

    /**
     * Check if the player has completed all levels.
     */
    public function hasWon(): bool
    {
        return $this->current_level > 11;
    }
}
