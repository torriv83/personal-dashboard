<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property int $sort_order
 * @property bool $is_shared
 * @property string|null $share_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WishlistItem> $items
 */
class WishlistGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sort_order',
        'is_shared',
        'share_token',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_shared' => 'boolean',
        ];
    }

    /**
     * @return HasMany<WishlistItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(WishlistItem::class, 'group_id');
    }

    /**
     * @param  Builder<WishlistGroup>  $query
     * @return Builder<WishlistGroup>
     */
    public function scopeSharedWithToken(Builder $query, string $token): Builder
    {
        return $query->where('is_shared', true)->where('share_token', $token);
    }

    public function generateShareToken(): string
    {
        $this->share_token = Str::random(32);
        $this->save();

        return $this->share_token;
    }

    public function getShareUrl(): ?string
    {
        if (! $this->is_shared || ! $this->share_token) {
            return null;
        }

        return route('wishlist.shared', $this->share_token);
    }
}
