<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Livewire\Tasks\AssistantTasks;
use App\Models\Assistant;
use App\Models\Task;
use App\Models\TaskList;
use Livewire\Livewire;

use function Pest\Laravel\get;

beforeEach(function () {
    $this->assistant = Assistant::factory()->create(['name' => 'Test Assistent']);
});

it('renders the assistant tasks page with valid token', function () {
    get(route('tasks.assistant', $this->assistant))
        ->assertOk()
        ->assertSeeLivewire(AssistantTasks::class);
});

it('returns 404 for invalid token', function () {
    get('/oppgaver/invalid-token-that-does-not-exist')
        ->assertNotFound();
});

it('displays assistant name in greeting', function () {
    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->assertSee('Hei, Test Assistent!');
});

it('has shared lists available in component', function () {
    $sharedList = TaskList::factory()->shared()->create(['name' => 'Felles Handletur']);
    Task::factory()->create([
        'task_list_id' => $sharedList->id,
        'title' => 'Kjøp melk',
    ]);

    $component = Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant]);

    // Shared list is available via computed property (dropdown is rendered in layout slot)
    expect($component->instance()->sharedLists)->toHaveCount(1);
    expect($component->instance()->sharedLists->first()->name)->toBe('Felles Handletur');

    // Task is not visible until list is selected
    $component->assertDontSee('Kjøp melk');
});

it('displays shared list tasks when selected', function () {
    $sharedList = TaskList::factory()->shared()->create(['name' => 'Felles Handletur']);
    Task::factory()->create([
        'task_list_id' => $sharedList->id,
        'title' => 'Kjøp melk',
    ]);

    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->call('selectList', $sharedList->id)
        ->assertSee('Felles Handletur')
        ->assertSee('Kjøp melk');
});

it('displays tasks from lists assigned to this assistant', function () {
    // Create a list that is assigned to this assistant (not a task assigned to the assistant)
    $assistantList = TaskList::factory()->forAssistant($this->assistant->id)->create(['name' => 'Assistentens liste']);
    Task::factory()->create([
        'task_list_id' => $assistantList->id,
        'title' => 'Min tildelte oppgave',
    ]);

    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->assertSee('Min tildelte oppgave');
});

it('does not display tasks assigned to other assistants', function () {
    $otherAssistant = Assistant::factory()->create();
    $privateList = TaskList::factory()->create(['is_shared' => false]);
    Task::factory()->create([
        'task_list_id' => $privateList->id,
        'title' => 'Annen assistents oppgave',
        'assistant_id' => $otherAssistant->id,
    ]);

    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->assertDontSee('Annen assistents oppgave');
});

it('can toggle task status on shared list', function () {
    $sharedList = TaskList::factory()->shared()->create();
    $task = Task::factory()->create([
        'task_list_id' => $sharedList->id,
        'status' => TaskStatus::Pending,
    ]);

    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->call('toggleTask', $task->id);

    expect($task->fresh()->status)->toBe(TaskStatus::Completed);
});

it('can toggle task status on assigned task', function () {
    $privateList = TaskList::factory()->create(['is_shared' => false]);
    $task = Task::factory()->create([
        'task_list_id' => $privateList->id,
        'assistant_id' => $this->assistant->id,
        'status' => TaskStatus::Pending,
    ]);

    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->call('toggleTask', $task->id);

    expect($task->fresh()->status)->toBe(TaskStatus::Completed);
});

it('can toggle task on own list without explicit assistant_id on task', function () {
    $ownList = TaskList::factory()->create([
        'is_shared' => false,
        'assistant_id' => $this->assistant->id,
    ]);
    $task = Task::factory()->create([
        'task_list_id' => $ownList->id,
        'assistant_id' => null,
        'status' => TaskStatus::Pending,
    ]);

    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->call('toggleTask', $task->id);

    expect($task->fresh()->status)->toBe(TaskStatus::Completed);
});

it('cannot toggle task not accessible to assistant', function () {
    $otherAssistant = Assistant::factory()->create();
    $privateList = TaskList::factory()->create(['is_shared' => false]);
    $task = Task::factory()->create([
        'task_list_id' => $privateList->id,
        'assistant_id' => $otherAssistant->id,
        'status' => TaskStatus::Pending,
    ]);

    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->call('toggleTask', $task->id);

    // Status should remain unchanged
    expect($task->fresh()->status)->toBe(TaskStatus::Pending);
});

it('shows empty state when no tasks', function () {
    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->assertSee('Ingen oppgaver')
        ->assertSee('Du har ingen oppgaver tildelt akkurat nå.');
});

it('has correct task counts on shared lists', function () {
    $sharedList = TaskList::factory()->shared()->create(['name' => 'Test Liste']);
    Task::factory()->count(3)->create([
        'task_list_id' => $sharedList->id,
        'status' => TaskStatus::Pending,
    ]);
    Task::factory()->count(2)->create([
        'task_list_id' => $sharedList->id,
        'status' => TaskStatus::Completed,
    ]);

    $component = Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant]);

    // Check computed property has correct counts
    $list = $component->instance()->sharedLists->first();
    expect($list->tasks)->toHaveCount(5);
    expect($list->tasks->where('status', TaskStatus::Pending)->count())->toBe(3);
});

it('can switch between lists', function () {
    $sharedList = TaskList::factory()->shared()->create(['name' => 'Handleliste']);

    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->assertSet('currentListId', null)
        ->call('selectList', $sharedList->id)
        ->assertSet('currentListId', $sharedList->id)
        ->call('selectList', null)
        ->assertSet('currentListId', null);
});

it('has assistant lists available in component', function () {
    $assistantList = TaskList::factory()->create([
        'name' => 'Min egen liste',
        'assistant_id' => $this->assistant->id,
        'is_shared' => false,
    ]);

    $component = Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant]);

    expect($component->instance()->assistantLists)->toHaveCount(1);
    expect($component->instance()->assistantLists->first()->name)->toBe('Min egen liste');
});

it('does not show other assistants lists', function () {
    $otherAssistant = Assistant::factory()->create();
    TaskList::factory()->create([
        'name' => 'Annen assistents liste',
        'assistant_id' => $otherAssistant->id,
        'is_shared' => false,
    ]);

    $component = Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant]);

    expect($component->instance()->assistantLists)->toHaveCount(0);
});

it('can add task to own list from dine oppgaver view', function () {
    $assistantList = TaskList::factory()->create([
        'name' => 'Min liste',
        'assistant_id' => $this->assistant->id,
        'is_shared' => false,
    ]);

    // From "Dine oppgaver" view (no list selected)
    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->assertSet('currentListId', null)
        ->set('newTaskTitle', 'Ny oppgave fra assistent')
        ->call('addTaskToOwnList');

    expect(Task::where('title', 'Ny oppgave fra assistent')->exists())->toBeTrue();
    $task = Task::where('title', 'Ny oppgave fra assistent')->first();
    expect($task->task_list_id)->toBe($assistantList->id);
    expect($task->assistant_id)->toBe($this->assistant->id);
    expect($task->priority->value)->toBe('low');
    expect($task->status)->toBe(TaskStatus::Pending);
});

it('cannot add task without having own list', function () {
    // Assistant has no list assigned
    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->set('newTaskTitle', 'Forsøk på oppgave')
        ->call('addTaskToOwnList');

    expect(Task::where('title', 'Forsøk på oppgave')->exists())->toBeFalse();
});

it('validates task title when adding', function () {
    TaskList::factory()->create([
        'assistant_id' => $this->assistant->id,
        'is_shared' => false,
    ]);

    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->set('newTaskTitle', '')
        ->call('addTaskToOwnList')
        ->assertHasErrors(['newTaskTitle' => 'required']);
});

it('resets form after adding task', function () {
    TaskList::factory()->create([
        'assistant_id' => $this->assistant->id,
        'is_shared' => false,
    ]);

    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->set('newTaskTitle', 'Test oppgave')
        ->call('addTaskToOwnList')
        ->assertSet('newTaskTitle', '');
});

it('can add task to shared list when allow_assistant_add is true', function () {
    $sharedList = TaskList::factory()->shared()->allowAssistantAdd()->create(['name' => 'Felles Handletur']);

    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->call('selectList', $sharedList->id)
        ->set('newTaskTitle', 'Oppgave på felles liste')
        ->call('addTaskToSharedList');

    expect(Task::where('title', 'Oppgave på felles liste')->exists())->toBeTrue();
    $task = Task::where('title', 'Oppgave på felles liste')->first();
    expect($task->task_list_id)->toBe($sharedList->id);
    expect($task->assistant_id)->toBeNull();
    expect($task->priority->value)->toBe('low');
    expect($task->status)->toBe(TaskStatus::Pending);
});

it('cannot add task to shared list when allow_assistant_add is false', function () {
    $sharedList = TaskList::factory()->shared()->create(['name' => 'Felles Handletur', 'allow_assistant_add' => false]);

    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->call('selectList', $sharedList->id)
        ->set('newTaskTitle', 'Forsøk på oppgave')
        ->call('addTaskToSharedList');

    expect(Task::where('title', 'Forsøk på oppgave')->exists())->toBeFalse();
});

it('cannot add task to shared list without selecting a list first', function () {
    TaskList::factory()->shared()->allowAssistantAdd()->create();

    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->assertSet('currentListId', null)
        ->set('newTaskTitle', 'Forsøk på oppgave')
        ->call('addTaskToSharedList');

    expect(Task::where('title', 'Forsøk på oppgave')->exists())->toBeFalse();
});

it('validates task title when adding to shared list', function () {
    $sharedList = TaskList::factory()->shared()->allowAssistantAdd()->create();

    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->call('selectList', $sharedList->id)
        ->set('newTaskTitle', '')
        ->call('addTaskToSharedList')
        ->assertHasErrors(['newTaskTitle' => 'required']);
});

it('resets form after adding task to shared list', function () {
    $sharedList = TaskList::factory()->shared()->allowAssistantAdd()->create();

    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->call('selectList', $sharedList->id)
        ->set('newTaskTitle', 'Test oppgave')
        ->call('addTaskToSharedList')
        ->assertSet('newTaskTitle', '');
});

it('dispatches toast after adding task to shared list', function () {
    $sharedList = TaskList::factory()->shared()->allowAssistantAdd()->create();

    Livewire::test(AssistantTasks::class, ['assistant' => $this->assistant])
        ->call('selectList', $sharedList->id)
        ->set('newTaskTitle', 'Test oppgave')
        ->call('addTaskToSharedList')
        ->assertDispatched('toast', type: 'success', message: 'Oppgave lagt til');
});
