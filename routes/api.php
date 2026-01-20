<?php

declare(strict_types=1);

use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskListController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.key')->group(function () {
    // Task Lists
    Route::get('task-lists', [TaskListController::class, 'index']);
    Route::get('task-lists/{taskList:slug}', [TaskListController::class, 'show']);

    // Tasks
    Route::post('tasks', [TaskController::class, 'store']);
    Route::put('tasks/{task}', [TaskController::class, 'update']);
    Route::delete('tasks/{task}', [TaskController::class, 'destroy']);
    Route::patch('tasks/{task}/toggle', [TaskListController::class, 'toggleTask']);
});
