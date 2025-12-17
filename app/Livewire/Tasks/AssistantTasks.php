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
 * @property-read Collection $assistantLists
 * @property-read Collection $assignedTasks
 * @property-read ?TaskList $currentList
 */
#[Layout('components.layouts.assistant')]
class AssistantTasks extends Component
{
    public Assistant $assistant;

    public ?int $currentListId = null;

    // Quick add form
    public string $newTaskTitle = '';

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

        // Check both shared lists and assistant's own lists
        return $this->sharedLists->firstWhere('id', $this->currentListId)
            ?? $this->assistantLists->firstWhere('id', $this->currentListId);
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
     * Get task lists assigned to this assistant.
     *
     * @return Collection<int, TaskList>
     */
    #[Computed]
    public function assistantLists(): Collection
    {
        return TaskList::query()
            ->where('assistant_id', $this->assistant->id)
            ->where('is_shared', false)
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
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
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
        unset($this->sharedLists, $this->assistantLists, $this->assignedTasks, $this->currentList);
    }

    /**
     * Add a new task to the assistant's own list (from "Dine oppgaver" view).
     */
    public function addTaskToOwnList(): void
    {
        $ownList = $this->assistantLists->first();

        if (! $ownList) {
            return;
        }

        $validated = $this->validate([
            'newTaskTitle' => 'required|string|max:255',
        ], [
            'newTaskTitle.required' => 'Tittel er påkrevd.',
            'newTaskTitle.max' => 'Tittel kan ikke være lengre enn 255 tegn.',
        ]);

        // Get the next sort_order
        $maxSortOrder = $ownList->tasks()->max('sort_order') ?? 0;

        Task::create([
            'task_list_id' => $ownList->id,
            'title' => $validated['newTaskTitle'],
            'priority' => 'low',
            'assistant_id' => $this->assistant->id,
            'status' => TaskStatus::Pending,
            'sort_order' => $maxSortOrder + 1,
        ]);

        // Reset form
        $this->newTaskTitle = '';

        // Clear computed caches
        unset($this->sharedLists, $this->assistantLists, $this->assignedTasks, $this->currentList);

        $this->dispatch('toast', type: 'success', message: 'Oppgave lagt til');
    }

    /**
     * Add a new task to the currently selected shared list.
     */
    public function addTaskToSharedList(): void
    {
        $list = $this->currentList;

        // Must have a list selected, and it must allow assistant adds
        if (! $list || ! $list->is_shared || ! $list->allow_assistant_add) {
            return;
        }

        $validated = $this->validate([
            'newTaskTitle' => 'required|string|max:255',
        ], [
            'newTaskTitle.required' => 'Tittel er påkrevd.',
            'newTaskTitle.max' => 'Tittel kan ikke være lengre enn 255 tegn.',
        ]);

        // Get the next sort_order
        $maxSortOrder = $list->tasks()->max('sort_order') ?? 0;

        Task::create([
            'task_list_id' => $list->id,
            'title' => $validated['newTaskTitle'],
            'priority' => 'low',
            'assistant_id' => null, // Shared list tasks don't have a specific assistant
            'status' => TaskStatus::Pending,
            'sort_order' => $maxSortOrder + 1,
        ]);

        // Reset form
        $this->newTaskTitle = '';

        // Clear computed caches
        unset($this->sharedLists, $this->assistantLists, $this->assignedTasks, $this->currentList);

        $this->dispatch('toast', type: 'success', message: 'Oppgave lagt til');
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
