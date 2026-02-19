<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTaskRequest;
use App\Http\Requests\Api\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\TaskList;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    /**
     * Store a newly created task.
     */
    public function store(StoreTaskRequest $request): TaskResource
    {
        $data = $request->validated();

        // Auto-inherit assistant_id from the task list if not explicitly set
        if (! isset($data['assistant_id'])) {
            $taskList = TaskList::find($data['task_list_id']);
            if ($taskList?->assistant_id) {
                $data['assistant_id'] = $taskList->assistant_id;
            }
        }

        $task = Task::create($data);

        return new TaskResource($task);
    }

    /**
     * Update the specified task.
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $task->update($request->validated());

        return new TaskResource($task->fresh());
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json(['message' => 'Task deleted'], 200);
    }
}
