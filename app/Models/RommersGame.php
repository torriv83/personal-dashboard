<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property int|null $winner_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read RommersPlayer|null $winner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, RommersPlayer> $players
 */
class RommersGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'started_at',
        'finished_at',
        'winner_id',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'winner_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<RommersPlayer, $this>
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(RommersPlayer::class, 'winner_id');
    }

    /**
     * @return HasMany<RommersPlayer, $this>
     */
    public function players(): HasMany
    {
        return $this->hasMany(RommersPlayer::class, 'game_id')->orderBy('sort_order');
    }

    /**
     * Check if the game is finished.
     */
    public function isFinished(): bool
    {
        return $this->finished_at !== null;
    }

    /**
     * Check if the game is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->finished_at === null;
    }
}
