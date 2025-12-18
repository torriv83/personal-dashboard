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
 * @property-read Collection $pendingTasks
 * @property-read Collection $completedTasks
 * @property-read Collection $assistants
 * @property-read array<string, string> $priorityOptions
 * @property-read bool $listHasAssistant
 */
#[Layout('components.layouts.app')]
class Show extends Component
{
    public TaskList $taskList;

    // Quick add form (priority and assistant managed by Livewire, title by Alpine)
    public string $newTaskPriority = 'low';

    public ?int $newTaskAssistantId = null;

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
     * @return Collection<int, Task>
     */
    #[Computed]
    public function pendingTasks(): Collection
    {
        return $this->tasks->where('status', TaskStatus::Pending);
    }

    /**
     * @return Collection<int, Task>
     */
    #[Computed]
    public function completedTasks(): Collection
    {
        return $this->tasks->where('status', TaskStatus::Completed);
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

    public function addTaskFromAlpine(string $title, string $priority, ?int $assistantId): void
    {
        $title = trim($title);
        if (empty($title) || strlen($title) > 255) {
            return;
        }

        if (! in_array($priority, ['low', 'medium', 'high'])) {
            $priority = 'low';
        }

        // Get the next sort_order
        $maxSortOrder = $this->taskList->tasks()->max('sort_order') ?? 0;

        // If list has an assistant, use that instead of the form value
        $finalAssistantId = $this->listHasAssistant
            ? $this->taskList->assistant_id
            : $assistantId;

        Task::create([
            'task_list_id' => $this->taskList->id,
            'title' => $title,
            'priority' => $priority,
            'assistant_id' => $finalAssistantId,
            'status' => TaskStatus::Pending,
            'sort_order' => $maxSortOrder + 1,
        ]);

        // Reset select values
        $this->newTaskPriority = 'low';
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

    public function updateTaskTitle(int $taskId, string $title): void
    {
        $task = Task::find($taskId);
        if (! $task || $task->task_list_id !== $this->taskList->id) {
            return;
        }

        $title = trim($title);
        if (empty($title)) {
            return;
        }

        $task->update(['title' => $title]);

        unset($this->tasks);
        $this->dispatch('toast', type: 'success', message: 'Oppgave oppdatert');
    }

    public function cycleTaskPriority(int $taskId): void
    {
        $task = Task::find($taskId);
        if (! $task || $task->task_list_id !== $this->taskList->id) {
            return;
        }

        $priorities = [TaskPriority::Low, TaskPriority::Medium, TaskPriority::High];
        $currentIndex = array_search($task->priority, $priorities);
        $nextIndex = ($currentIndex + 1) % count($priorities);

        $task->update(['priority' => $priorities[$nextIndex]]);

        unset($this->tasks);
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

        // Get only pending tasks ordered by sort_order (completed tasks are in separate section)
        $tasks = $this->taskList
            ->tasks()
            ->where('status', TaskStatus::Pending)
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

    public function render()
    {
        return view('livewire.tasks.show');
    }
}
