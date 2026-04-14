<?php

use App\Http\Controllers\Admin\CompositionController as AdminCompositionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\CompositionController;
use App\Http\Controllers\SimulatorController;
use App\Http\Controllers\TftDataController;
use Illuminate\Support\Facades\Route;

// Public routes (no auth required)
Route::get('/', [CompositionController::class, 'index'])->name('compositions.index');
Route::get('/compositions/{composition}', [CompositionController::class, 'show'])->name('compositions.show');
Route::get('/simulator', [SimulatorController::class, 'index'])->name('simulator.index');
Route::get('/api/tft-data', [TftDataController::class, 'index'])->name('tft-data');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/my-compositions', [CompositionController::class, 'myIndex'])->name('compositions.my');
    Route::get('/compositions-create', [CompositionController::class, 'create'])->name('compositions.create');
    Route::post('/compositions', [CompositionController::class, 'store'])->name('compositions.store');
    Route::get('/compositions/{composition}/edit', [CompositionController::class, 'edit'])->name('compositions.edit');
    Route::put('/compositions/{composition}', [CompositionController::class, 'update'])->name('compositions.update');
    Route::delete('/compositions/{composition}', [CompositionController::class, 'destroy'])->name('compositions.destroy');
    Route::post('/compositions/{composition}/duplicate', [CompositionController::class, 'duplicate'])->name('compositions.duplicate');
    Route::post('/compositions/{composition}/import', [CompositionController::class, 'import'])->name('compositions.import');
});

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::put('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('admin.users.updateRole');
    Route::put('/compositions/{composition}/toggle-global', [AdminCompositionController::class, 'toggleGlobal'])->name('admin.compositions.toggleGlobal');
});

require __DIR__ . '/auth.php';
