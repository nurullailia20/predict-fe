<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ForecastController;

Route::get('/', [DashboardController::class, 'index']);
Route::get('/prediksi', [ForecastController::class, 'index'])->name('perbandingan.prediksi');