<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $task_list_id
 * @property string $title
 * @property int|null $assistant_id
 * @property TaskStatus $status
 * @property TaskPriority $priority
 * @property bool $is_divider
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read TaskList $taskList
 * @property-read Assistant|null $assistant
 */
class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_list_id',
        'title',
        'assistant_id',
        'status',
        'priority',
        'is_divider',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'task_list_id' => 'integer',
            'assistant_id' => 'integer',
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'is_divider' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<TaskList, $this>
     */
    public function taskList(): BelongsTo
    {
        return $this->belongsTo(TaskList::class);
    }

    /**
     * @return BelongsTo<Assistant, $this>
     */
    public function assistant(): BelongsTo
    {
        return $this->belongsTo(Assistant::class);
    }

    /**
     * Scope to get only pending tasks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Task>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Task>
     */
    public function scopePending($query)
    {
        return $query->where('status', TaskStatus::Pending);
    }

    /**
     * Scope to get only completed tasks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Task>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Task>
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', TaskStatus::Completed);
    }

    /**
     * Check if this task is assigned to an assistant.
     */
    public function isAssigned(): bool
    {
        return $this->assistant_id !== null;
    }

    /**
     * Check if this task is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === TaskStatus::Completed;
    }
}
