<?php

namespace App\Livewire\Tasks;

use App\Enums\TaskStatus;
use App\Models\Assistant;
use App\Models\Task;
use App\Models\TaskList;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property-read Collection $sharedLists
 * @property-read Collection $assignedTasks
 * @property-read ?TaskList $currentList
 */
#[Layout('components.layouts.assistant')]
class AssistantTasks extends Component
{
    public Assistant $assistant;

    public ?int $currentListId = null;

    public function mount(Assistant $assistant): void
    {
        $this->assistant = $assistant;
    }

    /**
     * Switch to a different list view.
     */
    public function selectList(?int $listId): void
    {
        $this->currentListId = $listId;
        unset($this->currentList);
    }

    /**
     * Get the currently selected list (if any).
     */
    #[Computed]
    public function currentList(): ?TaskList
    {
        if ($this->currentListId === null) {
            return null;
        }

        return $this->sharedLists->firstWhere('id', $this->currentListId);
    }

    /**
     * Get all shared task lists with their tasks.
     *
     * @return Collection<int, TaskList>
     */
    #[Computed]
    public function sharedLists(): Collection
    {
        return TaskList::query()
            ->where('is_shared', true)
            ->with(['tasks' => fn ($q) => $q->orderBy('sort_order')->orderBy('created_at')])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get tasks assigned directly to this assistant on non-shared lists.
     *
     * @return Collection<int, Task>
     */
    #[Computed]
    public function assignedTasks(): Collection
    {
        return Task::query()
            ->where('assistant_id', $this->assistant->id)
            ->whereHas('taskList', fn ($q) => $q->where('is_shared', false))
            ->with('taskList')
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Toggle task completion status.
     */
    public function toggleTask(int $taskId): void
    {
        $task = Task::find($taskId);

        if (! $task) {
            return;
        }

        // Verify assistant has access to this task
        if (! $this->canAccessTask($task)) {
            return;
        }

        $task->status = $task->status === TaskStatus::Pending
            ? TaskStatus::Completed
            : TaskStatus::Pending;
        $task->save();

        // Clear computed caches
        unset($this->sharedLists, $this->assignedTasks);
    }

    /**
     * Check if the current assistant can access/modify a task.
     */
    private function canAccessTask(Task $task): bool
    {
        // Can access if on a shared list
        if ($task->taskList->is_shared) {
            return true;
        }

        // Can access if directly assigned to this assistant
        if ($task->assistant_id === $this->assistant->id) {
            return true;
        }

        return false;
    }

    public function render()
    {
        return view('livewire.tasks.assistant-tasks');
    }
}
