<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DryingHistoryController;
use App\Http\Controllers\ThresholdSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Application Routes — Helmet Care Dehumidifier Station
|--------------------------------------------------------------------------
|
| Modern MVC web routes for the 4 primary screens:
| 1. Dashboard Station (`/`)
| 2. Riwayat Pengeringan (`/riwayat`)
| 3. Perangkat (`/perangkat`)
| 4. Pengaturan Ambang (`/pengaturan`)
|
*/

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/dashboard/toggle-fan', [DashboardController::class, 'toggleFan'])->name('dashboard.toggle-fan');

Route::prefix('riwayat')->name('history.')->group(function (): void {
    Route::get('/', [DryingHistoryController::class, 'index'])->name('index');
    Route::get('/export', [DryingHistoryController::class, 'exportCsv'])->name('export');
});

Route::prefix('perangkat')->name('devices.')->group(function (): void {
    Route::get('/', [DeviceController::class, 'index'])->name('index');
    Route::post('/simulate', [DeviceController::class, 'simulate'])->name('simulate');
});

Route::prefix('pengaturan')->name('settings.')->group(function (): void {
    Route::get('/', [ThresholdSettingController::class, 'index'])->name('index');
    Route::put('/', [ThresholdSettingController::class, 'update'])->name('update');
    Route::post('/reset', [ThresholdSettingController::class, 'reset'])->name('reset');
});
