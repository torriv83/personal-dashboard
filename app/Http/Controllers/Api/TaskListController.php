<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskListResource;
use App\Models\Task;
use App\Models\TaskList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskListController extends Controller
{
    /**
     * Get all task lists with counts.
     */
    public function index(): AnonymousResourceCollection
    {
        $lists = TaskList::query()
            ->with('tasks')
            ->orderBy('sort_order')
            ->get();

        return TaskListResource::collection($lists);
    }

    /**
     * Get a single task list with all tasks.
     */
    public function show(TaskList $taskList): TaskListResource
    {
        $taskList->load(['tasks' => fn ($query) => $query->orderBy('sort_order')]);

        return new TaskListResource($taskList);
    }

    /**
     * Toggle a task's status between pending and completed.
     */
    public function toggleTask(Task $task): JsonResponse
    {
        $task->status = $task->status === TaskStatus::Completed
            ? TaskStatus::Pending
            : TaskStatus::Completed;
        $task->save();

        return response()->json([
            'id' => $task->id,
            'status' => $task->status->value,
        ]);
    }
}
