<?php

// routes/web.php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request; // Asegúrate de importar Request si se usan closures que lo necesiten
use Inertia\Inertia; // Asegúrate de importar Inertia si se usan closures que lo necesiten

// Controladores de Administración
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CharacterController;
use App\Http\Controllers\Admin\SurveyController;
use App\Http\Controllers\Admin\UserController;

// Controladores Públicos
use App\Http\Controllers\PublicController; // Landing Page, About, etc.
use App\Http\Controllers\PublicCategoryController; // Listado y detalles de categorías públicas
use App\Http\Controllers\PublicCharacterController; // Listado y detalles de personajes públicos
use App\Http\Controllers\PublicSurveyController; // Listado, detalles y vistas de votación
use App\Http\Controllers\PublicStatisticsController; // Rankings y estadísticas públicas
use App\Http\Controllers\SurveyVoteController; // Procesamiento de votos (POST)

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Aquí es donde registras las rutas web para tu aplicación. Estas
| rutas son cargadas por el RouteServiceProvider y todas ellas serán
| asignadas al middleware "web". Algunas rutas pueden requerir
| autenticación o verificación de correo electrónico.
|
*/

// --------------------------------------------------------------
// RUTA PRINCIPAL (Pública)
// --------------------------------------------------------------

// La ruta raíz '/' ahora apunta al controlador PublicController@index
// que maneja la Landing Page del sitio público.
Route::get('/', [PublicController::class, 'index'])->name('home');

// --------------------------------------------------------------
// RUTA DASHBOARD (Privada - Requiere autenticación y verificación de email)
// --------------------------------------------------------------

// La ruta '/dashboard' requiere que el usuario esté autenticado
// y haya verificado su dirección de correo electrónico.
Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// --------------------------------------------------------------
// RUTAS PÚBLICAS (No requieren autenticación)
// --------------------------------------------------------------
// Agrupamos rutas que son accesibles por cualquier usuario.
Route::name('public.')->group(function () { // Prefijo para nombres de rutas públicas

    // --- Landing Page ---
    // Route::get('/', [PublicController::class, 'index'])->name('home'); // <-- Ya definida arriba

    // --- Listados Públicos ---
    Route::get('/categories', [PublicCategoryController::class, 'index'])->name('categories.index');
    Route::get('/surveys', [PublicSurveyController::class, 'index'])->name('surveys.index');

    // --- Vistas Detalladas Públicas (Información general, no votación) ---
    // Requieren autenticación para ver detalles completos o votar
    // Se mueven a la sección 'auth' si se requiere login para verlos.
    // Route::get('/categories/{category}', [PublicCategoryController::class, 'show'])->name('categories.show');
    // Route::get('/surveys/{survey}', [PublicSurveyController::class, 'show'])->name('surveys.show');
    // Route::get('/characters/{character}', [PublicCharacterController::class, 'show'])->name('characters.show');

    // --- Estadísticas Públicas (Información general, no votación) ---
    // Route::get('/statistics/categories/{category}/rankings', [PublicStatisticsController::class, 'categoryRankings'])->name('statistics.category.rankings');
    // Route::get('/surveys/{survey}/results', [PublicStatisticsController::class, 'surveyResults'])->name('surveys.results');
    // Route::get('/characters/{character}/stats', [PublicStatisticsController::class, 'characterStats'])->name('characters.stats');
});

// --------------------------------------------------------------
// RUTAS PROTEGIDAS (Requieren autenticación)
// --------------------------------------------------------------
// Agrupamos rutas que requieren que el usuario esté logueado.
Route::middleware(['auth'])->group(function () {

    // --- Rutas Públicas que Requieren Autenticación ---
    // Vista de detalle de una encuesta (requiere autenticación)
    Route::get('/surveys/{survey}', [PublicSurveyController::class, 'show'])->name('public.surveys.show');

    // Vista de detalle de una categoría (requiere autenticación)
    Route::get('/categories/{category}', [PublicCategoryController::class, 'show'])->name('public.categories.show');

    // Vista de ranking por categoría (requiere autenticación)
    Route::get('/statistics/categories/{category}/rankings', [PublicStatisticsController::class, 'categoryRankings'])->name('public.statistics.category.rankings');

    // Vista de resultados de una encuesta (requiere autenticación)
    Route::get('/surveys/{survey}/results', [PublicStatisticsController::class, 'surveyResults'])->name('public.surveys.results');

    // Vista de estadísticas públicas de un personaje (requiere autenticación)
    Route::get('/characters/{character}/stats', [PublicStatisticsController::class, 'characterStats'])->name('public.characters.stats');

    // --- Rutas de Votación (Requieren autenticación) ---
    // Vista para iniciar la votación en una encuesta (requiere autenticación) - utiliza axios
    Route::get('/surveys/{survey}/vote', [PublicSurveyController::class, 'vote'])->name('public.surveys.vote');

    // Endpoint AJAX para obtener la próxima combinación para votar (requiere autenticación)
    Route::get('/ajax/surveys/{survey}/combination', [PublicSurveyController::class, 'getCombination'])->name('public.ajax.surveys.combination');

    // Procesar un voto (requiere autenticación)
    Route::post('/surveys/{survey}/vote', [SurveyVoteController::class, 'store'])->name('public.surveys.vote.store');

    // --- Rutas AJAX (Requieren autenticación) ---
    // Agrupamos rutas AJAX que requieren autenticación
    Route::prefix('ajax')
         ->name('ajax.')
         ->group(function () {
             // Cargar personajes por categoría para formularios (Admin)
             Route::get('/categories/{category}/characters', [CharacterController::class, 'getAjaxCharactersByCategory'])->name('categories.characters');

             // Cargar información de un personaje para componentes como TCharacterDialogAjax (requiere autenticación)
             Route::get('/characters/{character}', [PublicCharacterController::class, 'getAjaxCharacterInfo'])->name('character.info');
         });

    // --- Rutas de Administración ---
    // Agrupamos todas las rutas de administración bajo el prefijo 'admin'
    Route::prefix('admin')
         ->name('admin.')
         ->group(function () {
             // CRUD para Categorías
             Route::resource('categories', CategoryController::class);

             // CRUD para Personajes
             Route::resource('characters', CharacterController::class);

             // CRUD para Encuestas
             Route::resource('surveys', SurveyController::class);

             // CRUD para Usuarios
             Route::resource('users', UserController::class);
             Route::patch('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
             Route::delete('users/{user}/force', [UserController::class, 'forceDelete'])->name('users.force-delete');
             Route::put('users/{user}/change-status', [UserController::class, 'changeStatus'])->name('users.change-status');
         });
});

// --------------------------------------------------------------
// RUTAS DE AUTENTICACIÓN (login, register, etc.)
// --------------------------------------------------------------
// Las rutas de autenticación (login, register, logout, etc.) se definen
// en un archivo separado para mantener la limpieza del archivo principal.
require __DIR__.'/auth.php';

// --------------------------------------------------------------
// RUTAS DE CONFIGURACIÓN/PERFIL (profile, password, etc.)
// --------------------------------------------------------------
// Opcional: Si usas rutas de perfil o configuración
// require __DIR__.'/settings.php'; // Descomenta si es necesario