<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\CategoryController;

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

Route::middleware(['auth']) // <-- Cambiar a 'auth' si se usa sesión web para Inertia
    ->prefix('admin') // Prefijo para las rutas de administración
    ->group(function () {
        Route::resource('categories', CategoryController::class);
        Route::resource('characters', CharacterController::class);
        Route::resource('surveys', SurveyController::class);
    });
/* -------------------------------------------------------------*/
Route::get('prueba', function () {
    return 'prueba';
})->name('prueba');
/* -------------------------------------------------------------*/

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
