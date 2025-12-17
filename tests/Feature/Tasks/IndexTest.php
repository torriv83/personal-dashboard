<?php

use App\Livewire\Tasks\Index;
use App\Models\Assistant;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

it('renders the tasks index page', function () {
    get(route('bpa.tasks.index'))
        ->assertSeeLivewire(Index::class);
});

it('displays task lists', function () {
    $list1 = TaskList::factory()->create(['name' => 'Handletur']);
    $list2 = TaskList::factory()->create(['name' => 'Ukesoppgaver']);

    Livewire::test(Index::class)
        ->assertSee('Handletur')
        ->assertSee('Ukesoppgaver');
});

it('can create a new task list', function () {
    Livewire::test(Index::class)
        ->set('listName', 'Min nye liste')
        ->set('listIsShared', false)
        ->call('saveList');

    expect(TaskList::where('name', 'Min nye liste')->exists())->toBeTrue();
});

it('can edit an existing task list', function () {
    $list = TaskList::factory()->create(['name' => 'Gammel liste']);

    Livewire::test(Index::class)
        ->call('openListModal', $list->id)
        ->set('listName', 'Oppdatert liste')
        ->call('saveList');

    expect($list->fresh()->name)->toBe('Oppdatert liste');
});

it('can delete a task list', function () {
    $list = TaskList::factory()->create();

    Livewire::test(Index::class)
        ->call('deleteList', $list->id);

    expect(TaskList::find($list->id))->toBeNull();
});

it('shows shared badge for shared lists', function () {
    TaskList::factory()->create([
        'name' => 'Delt liste',
        'is_shared' => true,
    ]);

    Livewire::test(Index::class)
        ->assertSee('Delt liste');
});

it('displays task count', function () {
    $list = TaskList::factory()->create(['name' => 'Test liste']);

    Livewire::test(Index::class)
        ->assertSee('0 oppgaver');
});

it('can assign assistant to list', function () {
    $assistant = Assistant::factory()->create(['name' => 'Test Assistent']);

    Livewire::test(Index::class)
        ->set('listName', 'Liste med assistent')
        ->set('listAssistantId', $assistant->id)
        ->call('saveList');

    $list = TaskList::where('name', 'Liste med assistent')->first();
    expect($list->assistant_id)->toBe($assistant->id);
});

it('enforces is_shared and assistant_id are mutually exclusive', function () {
    $assistant = Assistant::factory()->create();

    Livewire::test(Index::class)
        ->set('listName', 'Test Liste')
        ->set('listIsShared', true)
        ->set('listAssistantId', $assistant->id)
        ->call('saveList');

    $list = TaskList::where('name', 'Test Liste')->first();
    expect($list->is_shared)->toBeTrue();
    expect($list->assistant_id)->toBeNull();
});

it('cascades assistant_id changes to all tasks in the list', function () {
    $assistant1 = Assistant::factory()->create();
    $assistant2 = Assistant::factory()->create();

    $list = TaskList::factory()->forAssistant($assistant1->id)->create();
    $task1 = Task::factory()->create([
        'task_list_id' => $list->id,
        'assistant_id' => $assistant1->id,
    ]);
    $task2 = Task::factory()->create([
        'task_list_id' => $list->id,
        'assistant_id' => $assistant1->id,
    ]);

    Livewire::test(Index::class)
        ->call('openListModal', $list->id)
        ->set('listAssistantId', $assistant2->id)
        ->call('saveList');

    expect($task1->fresh()->assistant_id)->toBe($assistant2->id);
    expect($task2->fresh()->assistant_id)->toBe($assistant2->id);
});

it('clears task assistants when list assistant is removed', function () {
    $assistant = Assistant::factory()->create();

    $list = TaskList::factory()->forAssistant($assistant->id)->create();
    $task = Task::factory()->create([
        'task_list_id' => $list->id,
        'assistant_id' => $assistant->id,
    ]);

    Livewire::test(Index::class)
        ->call('openListModal', $list->id)
        ->set('listAssistantId', null)
        ->call('saveList');

    expect($task->fresh()->assistant_id)->toBeNull();
});

it('displays assistant badge on list card', function () {
    $assistant = Assistant::factory()->create(['name' => 'Min Assistent']);
    TaskList::factory()->forAssistant($assistant->id)->create(['name' => 'Tildelt liste']);

    Livewire::test(Index::class)
        ->assertSee('Min Assistent');
});
