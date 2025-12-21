<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $color
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Bookmark> $bookmarks
 */
class BookmarkTag extends Model
{
    /** @use HasFactory<\Database\Factories\BookmarkTagFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<Bookmark, $this>
     */
    public function bookmarks(): BelongsToMany
    {
        return $this->belongsToMany(Bookmark::class, 'bookmark_tag', 'tag_id', 'bookmark_id');
    }
}
