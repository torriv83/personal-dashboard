<?php

namespace App\Livewire\Tasks;

use App\Models\Assistant;
use App\Models\TaskList;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property-read Collection<int, array<string, mixed>> $taskLists
 * @property-read Collection<int, array<string, mixed>> $assistants
 */
#[Layout('components.layouts.app')]
class Index extends Component
{
    // Modal states
    public bool $showListModal = false;

    // Form data for list
    public ?int $editingListId = null;

    public string $listName = '';

    public bool $listIsShared = false;

    public bool $listAllowAssistantAdd = false;

    public ?int $listAssistantId = null;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function taskLists(): Collection
    {
        /** @var Collection<int, array<string, mixed>> */
        return TaskList::query()
            ->with('assistant')
            ->withCount('tasks')
            ->withCount(['tasks as completed_count' => fn ($query) => $query->where('status', 'completed')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (TaskList $list): array => [
                'id' => $list->id,
                'name' => $list->name,
                'slug' => $list->slug,
                'is_shared' => $list->is_shared,
                'allow_assistant_add' => $list->allow_assistant_add,
                'assistant_id' => $list->assistant_id,
                'assistant_name' => $list->assistant?->name,
                'sort_order' => $list->sort_order,
                'task_count' => (int) $list->tasks_count,
                'completed_count' => (int) $list->getAttribute('completed_count'),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function assistants(): Collection
    {
        /** @var Collection<int, array<string, mixed>> */
        return Assistant::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Assistant $assistant): array => [
                'id' => $assistant->id,
                'name' => $assistant->name,
            ]);
    }

    public function openListModal(?int $id = null): void
    {
        $this->resetListForm();
        $this->editingListId = $id;

        if ($id) {
            $list = TaskList::find($id);
            if ($list) {
                $this->listName = $list->name;
                $this->listIsShared = $list->is_shared;
                $this->listAllowAssistantAdd = $list->allow_assistant_add;
                $this->listAssistantId = $list->assistant_id;
            }
        }

        $this->showListModal = true;
    }

    public function closeListModal(): void
    {
        $this->showListModal = false;
        $this->resetListForm();
    }

    public function resetListForm(): void
    {
        $this->editingListId = null;
        $this->listName = '';
        $this->listIsShared = false;
        $this->listAllowAssistantAdd = false;
        $this->listAssistantId = null;
        $this->resetValidation();
    }

    public function saveList(): void
    {
        $validated = $this->validate([
            'listName' => 'required|string|max:255',
            'listIsShared' => 'boolean',
            'listAllowAssistantAdd' => 'boolean',
            'listAssistantId' => 'nullable|exists:assistants,id',
        ], [
            'listName.required' => 'Navn er påkrevd.',
        ]);

        // is_shared and assistant_id are mutually exclusive
        $isShared = $validated['listIsShared'];
        $assistantId = $validated['listAssistantId'];

        // If shared, clear assistant. If assistant set, clear shared.
        if ($isShared) {
            $assistantId = null;
        } elseif ($assistantId) {
            $isShared = false;
        }

        // allow_assistant_add only makes sense for shared lists
        $allowAssistantAdd = $isShared ? $validated['listAllowAssistantAdd'] : false;

        $data = [
            'name' => $validated['listName'],
            'is_shared' => $isShared,
            'allow_assistant_add' => $allowAssistantAdd,
            'assistant_id' => $assistantId,
        ];

        if ($this->editingListId) {
            $list = TaskList::find($this->editingListId);
            if (! $list) {
                $this->dispatch('toast', type: 'error', message: 'Listen ble ikke funnet');
                $this->closeListModal();

                return;
            }
            $list->update($data);
            $this->dispatch('toast', type: 'success', message: 'Listen ble oppdatert');
        } else {
            // Set sort_order for new list
            $maxSortOrder = TaskList::max('sort_order') ?? 0;
            $data['sort_order'] = $maxSortOrder + 1;

            TaskList::create($data);
            $this->dispatch('toast', type: 'success', message: 'Listen ble opprettet');
        }

        $this->closeListModal();
        unset($this->taskLists);
    }

    public function deleteList(int $id): void
    {
        $list = TaskList::find($id);
        if ($list) {
            $list->delete(); // Cascade handles task deletion
            $this->dispatch('toast', type: 'success', message: 'Listen ble slettet');
        }
        unset($this->taskLists);
    }

    public function updateOrder(string $item, int $position): void
    {
        // Parse item key (e.g., "list-2")
        [$type, $id] = explode('-', $item);
        $id = (int) $id;

        // Get all lists ordered by sort_order
        $lists = TaskList::orderBy('sort_order')->get();

        // Find the moved list and remove it from its current position
        $movedIndex = $lists->search(fn ($list) => $list->id === $id);
        if ($movedIndex === false) {
            return;
        }

        $movedList = $lists->pull($movedIndex);
        $lists = $lists->values();

        // Insert at new position
        $lists->splice($position, 0, [$movedList]);

        // Update sort_order for all lists
        foreach ($lists as $index => $list) {
            $list->sort_order = $index;
            $list->save();
        }

        unset($this->taskLists);
    }

    public function render()
    {
        return view('livewire.tasks.index');
    }
}
