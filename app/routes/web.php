<?php

use App\Http\Controllers\CompositionController;
use App\Http\Controllers\SimulatorController;
use App\Http\Controllers\TftDataController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CompositionController::class, 'index'])->name('compositions.index');
Route::get('/compositions/create', [CompositionController::class, 'create'])->name('compositions.create');
Route::post('/compositions', [CompositionController::class, 'store'])->name('compositions.store');
Route::get('/compositions/{composition}/edit', [CompositionController::class, 'edit'])->name('compositions.edit');
Route::put('/compositions/{composition}', [CompositionController::class, 'update'])->name('compositions.update');
Route::delete('/compositions/{composition}', [CompositionController::class, 'destroy'])->name('compositions.destroy');
Route::post('/compositions/{composition}/duplicate', [CompositionController::class, 'duplicate'])->name('compositions.duplicate');

Route::get('/simulator', [SimulatorController::class, 'index'])->name('simulator.index');

Route::get('/api/tft-data', [TftDataController::class, 'index'])->name('tft-data');
