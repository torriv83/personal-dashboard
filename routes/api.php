<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Bpa\CalendarDataController;
use App\Http\Controllers\Api\Bpa\ShiftController;
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

Route::middleware(['web', 'auth'])->prefix('bpa')->group(function () {
    // Calendar data (read-only)
    Route::get('calendar/shifts', [CalendarDataController::class, 'shifts']);
    Route::get('calendar/external-events', [CalendarDataController::class, 'externalEvents']);
    Route::get('calendar/assistants', [CalendarDataController::class, 'assistants']);
    Route::get('calendar/remaining-hours', [CalendarDataController::class, 'remainingHours']);
    Route::get('calendar/available-years', [CalendarDataController::class, 'availableYears']);
    Route::get('calendar/days', [CalendarDataController::class, 'calendarDays']);

    // Shift CRUD
    Route::post('shifts/quick-create', [ShiftController::class, 'quickCreate']);
    Route::post('shifts', [ShiftController::class, 'store']);
    Route::put('shifts/{shift}', [ShiftController::class, 'update']);
    Route::delete('shifts/{shift}', [ShiftController::class, 'destroy']);
    Route::post('shifts/{shift}/move', [ShiftController::class, 'move']);
    Route::post('shifts/{shift}/resize', [ShiftController::class, 'resize']);
    Route::post('shifts/{shift}/duplicate', [ShiftController::class, 'duplicate']);
});
