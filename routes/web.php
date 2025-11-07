<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request; // Asegúrate de importar Request si se usan closures que lo necesiten
use Inertia\Inertia; // Asegúrate de importar Inertia si se usan closures que lo necesiten
use App\Http\Controllers\PublicController; // Controlador para la landing page principal
use App\Http\Controllers\PublicCategoryController;
use App\Http\Controllers\PublicSurveyController;
use App\Http\Controllers\SurveyVoteController; // Controlador para procesar votos
use App\Http\Controllers\Api\SurveyController as ApiPublicSurveyController; // Controlador para API pública
// Rutas de administración
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CharacterController;
use App\Http\Controllers\Admin\SurveyController; // Asumiendo que SurveyController es para admin
use App\Http\Controllers\Admin\UserController;

// Ruta de inicio (pública)
Route::get('/', [PublicController::class, 'index'])->name('home'); // O podrías mantener el closure si es muy simple

// Ruta de dashboard (requiere autenticación y verificación de email)
Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// --------------------------------------------------------------
// RUTAS PÚBLICAS (sin autenticación)
// --------------------------------------------------------------
Route::name('public.')->group(function () {
    // Landing Page (pública)
    Route::get('/landing', [PublicController::class, 'index'])->name('landing.index'); // Asumiendo que PublicController@index es la landing

    // Listados Públicos (categorías y encuestas)
    Route::get('/categories', [PublicCategoryController::class, 'index'])->name('categories.index');
    Route::get('/surveys', [PublicSurveyController::class, 'index'])->name('surveys.index');

    // Vista de detalle de una categoría (pública)
    Route::get('/categories/{category}', [PublicCategoryController::class, 'show'])->name('categories.show');

    // Vista de detalle de una encuesta (pública, pero se requiere autenticación para verla completamente o votar)
    // Esta ruta se moverá al grupo 'auth' si requiere login para verla.
    // Route::get('/surveys/{survey}', [PublicSurveyController::class, 'show'])->name('surveys.show');
});

// --------------------------------------------------------------
// RUTAS PÚBLICAS (requieren autenticación)
// --------------------------------------------------------------
Route::middleware(['auth'])->group(function () {
    Route::name('public.')->group(function () {
        // Vista de detalle de una encuesta (requiere autenticación)
        Route::get('/surveys/{survey}', [PublicSurveyController::class, 'show'])->name('surveys.show');

        // Vista para iniciar la votación en una encuesta (requiere autenticación)
        Route::get('/surveys/{survey}/vote', [PublicSurveyController::class, 'vote'])->name('surveys.vote');

        // Procesar un voto (requiere autenticación)
        Route::post('/surveys/{survey}/vote', [SurveyVoteController::class, 'store'])->name('surveys.vote.store');
    });

    // --------------------------------------------------------------
    // RUTAS API PÚBLICAS (requieren autenticación)
    // --------------------------------------------------------------
    // Agrupamos las rutas de la API pública de encuestas
    Route::prefix('api/public')->name('api.public.')->group(function () {
        // Ruta para obtener la próxima combinación para votar
        Route::get('/surveys/{survey}/next-combination', [ApiPublicSurveyController::class, 'getNextCombination'])
             ->name('surveys.next_combination');
    });
});

// --------------------------------------------------------------
// RUTAS DE ADMINISTRACIÓN (requieren autenticación)
// --------------------------------------------------------------
Route::middleware(['auth'])
    ->prefix('admin') // Prefijo para las rutas de administración
    ->name('admin.')   // Prefijo para los nombres de las rutas
    ->group(function () {
        // Categorías
        Route::resource('categories', CategoryController::class);

        // Personajes
        Route::resource('characters', CharacterController::class);

        // Encuestas (Admin CRUD)
        Route::resource('surveys', SurveyController::class);

        // Usuarios (Admin CRUD)
        Route::resource('users', UserController::class);
        Route::patch('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
        Route::delete('users/{user}/force', [UserController::class, 'forceDelete'])->name('users.force-delete');
        Route::put('users/{user}/change-status', [UserController::class, 'changeStatus'])->name('users.change-status');
    });

// --------------------------------------------------------------
// RUTAS AJAX (requieren autenticación)
// --------------------------------------------------------------
// Ejemplo: Cargar personajes por categoría para formularios
Route::middleware(['auth'])->group(function () {
    Route::get('/ajax/categories/{category}/characters', [CharacterController::class, 'getCharactersByCategoryAjax'])->name('ajax.categories.characters');
    // Puedes añadir otras rutas AJAX aquí si es necesario
});

// --------------------------------------------------------------
// RUTAS DE AUTENTICACIÓN (login, register, etc.)
// --------------------------------------------------------------
require __DIR__.'/auth.php';

// --------------------------------------------------------------
// OTROS REQUIRES (settings, etc.)
// --------------------------------------------------------------
// require __DIR__.'/settings.php'; // Descomenta si es necesario