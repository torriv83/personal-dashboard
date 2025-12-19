<?php

namespace App\Livewire\Tasks;

use App\Enums\TaskStatus;
use App\Models\Assistant;
use App\Models\Shift;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\User;
use App\Notifications\AssistantAbsenceDeleted;
use App\Notifications\AssistantAbsenceRegistered;
use App\Notifications\AssistantTaskUpdated;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property-read Collection $sharedLists
 * @property-read Collection $assistantLists
 * @property-read Collection $assignedTasks
 * @property-read ?TaskList $currentList
 * @property-read Collection $upcomingAbsences
 * @property-read Collection $pastAbsences
 */
#[Layout('components.layouts.assistant')]
class AssistantTasks extends Component
{
    public Assistant $assistant;

    public ?int $currentListId = null;

    // Quick add form
    public string $newTaskTitle = '';

    // Absence form
    public ?int $editingAbsenceId = null;

    public string $absenceStartDate = '';

    public string $absenceEndDate = '';

    public string $absenceStartTime = '08:00';

    public string $absenceEndTime = '16:00';

    public bool $absenceIsAllDay = true;

    public string $absenceNote = '';

    public bool $showAbsenceForm = false;

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
     * Get all tasks from lists assigned to this assistant (including dividers).
     *
     * @return Collection<int, Task>
     */
    #[Computed]
    public function assignedTasks(): Collection
    {
        return Task::query()
            ->whereHas('taskList', fn ($q) => $q
                ->where('assistant_id', $this->assistant->id)
                ->where('is_shared', false)
            )
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

        $wasCompleted = $task->status === TaskStatus::Completed;
        $task->status = $wasCompleted
            ? TaskStatus::Pending
            : TaskStatus::Completed;
        $task->save();

        // Send push notification when task is completed
        if (! $wasCompleted && $task->status === TaskStatus::Completed) {
            $owner = User::first();
            if ($owner) {
                $owner->notify(new AssistantTaskUpdated($task, $this->assistant, 'completed'));
            }
        }

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

        // Find first divider's sort_order, or use max + 1 if no dividers
        $firstDivider = $ownList->tasks()->where('is_divider', true)->orderBy('sort_order')->first();
        $sortOrder = $firstDivider
            ? $firstDivider->sort_order - 1
            : ($ownList->tasks()->max('sort_order') ?? 0) + 1;

        $task = Task::create([
            'task_list_id' => $ownList->id,
            'title' => $validated['newTaskTitle'],
            'priority' => 'low',
            'assistant_id' => $this->assistant->id,
            'status' => TaskStatus::Pending,
            'sort_order' => $sortOrder,
        ]);

        // Send push notification to owner
        $owner = User::first();
        if ($owner) {
            $owner->notify(new AssistantTaskUpdated($task, $this->assistant, 'added'));
        }

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

        // Find first divider's sort_order, or use max + 1 if no dividers
        $firstDivider = $list->tasks()->where('is_divider', true)->orderBy('sort_order')->first();
        $sortOrder = $firstDivider
            ? $firstDivider->sort_order - 1
            : ($list->tasks()->max('sort_order') ?? 0) + 1;

        $task = Task::create([
            'task_list_id' => $list->id,
            'title' => $validated['newTaskTitle'],
            'priority' => 'low',
            'assistant_id' => null, // Shared list tasks don't have a specific assistant
            'status' => TaskStatus::Pending,
            'sort_order' => $sortOrder,
        ]);

        // Send push notification to owner
        $owner = User::first();
        if ($owner) {
            $owner->notify(new AssistantTaskUpdated($task, $this->assistant, 'added'));
        }

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

    /**
     * Get current and upcoming absences for this assistant.
     * Shows absences that haven't ended yet.
     *
     * @return Collection<int, Shift>
     */
    #[Computed]
    public function upcomingAbsences(): Collection
    {
        return Shift::query()
            ->where('assistant_id', $this->assistant->id)
            ->where('is_unavailable', true)
            ->where('ends_at', '>=', now()->startOfDay())
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Get past absences for this assistant (last 3 months).
     * Shows absences that have already ended.
     *
     * @return Collection<int, Shift>
     */
    #[Computed]
    public function pastAbsences(): Collection
    {
        return Shift::query()
            ->where('assistant_id', $this->assistant->id)
            ->where('is_unavailable', true)
            ->where('ends_at', '<', now()->startOfDay())
            ->where('ends_at', '>=', now()->subMonths(3))
            ->orderByDesc('starts_at')
            ->get();
    }

    /**
     * Show the absence form for creating a new absence.
     */
    public function showCreateAbsenceForm(): void
    {
        $this->resetAbsenceForm();
        $this->absenceStartDate = now()->format('Y-m-d');
        $this->absenceEndDate = now()->format('Y-m-d');
        $this->showAbsenceForm = true;
    }

    /**
     * Show the absence form for editing an existing absence.
     */
    public function editAbsence(int $absenceId): void
    {
        $absence = Shift::find($absenceId);

        if (! $absence || $absence->assistant_id !== $this->assistant->id) {
            return;
        }

        $this->editingAbsenceId = $absence->id;
        $this->absenceStartDate = $absence->starts_at->format('Y-m-d');
        $this->absenceEndDate = $absence->ends_at->format('Y-m-d');
        $this->absenceIsAllDay = $absence->is_all_day;
        $this->absenceStartTime = $absence->starts_at->format('H:i');
        $this->absenceEndTime = $absence->ends_at->format('H:i');
        $this->absenceNote = $absence->note ?? '';
        $this->showAbsenceForm = true;
    }

    /**
     * Save (create or update) an absence.
     */
    public function saveAbsence(): void
    {
        $this->validate([
            'absenceStartDate' => 'required|date',
            'absenceEndDate' => 'required|date|after_or_equal:absenceStartDate',
            'absenceStartTime' => 'required_if:absenceIsAllDay,false',
            'absenceEndTime' => 'required_if:absenceIsAllDay,false',
            'absenceNote' => 'nullable|string|max:255',
        ], [
            'absenceStartDate.required' => 'Fra-dato er påkrevd.',
            'absenceEndDate.required' => 'Til-dato er påkrevd.',
            'absenceEndDate.after_or_equal' => 'Til-dato må være etter eller lik fra-dato.',
            'absenceNote.max' => 'Merknad kan ikke være lengre enn 255 tegn.',
        ]);

        if ($this->absenceIsAllDay) {
            $startsAt = Carbon::parse($this->absenceStartDate)->startOfDay();
            $endsAt = Carbon::parse($this->absenceEndDate)->endOfDay();
        } else {
            $startsAt = Carbon::parse($this->absenceStartDate.' '.$this->absenceStartTime);
            $endsAt = Carbon::parse($this->absenceEndDate.' '.$this->absenceEndTime);

            if ($endsAt->lte($startsAt)) {
                $this->addError('absenceEndTime', 'Slutt-tid må være etter start-tid.');

                return;
            }
        }

        // Sjekk for overlappende fravær
        $overlapping = Shift::findOverlappingUnavailability(
            $this->assistant->id,
            $startsAt,
            $endsAt,
            $this->editingAbsenceId
        );

        if ($overlapping) {
            $this->addError('absenceStartDate', 'Det finnes allerede et fravær som overlapper med denne perioden.');

            return;
        }

        $data = [
            'assistant_id' => $this->assistant->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_unavailable' => true,
            'is_all_day' => $this->absenceIsAllDay,
            'note' => $this->absenceNote ?: null,
        ];

        if ($this->editingAbsenceId) {
            $absence = Shift::find($this->editingAbsenceId);
            if ($absence && $absence->assistant_id === $this->assistant->id) {
                $absence->update($data);
                $this->dispatch('toast', type: 'success', message: 'Fravær oppdatert');
            }
        } else {
            $absence = Shift::create($data);
            $this->dispatch('toast', type: 'success', message: 'Fravær registrert');
        }

        // Send push notification to owner
        if (isset($absence)) {
            $owner = User::first();
            if ($owner) {
                $owner->notify(new AssistantAbsenceRegistered($absence, $this->assistant));
            }
        }

        $this->resetAbsenceForm();
        unset($this->upcomingAbsences, $this->pastAbsences);
    }

    /**
     * Delete an absence.
     */
    public function deleteAbsence(int $absenceId): void
    {
        $absence = Shift::find($absenceId);

        if (! $absence || $absence->assistant_id !== $this->assistant->id) {
            return;
        }

        // Store absence data before deletion for notification
        $startsAt = $absence->starts_at;
        $endsAt = $absence->ends_at;
        $note = $absence->note;

        $absence->delete();

        // Send push notification to owner
        $owner = User::first();
        if ($owner) {
            $owner->notify(new AssistantAbsenceDeleted($this->assistant, $startsAt, $endsAt, $note));
        }

        $this->dispatch('toast', type: 'success', message: 'Fravær slettet');
        unset($this->upcomingAbsences, $this->pastAbsences);
    }

    /**
     * Cancel editing and hide the form.
     */
    public function cancelAbsenceForm(): void
    {
        $this->resetAbsenceForm();
    }

    /**
     * Reset the absence form to defaults.
     */
    private function resetAbsenceForm(): void
    {
        $this->editingAbsenceId = null;
        $this->absenceStartDate = '';
        $this->absenceEndDate = '';
        $this->absenceStartTime = '08:00';
        $this->absenceEndTime = '16:00';
        $this->absenceIsAllDay = true;
        $this->absenceNote = '';
        $this->showAbsenceForm = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.tasks.assistant-tasks');
    }
}
