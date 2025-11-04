<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\Auth\AsanaController;
use App\Http\Controllers\AsanaInfoController;
use App\Http\Controllers\TasksPageController;
use App\Http\Controllers\AsanaUsersController; 

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// landing page
Route::get('/', [IndexController::class, 'landing_page'])->name('landing');

// asana login
Route::get('/asana', [AsanaController::class, 'redirectToProvider'])->name('login.asana');
Route::get('/asana/callback', [AsanaController::class, 'handleProviderCallback'])->name('asana.callback');

// logout
Route::post('/logout', [AsanaController::class, 'logout'])->middleware('auth')->name('logout');

// auth
Route::middleware(['auth'])->group(function () {

    // CEA
    Route::get('/dashboard', [AsanaInfoController::class, 'dashboard'])->name('dashboard');

    // user routes
    Route::get('/users/asana', [AsanaUsersController::class, 'index'])->name('asana.users.index');
    Route::get('/api/asana/user-info', [AsanaUsersController::class, 'getUserInfo'])->name('asana.users.info');


    // asana routes
    Route::prefix('asana')->group(function () {

        Route::get('/tasks/{gid}', [AsanaInfoController::class, 'showTask'])->name('asana.tasks.show');
        Route::get('/tasks', [AsanaInfoController::class, 'index'])->name('asana.tasks.index');
        Route::post('/tasks/{gid}/move', [AsanaInfoController::class, 'moveTaskToSection'])->name('asana.tasks.move');
        Route::get('/load-project/{projectId}', [AsanaInfoController::class, 'loadProject'])->name('asana.load.project');
    });
    Route::get('/tasks', [TasksPageController::class, 'index'])->name('tasks.page');
});