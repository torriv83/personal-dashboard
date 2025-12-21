<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property int $sort_order
 * @property bool $is_default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read BookmarkFolder|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BookmarkFolder> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Bookmark> $bookmarks
 */
class BookmarkFolder extends Model
{
    /** @use HasFactory<\Database\Factories\BookmarkFolderFactory> */
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'sort_order',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'sort_order' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<BookmarkFolder, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(BookmarkFolder::class, 'parent_id');
    }

    /**
     * @return HasMany<BookmarkFolder, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(BookmarkFolder::class, 'parent_id');
    }

    /**
     * @return HasMany<Bookmark, $this>
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class, 'folder_id');
    }

    /**
     * Get the default folder, if any.
     */
    public static function getDefault(): ?self
    {
        return self::where('is_default', true)->first();
    }

    /**
     * Set this folder as the default, unsetting any previous default.
     */
    public function setAsDefault(): void
    {
        self::where('is_default', true)->update(['is_default' => false]);
        $this->is_default = true;
        $this->save();
    }

    /**
     * Check if this is a root folder (no parent).
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Check if this is a child folder (has a parent).
     */
    public function isChild(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Get the full path including parent name.
     */
    public function getFullPath(): string
    {
        return $this->parent ? "{$this->parent->name} / {$this->name}" : $this->name;
    }
}
