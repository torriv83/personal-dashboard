<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $folder_id
 * @property string $url
 * @property string $title
 * @property string|null $description
 * @property string|null $favicon_path
 * @property bool $is_read
 * @property bool $is_dead
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read BookmarkFolder|null $folder
 */
class Bookmark extends Model
{
    /** @use HasFactory<\Database\Factories\BookmarkFactory> */
    use HasFactory;

    protected $fillable = [
        'folder_id',
        'url',
        'title',
        'description',
        'favicon_path',
        'is_read',
        'is_dead',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'folder_id' => 'integer',
            'is_read' => 'boolean',
            'is_dead' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<BookmarkFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(BookmarkFolder::class, 'folder_id');
    }

    /**
     * Check if this bookmark belongs to a folder.
     */
    public function isInFolder(): bool
    {
        return $this->folder_id !== null;
    }

    /**
     * Check if this bookmark is standalone (not in any folder).
     */
    public function isStandalone(): bool
    {
        return $this->folder_id === null;
    }

    /**
     * Get the domain from the URL.
     */
    public function getDomain(): string
    {
        $parsed = parse_url($this->url);

        return $parsed['host'] ?? $this->url;
    }
}
