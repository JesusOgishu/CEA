<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\Auth\AsanaController;
use App\Http\Controllers\AsanaInfoController;
use App\Http\Controllers\TasksPageController;
use App\Http\Controllers\AsanaUsersController;
use App\Http\Controllers\MetricsController;

Route::get('/', [IndexController::class, 'landing_page'])->name('landing');

Route::get('/asana', [AsanaController::class, 'redirectToProvider'])->name('login.asana');
Route::get('/asana/callback', [AsanaController::class, 'handleProviderCallback'])->name('asana.callback');

Route::post('/logout', [AsanaController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [AsanaInfoController::class, 'dashboard'])->name('dashboard');

    Route::get('/users/asana', [AsanaUsersController::class, 'index'])->name('asana.users.index');
    Route::get('/api/asana/user-info', [AsanaUsersController::class, 'getUserInfo'])->name('asana.users.info');

    Route::prefix('asana')->group(function () {
        Route::get('/tasks/{gid}', [AsanaInfoController::class, 'showTask'])->name('asana.tasks.show');
        Route::get('/tasks', [AsanaInfoController::class, 'index'])->name('asana.tasks.index');
        Route::post('/tasks/{gid}/move', [AsanaInfoController::class, 'moveTaskToSection'])->name('asana.tasks.move');
        Route::get('/load-project/{projectId}', [AsanaInfoController::class, 'loadProject'])->name('asana.load.project');
    });

    Route::get('/tasks', [TasksPageController::class, 'index'])->name('tasks.page');

    // Metrics Dashboard
    Route::get('/metrics', [MetricsController::class, 'index'])->name('metrics.page');

    // Metrics API Endpoints
    Route::prefix('metrics/api')->group(function () {
        Route::get('/overview', [MetricsController::class, 'apiOverview'])->name('metrics.api.overview');
        Route::get('/tasks-completed', [MetricsController::class, 'apiTasksCompleted'])->name('metrics.api.tasks_completed');
        Route::get('/tasks-by-project', [MetricsController::class, 'apiTasksByProject'])->name('metrics.api.tasks_by_project');
        Route::get('/top-assignees', [MetricsController::class, 'apiTopAssignees'])->name('metrics.api.top_assignees');
        Route::get('/overdue', [MetricsController::class, 'apiOverdue'])->name('metrics.api.overdue');
    });
});
