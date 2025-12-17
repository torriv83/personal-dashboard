<?php

namespace App\Livewire\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Assistant;
use App\Models\Task;
use App\Models\TaskList;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property-read Collection $tasks
 * @property-read Collection $assistants
 * @property-read array<string, string> $priorityOptions
 * @property-read bool $listHasAssistant
 */
#[Layout('components.layouts.app')]
class Show extends Component
{
    public TaskList $taskList;

    // Quick add form
    public string $newTaskTitle = '';

    public string $newTaskPriority = 'medium';

    public ?int $newTaskAssistantId = null;

    // Edit task modal
    public bool $showEditModal = false;

    public ?int $editingTaskId = null;

    public string $editTaskTitle = '';

    public string $editTaskPriority = 'medium';

    public ?int $editTaskAssistantId = null;

    /**
     * @return Collection<int, Task>
     */
    #[Computed]
    public function tasks(): Collection
    {
        return $this->taskList
            ->tasks()
            ->with('assistant')
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * @return Collection<int, Assistant>
     */
    #[Computed]
    public function assistants(): Collection
    {
        return Assistant::orderBy('name')->get();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function priorityOptions(): array
    {
        return TaskPriority::options();
    }

    /**
     * Check if this list is assigned to a specific assistant.
     * If true, all tasks inherit the list's assistant and individual assignment is disabled.
     */
    #[Computed]
    public function listHasAssistant(): bool
    {
        return $this->taskList->assistant_id !== null;
    }

    public function mount(TaskList $taskList): void
    {
        $this->taskList = $taskList->load('assistant');
    }

    public function addTask(): void
    {
        $validated = $this->validate([
            'newTaskTitle' => 'required|string|max:255',
            'newTaskPriority' => 'required|in:low,medium,high',
            'newTaskAssistantId' => 'nullable|exists:assistants,id',
        ], [
            'newTaskTitle.required' => 'Tittel er påkrevd.',
            'newTaskTitle.max' => 'Tittel kan ikke være lengre enn 255 tegn.',
        ]);

        // Get the next sort_order
        $maxSortOrder = $this->taskList->tasks()->max('sort_order') ?? 0;

        // If list has an assistant, use that instead of the form value
        $assistantId = $this->listHasAssistant
            ? $this->taskList->assistant_id
            : $validated['newTaskAssistantId'];

        Task::create([
            'task_list_id' => $this->taskList->id,
            'title' => $validated['newTaskTitle'],
            'priority' => $validated['newTaskPriority'],
            'assistant_id' => $assistantId,
            'status' => TaskStatus::Pending,
            'sort_order' => $maxSortOrder + 1,
        ]);

        // Reset form
        $this->newTaskTitle = '';
        $this->newTaskPriority = 'medium';
        $this->newTaskAssistantId = null;

        unset($this->tasks);
        $this->dispatch('toast', type: 'success', message: 'Oppgave lagt til');
    }

    public function toggleTaskStatus(int $taskId): void
    {
        $task = Task::find($taskId);
        if (! $task || $task->task_list_id !== $this->taskList->id) {
            return;
        }

        $task->status = $task->status === TaskStatus::Pending
            ? TaskStatus::Completed
            : TaskStatus::Pending;
        $task->save();

        unset($this->tasks);
    }

    public function openEditModal(int $taskId): void
    {
        $task = Task::find($taskId);
        if (! $task || $task->task_list_id !== $this->taskList->id) {
            return;
        }

        $this->editingTaskId = $taskId;
        $this->editTaskTitle = $task->title;
        $this->editTaskPriority = $task->priority->value;
        $this->editTaskAssistantId = $task->assistant_id;
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->resetEditForm();
    }

    public function saveTask(): void
    {
        $validated = $this->validate([
            'editTaskTitle' => 'required|string|max:255',
            'editTaskPriority' => 'required|in:low,medium,high',
            'editTaskAssistantId' => 'nullable|exists:assistants,id',
        ], [
            'editTaskTitle.required' => 'Tittel er påkrevd.',
            'editTaskTitle.max' => 'Tittel kan ikke være lengre enn 255 tegn.',
        ]);

        $task = Task::find($this->editingTaskId);
        if (! $task || $task->task_list_id !== $this->taskList->id) {
            return;
        }

        // If list has an assistant, use that instead of the form value
        $assistantId = $this->listHasAssistant
            ? $this->taskList->assistant_id
            : $validated['editTaskAssistantId'];

        $task->update([
            'title' => $validated['editTaskTitle'],
            'priority' => $validated['editTaskPriority'],
            'assistant_id' => $assistantId,
        ]);

        $this->closeEditModal();
        unset($this->tasks);
        $this->dispatch('toast', type: 'success', message: 'Oppgave oppdatert');
    }

    public function deleteTask(int $taskId): void
    {
        $task = Task::find($taskId);
        if (! $task || $task->task_list_id !== $this->taskList->id) {
            return;
        }

        $task->delete();

        unset($this->tasks);
        $this->dispatch('toast', type: 'success', message: 'Oppgave slettet');
    }

    public function updateOrder(string $item, int $position): void
    {
        // Parse item key (e.g., "task-3")
        [$type, $id] = explode('-', $item);
        $id = (int) $id;

        // Verify the task belongs to this list
        $task = Task::find($id);
        if (! $task || $task->task_list_id !== $this->taskList->id) {
            return;
        }

        // Get all tasks ordered by sort_order
        $tasks = $this->taskList
            ->tasks()
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        // Find the moved task and remove it
        $movedIndex = $tasks->search(fn ($t) => $t->id === $id);
        if ($movedIndex === false) {
            return;
        }

        $movedTask = $tasks->pull($movedIndex);
        $tasks = $tasks->values();

        // Insert at new position
        $tasks->splice($position, 0, [$movedTask]);

        // Update sort_order for all tasks
        foreach ($tasks as $index => $taskItem) {
            $taskItem->sort_order = $index;
            $taskItem->save();
        }

        unset($this->tasks);
    }

    private function resetEditForm(): void
    {
        $this->editingTaskId = null;
        $this->editTaskTitle = '';
        $this->editTaskPriority = 'medium';
        $this->editTaskAssistantId = null;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.tasks.show');
    }
}
