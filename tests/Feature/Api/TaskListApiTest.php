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
