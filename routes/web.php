<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CharacterController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SurveyController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\PublicCategoryController;
use App\Http\Controllers\PublicSurveyController;
use App\Http\Controllers\SurveyVoteController;
use App\Http\Controllers\Api\SurveyController as ApiSurveyController;

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

/* -------------------------------------------------------------*/
// Agrupamos las rutas de la API pública de encuestas
Route::middleware(['auth']) // O 'auth' si usas sesión web para Inertia
    ->prefix('public')
    ->name('api.public.')
    ->group(function () {
        // Ruta para obtener la próxima combinación para votar
        Route::get('/surveys/{survey}/next-combination', [ApiSurveyController::class, 'getNextCombination'])
             ->name('surveys.next_combination');
    });
/* -------------------------------------------------------------*/
// Ruta para la landing page
Route::get('/landing', [PublicController::class, 'index'])->name('landing.index');

// Rutas para listados públicos (futuras)
Route::get('/categories', [PublicCategoryController::class, 'index'])->name('categories.public.index');
Route::get('/categories/{category}', [PublicCategoryController::class, 'show'])->name('categories.public.show');
Route::get('/surveys', [PublicSurveyController::class, 'index'])->name('surveys.public.index'); // Opcional: Ruta para listar encuestas públicas (si no se ha hecho)
// Ruta para mostrar una encuesta específica
Route::get('/surveys/{survey}', [PublicSurveyController::class, 'show'])->name('surveys.public.show');
/* -------------------------------------------------------------*/
// Ruta para votar - dentro del grupo auth
Route::middleware('auth')->group(function () {
    Route::post('/surveys/{survey}/vote', [SurveyVoteController::class, 'store'])->name('surveys.vote.store');

    // Ruta para ver una encuesta específica (pública o privada, según necesidad) - se requiere autenticación para ver los detalles de la encuesta:
    Route::get('/surveys/{survey}', [PublicSurveyController::class, 'show'])->name('surveys.public.show');
});

// Ruta para procesar un voto (requiere autenticación)
Route::middleware(['auth'])->post('/surveys/{survey}/vote', [SurveyVoteController::class, 'store'])->name('surveys.vote.store');

/* -------------------------------------------------------------*/

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
