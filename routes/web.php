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

    // Route::get('/category/create', [CategoryController::class, 'create'])->name('categories.create');
}); */
/* -------------------------------------------------------------*/

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
