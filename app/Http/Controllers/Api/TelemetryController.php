<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelemetryIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TelemetryController
 *
 * Provides REST/JSON endpoints for:
 * 1. ESP32 or MQTT Bridge telemetry ingestion (`POST /api/iot/telemetry`)
 * 2. Real-time browser dashboard polling every N seconds (`GET /api/iot/state`)
 * 3. ESP32 startup threshold configuration sync (`GET /api/iot/config`)
 */
class TelemetryController extends Controller
{
    public function __construct(
        private readonly TelemetryIngestionService $telemetryService
    ) {}

    /**
     * Ingest a sensor reading payload from an ESP32 station or MQTT webhook bridge.
     */
    public function store(Request $request): JsonResponse
    {
        $configuredToken = config('mqtt.api_token');
        if (is_string($configuredToken) && $configuredToken !== '') {
            $providedToken = $request->header('X-IoT-Token') ?? $request->input('token');
            if ($providedToken !== $configuredToken) {
                return response()->json(['message' => 'Unauthorized IoT token.'], 401);
            }
        }

        $validated = $request->validate([
            'station_code' => ['nullable', 'string', 'max:64'],
            'humidity' => ['required', 'numeric', 'min:0', 'max:100'],
            'temperature' => ['required', 'numeric', 'min:-20', 'max:85'],
            'distance_cm' => ['required', 'numeric', 'min:0', 'max:400'],
            'wifi_ssid' => ['nullable', 'string', 'max:100'],
            'wifi_rssi' => ['nullable', 'integer', 'min:-120', 'max:0'],
            'firmware_version' => ['nullable', 'string', 'max:50'],
        ]);

        $station = $this->telemetryService->ingest($validated);

        return response()->json([
            'status' => 'ok',
            'station' => [
                'code' => $station->code,
                'name' => $station->name,
                'current_humidity' => $station->current_humidity,
                'current_temperature' => $station->current_temperature,
                'current_distance_cm' => $station->current_distance_cm,
                'helmet_detected' => $station->helmet_detected,
                'fan_is_on' => $station->fan_is_on,
                'led_status' => $station->led_status,
                'led_label' => $station->led_label,
                'drying_state' => $station->drying_state,
                'drying_state_label' => $station->drying_state_label,
            ],
            'commands' => [
                'relay_fan' => $station->fan_is_on ? 'ON' : 'OFF',
                'led_mode' => $station->led_status,
                'interval_seconds' => $station->data_interval_seconds,
            ],
        ]);
    }

    /**
     * Return the live station state and recent chart points for the Dashboard 2-second poller.
     */
    public function state(Request $request): JsonResponse
    {
        $station = $this->telemetryService->getOrSeedDefaultStation(
            $request->query('station')
        );

        $activeCycle = $station->activeCycle()->first();
        $recentReadings = $station->sensorReadings()
            ->orderByDesc('recorded_at')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'station' => [
                'code' => $station->code,
                'name' => $station->name,
                'status' => $station->status,
                'drying_state' => $station->drying_state,
                'drying_state_label' => $station->drying_state_label,
                'current_humidity' => round($station->current_humidity, 0),
                'current_temperature' => number_format($station->current_temperature, 1, ',', '.'),
                'current_distance_cm' => round($station->current_distance_cm, 0),
                'helmet_detected' => $station->helmet_detected,
                'fan_is_on' => $station->fan_is_on,
                'led_status' => $station->led_status,
                'led_label' => $station->led_label,
                'fan_on_humidity' => $station->fan_on_humidity,
                'fan_off_humidity' => $station->fan_off_humidity,
                'detection_distance_cm' => $station->detection_distance_cm,
                'data_interval_seconds' => $station->data_interval_seconds,
                'auto_mode' => $station->auto_mode,
                'progress_percent' => $station->calculateDryingProgress($activeCycle),
                'last_seen_human' => $station->last_seen_at ? $station->last_seen_at->diffForHumans() : '2 detik lalu',
            ],
            'active_cycle' => $activeCycle ? [
                'started_at' => $activeCycle->start_time,
                'duration_minutes' => $activeCycle->duration_minutes,
                'initial_humidity' => round($activeCycle->initial_humidity, 0),
                'mode' => $activeCycle->mode,
                'status_label' => $activeCycle->status_label,
            ] : null,
            'readings' => $recentReadings->map(fn ($reading) => [
                'time' => $reading->recorded_at->format('H:i'),
                'humidity' => round((float) $reading->humidity, 1),
                'temperature' => round((float) $reading->temperature, 1),
            ]),
        ]);
    }

    /**
     * Return threshold settings for ESP32 boot/sync requests.
     */
    public function config(Request $request): JsonResponse
    {
        $station = $this->telemetryService->getOrSeedDefaultStation(
            $request->query('station')
        );

        return response()->json([
            'station_code' => $station->code,
            'fan_on_humidity' => $station->fan_on_humidity,
            'fan_off_humidity' => $station->fan_off_humidity,
            'detection_distance_cm' => $station->detection_distance_cm,
            'data_interval_seconds' => $station->data_interval_seconds,
            'auto_mode' => $station->auto_mode,
            'notify_on_complete' => $station->notify_on_complete,
        ]);
    }
}
