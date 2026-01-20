<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskList;

use function Pest\Laravel\getJson;
use function Pest\Laravel\withHeader;

beforeEach(function () {
    config(['app.api_key' => 'test-api-key-12345']);
});

// Authentication tests

it('returns 401 without api key', function () {
    getJson('/api/task-lists')
        ->assertUnauthorized()
        ->assertJson(['error' => 'Unauthorized']);
});

it('returns 401 with invalid api key', function () {
    withHeader('Authorization', 'Bearer wrong-key')
        ->getJson('/api/task-lists')
        ->assertUnauthorized();
});

it('returns 200 with valid api key', function () {
    TaskList::factory()->create();

    withHeader('Authorization', 'Bearer test-api-key-12345')
        ->getJson('/api/task-lists')
        ->assertOk();
});

it('returns 503 when api key is not configured', function () {
    config(['app.api_key' => null]);

    withHeader('Authorization', 'Bearer any-key')
        ->getJson('/api/task-lists')
        ->assertServiceUnavailable()
        ->assertJson(['error' => 'API not configured']);
});

// Task list endpoints

it('returns all task lists with counts', function () {
    $list1 = TaskList::factory()->create(['name' => 'Handletur', 'slug' => 'handletur', 'sort_order' => 0]);
    $list2 = TaskList::factory()->create(['name' => 'Ukesoppgaver', 'slug' => 'ukesoppgaver', 'sort_order' => 1]);

    Task::factory()->count(3)->create(['task_list_id' => $list1->id, 'status' => TaskStatus::Pending]);
    Task::factory()->count(2)->create(['task_list_id' => $list1->id, 'status' => TaskStatus::Completed]);
    Task::factory()->count(1)->create(['task_list_id' => $list2->id, 'status' => TaskStatus::Pending]);

    withHeader('Authorization', 'Bearer test-api-key-12345')
        ->getJson('/api/task-lists')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Handletur')
        ->assertJsonPath('data.0.task_count', 5)
        ->assertJsonPath('data.0.completed_count', 2)
        ->assertJsonPath('data.1.name', 'Ukesoppgaver')
        ->assertJsonPath('data.1.task_count', 1)
        ->assertJsonPath('data.1.completed_count', 0);
});

it('returns single task list with tasks by slug', function () {
    $list = TaskList::factory()->create(['name' => 'Handleliste', 'slug' => 'handleliste']);
    Task::factory()->create([
        'task_list_id' => $list->id,
        'title' => 'Kjøp melk',
        'status' => 'pending',
        'sort_order' => 1,
    ]);
    Task::factory()->create([
        'task_list_id' => $list->id,
        'title' => 'Kjøp brød',
        'status' => 'completed',
        'sort_order' => 0,
    ]);

    withHeader('Authorization', 'Bearer test-api-key-12345')
        ->getJson('/api/task-lists/handleliste')
        ->assertOk()
        ->assertJsonPath('data.name', 'Handleliste')
        ->assertJsonPath('data.slug', 'handleliste')
        ->assertJsonCount(2, 'data.tasks')
        ->assertJsonPath('data.tasks.0.title', 'Kjøp brød')
        ->assertJsonPath('data.tasks.1.title', 'Kjøp melk');
});

it('returns 404 for non-existent task list', function () {
    withHeader('Authorization', 'Bearer test-api-key-12345')
        ->getJson('/api/task-lists/does-not-exist')
        ->assertNotFound();
});

// Toggle task endpoint

it('toggles task status from pending to completed', function () {
    $task = Task::factory()->create(['status' => TaskStatus::Pending]);

    withHeader('Authorization', 'Bearer test-api-key-12345')
        ->patchJson("/api/tasks/{$task->id}/toggle")
        ->assertOk()
        ->assertJson([
            'id' => $task->id,
            'status' => 'completed',
        ]);

    expect($task->fresh()->status)->toBe(TaskStatus::Completed);
});

it('toggles task status from completed to pending', function () {
    $task = Task::factory()->create(['status' => TaskStatus::Completed]);

    withHeader('Authorization', 'Bearer test-api-key-12345')
        ->patchJson("/api/tasks/{$task->id}/toggle")
        ->assertOk()
        ->assertJson([
            'id' => $task->id,
            'status' => 'pending',
        ]);

    expect($task->fresh()->status)->toBe(TaskStatus::Pending);
});

it('returns 404 when toggling non-existent task', function () {
    withHeader('Authorization', 'Bearer test-api-key-12345')
        ->patchJson('/api/tasks/99999/toggle')
        ->assertNotFound();
});

// Create task endpoint

it('creates a new task', function () {
    $list = TaskList::factory()->create();

    withHeader('Authorization', 'Bearer test-api-key-12345')
        ->postJson('/api/tasks', [
            'task_list_id' => $list->id,
            'title' => 'Ny oppgave',
            'priority' => 'high',
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Ny oppgave')
        ->assertJsonPath('data.priority', 'high')
        ->assertJsonPath('data.status', 'pending');

    expect(Task::where('title', 'Ny oppgave')->exists())->toBeTrue();
});

it('validates required fields when creating task', function () {
    withHeader('Authorization', 'Bearer test-api-key-12345')
        ->postJson('/api/tasks', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['task_list_id', 'title']);
});

it('validates task_list_id exists when creating task', function () {
    withHeader('Authorization', 'Bearer test-api-key-12345')
        ->postJson('/api/tasks', [
            'task_list_id' => 99999,
            'title' => 'Test',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['task_list_id']);
});

// Update task endpoint

it('updates an existing task', function () {
    $task = Task::factory()->create(['title' => 'Gammel tittel', 'priority' => 'low']);

    withHeader('Authorization', 'Bearer test-api-key-12345')
        ->putJson("/api/tasks/{$task->id}", [
            'title' => 'Ny tittel',
            'priority' => 'high',
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Ny tittel')
        ->assertJsonPath('data.priority', 'high');

    expect($task->fresh()->title)->toBe('Ny tittel');
    expect($task->fresh()->priority->value)->toBe('high');
});

it('can update task status', function () {
    $task = Task::factory()->create(['status' => TaskStatus::Pending]);

    withHeader('Authorization', 'Bearer test-api-key-12345')
        ->putJson("/api/tasks/{$task->id}", [
            'status' => 'completed',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    expect($task->fresh()->status)->toBe(TaskStatus::Completed);
});

it('can move task to different list', function () {
    $list1 = TaskList::factory()->create();
    $list2 = TaskList::factory()->create();
    $task = Task::factory()->create(['task_list_id' => $list1->id]);

    withHeader('Authorization', 'Bearer test-api-key-12345')
        ->putJson("/api/tasks/{$task->id}", [
            'task_list_id' => $list2->id,
        ])
        ->assertOk();

    expect($task->fresh()->task_list_id)->toBe($list2->id);
});

it('returns 404 when updating non-existent task', function () {
    withHeader('Authorization', 'Bearer test-api-key-12345')
        ->putJson('/api/tasks/99999', ['title' => 'Test'])
        ->assertNotFound();
});

// Delete task endpoint

it('deletes a task', function () {
    $task = Task::factory()->create();
    $taskId = $task->id;

    withHeader('Authorization', 'Bearer test-api-key-12345')
        ->deleteJson("/api/tasks/{$taskId}")
        ->assertOk()
        ->assertJson(['message' => 'Task deleted']);

    expect(Task::find($taskId))->toBeNull();
});

it('returns 404 when deleting non-existent task', function () {
    withHeader('Authorization', 'Bearer test-api-key-12345')
        ->deleteJson('/api/tasks/99999')
        ->assertNotFound();
});
