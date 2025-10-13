<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CharacterController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SurveyController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

/* -------------------------------------------------------------*/
/* Route::middleware('auth', 'verified')->group(function () {

    Route::resource('categories', CategoryController::class);
    Route::resource('characters', CharacterController::class);
    Route::resource('surveys', SurveyController::class);
}); */

Route::middleware(['auth'])
    ->prefix('admin') // Prefijo para las rutas de administración
    ->name('admin.')   // Prefijo para los nombres de las rutas
    ->group(function () {
        /* Categorias */
        Route::resource('categories', CategoryController::class);
        /* Personajes */
        Route::resource('characters', CharacterController::class);
        /* Encuestas */
        Route::resource('surveys', SurveyController::class);        
        /* Usuarios */
        Route::resource('users', UserController::class);
        Route::patch('users/{user}/restore',  [UserController::class, 'restore'])->name('users.restore');
        Route::delete('users/{user}/force',   [UserController::class, 'forceDelete'])->name('users.force-delete');
        Route::put('users/{user}/change-status', [UserController::class, 'changeStatus'])->name('users.change-status');
        });
        // Ajax Routes para cargar personajes por categoría
        Route::get('/ajax/categories/{category}/characters', [CharacterController::class, 'getCharactersByCategoryAjax'])->name('ajax.categories.characters');
        // Ruta para votacion
        Route::get('/surveys/{survey}/vote', [SurveyController::class, 'vote'])->name('admin.surveys.vote');
/* -------------------------------------------------------------*/
Route::get('prueba', function () {
    return 'prueba';
})->name('prueba');
/* -------------------------------------------------------------*/

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
