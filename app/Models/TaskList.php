<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $is_shared
 * @property bool $allow_assistant_add
 * @property int|null $assistant_id
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Task> $tasks
 * @property-read Assistant|null $assistant
 */
class TaskList extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_shared',
        'allow_assistant_add',
        'assistant_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_shared' => 'boolean',
            'allow_assistant_add' => 'boolean',
            'assistant_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TaskList $taskList): void {
            if (empty($taskList->slug)) {
                $baseSlug = Str::slug($taskList->name);
                $slug = $baseSlug;
                $count = 1;

                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$count++;
                }

                $taskList->slug = $slug;
            }
        });

        // Cascade assistant_id changes to all tasks in the list
        static::updated(function (TaskList $taskList): void {
            if ($taskList->isDirty('assistant_id')) {
                $taskList->tasks()->update(['assistant_id' => $taskList->assistant_id]);
            }
        });
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return BelongsTo<Assistant, $this>
     */
    public function assistant(): BelongsTo
    {
        return $this->belongsTo(Assistant::class);
    }

    /**
     * Check if this list is assigned to a specific assistant.
     */
    public function isAssignedToAssistant(): bool
    {
        return $this->assistant_id !== null;
    }

    /**
     * Scope to get only shared lists.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TaskList>  $query
     * @return \Illuminate\Database\Eloquent\Builder<TaskList>
     */
    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }
}
