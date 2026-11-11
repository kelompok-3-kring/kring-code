<?php

declare(strict_types=1);

use App\Http\Controllers\Api\TelemetryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| IoT & MQTT Bridge API Routes (`/api/iot/*`)
|--------------------------------------------------------------------------
|
| Stateless JSON endpoints used by ESP32 stations, MQTT webhook bridges,
| and the real-time web dashboard poller.
|
*/

Route::prefix('iot')->name('api.iot.')->group(function (): void {
    Route::post('/telemetry', [TelemetryController::class, 'store'])->name('telemetry.store');
    Route::get('/state', [TelemetryController::class, 'state'])->name('state');
    Route::get('/config', [TelemetryController::class, 'config'])->name('config');
});
