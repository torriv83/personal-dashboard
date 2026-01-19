<?php

declare(strict_types=1);

use App\Http\Controllers\Api\TaskListController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.key')->group(function () {
    Route::get('task-lists', [TaskListController::class, 'index']);
    Route::get('task-lists/{taskList:slug}', [TaskListController::class, 'show']);
    Route::patch('tasks/{task}/toggle', [TaskListController::class, 'toggleTask']);
});
