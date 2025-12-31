<?php

declare(strict_types=1);

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
        ->call('addTaskFromAlpine', 'Ny oppgave', 'high', null);

    expect(Task::where('title', 'Ny oppgave')->where('task_list_id', $this->taskList->id)->exists())->toBeTrue();
    expect(Task::where('title', 'Ny oppgave')->first()->priority)->toBe(TaskPriority::High);
});

it('ignores empty title when adding task', function () {
    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->call('addTaskFromAlpine', '', 'low', null);

    expect(Task::where('task_list_id', $this->taskList->id)->count())->toBe(0);
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

it('can update task title inline', function () {
    $task = Task::factory()->create([
        'task_list_id' => $this->taskList->id,
        'title' => 'Gammel tittel',
    ]);

    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->call('updateTaskTitle', $task->id, 'Ny tittel');

    expect($task->fresh()->title)->toBe('Ny tittel');
});

it('can cycle task priority', function () {
    $task = Task::factory()->create([
        'task_list_id' => $this->taskList->id,
        'priority' => TaskPriority::Low,
    ]);

    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->call('cycleTaskPriority', $task->id);

    expect($task->fresh()->priority)->toBe(TaskPriority::Medium);

    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->call('cycleTaskPriority', $task->id);

    expect($task->fresh()->priority)->toBe(TaskPriority::High);
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
        ->call('addTaskFromAlpine', 'Oppgave med assistent', 'low', $assistant->id);

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
    $task1 = Task::factory()->pending()->create([
        'task_list_id' => $this->taskList->id,
        'title' => 'Oppgave 1',
        'sort_order' => 0,
    ]);
    $task2 = Task::factory()->pending()->create([
        'task_list_id' => $this->taskList->id,
        'title' => 'Oppgave 2',
        'sort_order' => 1,
    ]);
    $task3 = Task::factory()->pending()->create([
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

it('ignores empty title when updating task', function () {
    $task = Task::factory()->create([
        'task_list_id' => $this->taskList->id,
        'title' => 'Original tittel',
    ]);

    Livewire::test(Show::class, ['taskList' => $this->taskList])
        ->call('updateTaskTitle', $task->id, '');

    expect($task->fresh()->title)->toBe('Original tittel');
});

it('auto-assigns list assistant to new tasks', function () {
    $assistant = Assistant::factory()->create();
    $listWithAssistant = TaskList::factory()->forAssistant($assistant->id)->create();

    Livewire::test(Show::class, ['taskList' => $listWithAssistant])
        ->call('addTaskFromAlpine', 'Ny oppgave', 'medium', null);

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
