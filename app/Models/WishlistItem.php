<?php

namespace App\Models;

use App\Enums\WishlistStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $group_id
 * @property string $name
 * @property string|null $url
 * @property int $price
 * @property int $quantity
 * @property WishlistStatus $status
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read WishlistGroup|null $group
 */
class WishlistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'name',
        'url',
        'price',
        'quantity',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'group_id' => 'integer',
            'price' => 'integer',
            'quantity' => 'integer',
            'sort_order' => 'integer',
            'status' => WishlistStatus::class,
        ];
    }

    /**
     * @return BelongsTo<WishlistGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(WishlistGroup::class, 'group_id');
    }

    /**
     * Check if this item belongs to a group.
     */
    public function isGrouped(): bool
    {
        return $this->group_id !== null;
    }

    /**
     * Check if this item is a standalone (top-level) item.
     */
    public function isStandalone(): bool
    {
        return $this->group_id === null;
    }
}
