<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Livewire\Tasks\Show;
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
    $this->taskList = TaskList::factory()->create(['name' => 'Test Liste']);
});

it('renders the tasks show page', function () {
    get(route('bpa.tasks.show', $this->taskList))
        ->assertSeeLivewire(Show::class);
});

it('displays the task list name', function () {
    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->assertSee('Test Liste');
});

it('displays tasks belonging to the list', function () {
    Task::factory()->create([
        'task_list_id' => $this->taskList->id,
        'title' => 'Min første oppgave',
        'status' => TaskStatus::Pending,
    ]);

    Task::factory()->create([
        'task_list_id' => $this->taskList->id,
        'title' => 'Min andre oppgave',
        'status' => TaskStatus::Completed,
    ]);

    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->assertSee('Min første oppgave')
        ->assertSee('Min andre oppgave');
});

it('shows empty state when no tasks', function () {
    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->assertSee('Ingen oppgaver ennå');
});

it('can add a new task', function () {
    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->set('newTaskTitle', 'Ny oppgave')
        ->set('newTaskPriority', 'high')
        ->call('addTask');

    expect(Task::where('title', 'Ny oppgave')->where('task_list_id', $this->taskList->id)->exists())->toBeTrue();
    expect(Task::where('title', 'Ny oppgave')->first()->priority)->toBe(TaskPriority::High);
});

it('validates required title when adding task', function () {
    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->set('newTaskTitle', '')
        ->call('addTask')
        ->assertHasErrors(['newTaskTitle' => 'required']);
});

it('can toggle task status from pending to completed', function () {
    $task = Task::factory()->create([
        'task_list_id' => $this->taskList->id,
        'status' => TaskStatus::Pending,
    ]);

    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->call('toggleTaskStatus', $task->id);

    expect($task->fresh()->status)->toBe(TaskStatus::Completed);
});

it('can toggle task status from completed to pending', function () {
    $task = Task::factory()->create([
        'task_list_id' => $this->taskList->id,
        'status' => TaskStatus::Completed,
    ]);

    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->call('toggleTaskStatus', $task->id);

    expect($task->fresh()->status)->toBe(TaskStatus::Pending);
});

it('cannot toggle status for task from another list', function () {
    $otherList = TaskList::factory()->create();
    $task = Task::factory()->create([
        'task_list_id' => $otherList->id,
        'status' => TaskStatus::Pending,
    ]);

    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->call('toggleTaskStatus', $task->id);

    // Status should remain unchanged
    expect($task->fresh()->status)->toBe(TaskStatus::Pending);
});

it('can open edit modal for a task', function () {
    $task = Task::factory()->create([
        'task_list_id' => $this->taskList->id,
        'title' => 'Oppgave å redigere',
        'priority' => TaskPriority::Medium,
    ]);

    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->call('openEditModal', $task->id)
        ->assertSet('showEditModal', true)
        ->assertSet('editingTaskId', $task->id)
        ->assertSet('editTaskTitle', 'Oppgave å redigere')
        ->assertSet('editTaskPriority', 'medium');
});

it('can save edited task', function () {
    $task = Task::factory()->create([
        'task_list_id' => $this->taskList->id,
        'title' => 'Gammel tittel',
        'priority' => TaskPriority::Low,
    ]);

    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->call('openEditModal', $task->id)
        ->set('editTaskTitle', 'Ny tittel')
        ->set('editTaskPriority', 'high')
        ->call('saveTask');

    $task->refresh();
    expect($task->title)->toBe('Ny tittel');
    expect($task->priority)->toBe(TaskPriority::High);
});

it('can delete a task', function () {
    $task = Task::factory()->create([
        'task_list_id' => $this->taskList->id,
    ]);

    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->call('deleteTask', $task->id);

    expect(Task::find($task->id))->toBeNull();
});

it('cannot delete task from another list', function () {
    $otherList = TaskList::factory()->create();
    $task = Task::factory()->create([
        'task_list_id' => $otherList->id,
    ]);

    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->call('deleteTask', $task->id);

    // Task should still exist
    expect(Task::find($task->id))->not->toBeNull();
});

it('can assign task to assistant', function () {
    $assistant = Assistant::factory()->create(['name' => 'Test Assistent']);

    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->set('newTaskTitle', 'Oppgave med assistent')
        ->set('newTaskAssistantId', $assistant->id)
        ->call('addTask');

    $task = Task::where('title', 'Oppgave med assistent')->first();
    expect($task->assistant_id)->toBe($assistant->id);
});

it('displays assistant badge when task is assigned', function () {
    $assistant = Assistant::factory()->create(['name' => 'Ola Nordmann']);
    Task::factory()->create([
        'task_list_id' => $this->taskList->id,
        'title' => 'Tildelt oppgave',
        'assistant_id' => $assistant->id,
    ]);

    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->assertSee('Ola Nordmann');
});

it('displays priority badge', function () {
    Task::factory()->create([
        'task_list_id' => $this->taskList->id,
        'title' => 'Høy prioritet oppgave',
        'priority' => TaskPriority::High,
    ]);

    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->assertSee('Høy');
});

it('shows task count in header', function () {
    Task::factory()->count(3)->create([
        'task_list_id' => $this->taskList->id,
        'status' => TaskStatus::Pending,
    ]);
    Task::factory()->count(2)->create([
        'task_list_id' => $this->taskList->id,
        'status' => TaskStatus::Completed,
    ]);

    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->assertSee('3 av 5 oppgaver gjenstår');
});

it('can reorder tasks', function () {
    $task1 = Task::factory()->create([
        'task_list_id' => $this->taskList->id,
        'title' => 'Oppgave 1',
        'sort_order' => 0,
    ]);
    $task2 = Task::factory()->create([
        'task_list_id' => $this->taskList->id,
        'title' => 'Oppgave 2',
        'sort_order' => 1,
    ]);
    $task3 = Task::factory()->create([
        'task_list_id' => $this->taskList->id,
        'title' => 'Oppgave 3',
        'sort_order' => 2,
    ]);

    // Move task3 to position 0
    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->call('updateOrder', "task-{$task3->id}", 0);

    expect($task3->fresh()->sort_order)->toBe(0);
    expect($task1->fresh()->sort_order)->toBe(1);
    expect($task2->fresh()->sort_order)->toBe(2);
});

it('closes edit modal and resets form', function () {
    $task = Task::factory()->create([
        'task_list_id' => $this->taskList->id,
    ]);

    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->call('openEditModal', $task->id)
        ->assertSet('showEditModal', true)
        ->call('closeEditModal')
        ->assertSet('showEditModal', false)
        ->assertSet('editingTaskId', null)
        ->assertSet('editTaskTitle', '');
});

it('auto-assigns list assistant to new tasks', function () {
    $assistant = Assistant::factory()->create();
    $listWithAssistant = TaskList::factory()->forAssistant($assistant->id)->create();

    Livewire::test(Show::class, ['taskList' => $listWithAssistant])
        ->set('newTaskTitle', 'Ny oppgave')
        ->set('newTaskPriority', 'medium')
        ->set('newTaskAssistantId', null) // Try to set null
        ->call('addTask');

    $task = Task::where('title', 'Ny oppgave')->first();
    expect($task->assistant_id)->toBe($assistant->id);
});

it('reports listHasAssistant as true when list has assistant', function () {
    $assistant = Assistant::factory()->create();
    $listWithAssistant = TaskList::factory()->forAssistant($assistant->id)->create();

    Livewire::test(Show::class, ['taskList' => $listWithAssistant])
        ->assertSet('listHasAssistant', true);
});

it('reports listHasAssistant as false when list has no assistant', function () {
    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->assertSet('listHasAssistant', false);
});
