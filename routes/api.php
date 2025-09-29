<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CharacterController;
use App\Http\Controllers\Admin\SurveyController;

/* Route::middleware('auth', 'verified')->group(function () {

    Route::resource('categories', CategoryController::class);
    Route::resource('characters', CharacterController::class);
    Route::resource('surveys', SurveyController::class);
});  */   

// Este grupo recibe el middleware 'api' definido internamente por Laravel
// cuando se especifica 'api: ...' en bootstrap/app.php.
// Para proteger las rutas de administración, aplicamos 'auth' o 'auth:sanctum' internamente.
// Asumiendo autenticación por sesión web (más común con Inertia)
Route::middleware(['auth']) // <-- Cambiar a 'auth' si se usa sesión web para Inertia
    ->prefix('admin') // Prefijo para las rutas de administración
    ->group(function () {
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('characters', CharacterController::class);
        Route::apiResource('surveys', SurveyController::class);
    });

// Otras rutas API públicas o con diferentes middleware pueden ir aquí fuera del grupo protegido.