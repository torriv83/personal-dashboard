<?php

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

it('displays tasks assigned to this assistant on non-shared lists', function () {
    $privateList = TaskList::factory()->create(['name' => 'Privat liste', 'is_shared' => false]);
    Task::factory()->create([
        'task_list_id' => $privateList->id,
        'title' => 'Min tildelte oppgave',
        'assistant_id' => $this->assistant->id,
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
