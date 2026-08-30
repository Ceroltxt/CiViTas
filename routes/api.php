<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\Identity\AuthController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\NavigationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TeamController;
use Illuminate\Support\Facades\Route;

Route::get('/status', StatusController::class)->name('status');

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/register', [AuthController::class, 'register'])->name('register');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [MeController::class, 'show'])->name('me.summary');
    Route::get('/me/current-project', [MeController::class, 'currentProject'])->name('me.current-project');

    Route::get('/navigation', [NavigationController::class, 'index'])->name('navigation');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/metrics', [DashboardController::class, 'metrics'])->name('metrics');
        Route::get('/agenda', [DashboardController::class, 'agenda'])->name('agenda');
        Route::get('/ranking', [DashboardController::class, 'ranking'])->name('ranking');
        Route::get('/projects', [DashboardController::class, 'projects'])->name('projects');
    });

    Route::get('/teams', [TeamController::class, 'index'])->name('teams');
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.update-status');

    Route::prefix('board')->name('board.')->group(function () {
        Route::get('/tasks', [BoardController::class, 'tasks'])->name('tasks');
        Route::get('/timeline', [BoardController::class, 'timeline'])->name('timeline');
    });

    Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs');
});
