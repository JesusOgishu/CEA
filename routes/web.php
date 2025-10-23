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

// 🔹 Página principal (landing)
Route::get('/', [IndexController::class, 'landing_page'])->name('landing');

// 🔹 Login con Asana
Route::get('/asana', [AsanaController::class, 'redirectToProvider'])->name('login.asana');
Route::get('/asana/callback', [AsanaController::class, 'handleProviderCallback'])->name('asana.callback');

// 🔹 Logout
Route::post('/logout', [AsanaController::class, 'logout'])->middleware('auth')->name('logout');

// ========================================================
// 🔐 Rutas protegidas por autenticación
// ========================================================
Route::middleware(['auth'])->group(function () {

    // 🧭 Dashboard principal con cuadrantes
    Route::get('/dashboard', [AsanaInfoController::class, 'dashboard'])->name('dashboard');

    // ========================================================
    // 👤 Rutas de Usuario Asana
    // ========================================================
    Route::get('/users/asana', [AsanaUsersController::class, 'index'])->name('asana.users.index');
    Route::get('/api/asana/user-info', [AsanaUsersController::class, 'getUserInfo'])->name('asana.users.info');


    // ========================================================
    // 🧩 Rutas específicas de Asana
    // ========================================================
    Route::prefix('asana')->group(function () {

        // 🔸 Mostrar una tarea específica (si lo usas)
        Route::get('/tasks/{gid}', [AsanaInfoController::class, 'showTask'])->name('asana.tasks.show');

        // 🔸 Listar tareas generales (opcional)
        Route::get('/tasks', [AsanaInfoController::class, 'index'])->name('asana.tasks.index');

        // 🔸 Mover tareas entre secciones (AJAX)
        Route::post('/tasks/{gid}/move', [AsanaInfoController::class, 'moveTaskToSection'])->name('asana.tasks.move');

        // 🔸 Cargar dinámicamente un proyecto completo (AJAX) - ⚠️ Revisar si aún es necesaria
        Route::get('/load-project/{projectId}', [AsanaInfoController::class, 'loadProject'])->name('asana.load.project');
    });

    // ========================================================
    // 📋 Página alternativa de listado de tareas (si la usas)
    // ========================================================
    Route::get('/tasks', [TasksPageController::class, 'index'])->name('tasks.page');
});