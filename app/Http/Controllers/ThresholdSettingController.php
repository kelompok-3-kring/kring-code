<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DeviceActivityLog;
use App\Models\Station;
use App\Services\MqttBrokerService;
use App\Services\TelemetryIngestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ThresholdSettingController
 *
 * Handles the "Pengaturan Ambang" page (`/pengaturan`), allowing users to:
 * - Configure upper RH threshold (`fan_on_humidity`, default 65%)
 * - Configure lower RH threshold (`fan_off_humidity`, default 55%)
 * - Configure ultrasonic helmet detection distance (`detection_distance_cm`, default 15 cm)
 * - Configure ESP32 telemetry transmission interval (`data_interval_seconds`, default 2 s)
 * - Configure operating mode (`auto_mode` & `notify_on_complete`)
 * - Reset settings to factory defaults (`Kembalikan Default`)
 * - Publish updated thresholds to the ESP32 via MQTT
 */
class ThresholdSettingController extends Controller
{
    public function __construct(
        private readonly TelemetryIngestionService $telemetryService,
        private readonly MqttBrokerService $mqttBroker
    ) {}

    /**
     * Display the Threshold Settings form and LED/Mode reference panel.
     */
    public function index(): View
    {
        $station = $this->telemetryService->getOrSeedDefaultStation();

        return view('settings.index', [
            'station' => $station,
        ]);
    }

    /**
     * Validate and save updated threshold settings, then publish to ESP32 over MQTT.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fan_on_humidity' => ['required', 'integer', 'min:30', 'max:95', 'gt:fan_off_humidity'],
            'fan_off_humidity' => ['required', 'integer', 'min:20', 'max:90'],
            'detection_distance_cm' => ['required', 'integer', 'min:2', 'max:100'],
            'data_interval_seconds' => ['required', 'integer', 'min:1', 'max:300'],
            'auto_mode' => ['nullable', 'boolean'],
            'notify_on_complete' => ['nullable', 'boolean'],
        ], [
            'fan_on_humidity.gt' => 'Ambang kipas menyala (% RH) harus lebih besar daripada ambang kipas mati (% RH).',
        ]);

        $station = $this->telemetryService->getOrSeedDefaultStation();

        $station->update([
            'fan_on_humidity' => (int) $validated['fan_on_humidity'],
            'fan_off_humidity' => (int) $validated['fan_off_humidity'],
            'detection_distance_cm' => (int) $validated['detection_distance_cm'],
            'data_interval_seconds' => (int) $validated['data_interval_seconds'],
            'auto_mode' => $request->boolean('auto_mode'),
            'notify_on_complete' => $request->boolean('notify_on_complete'),
        ]);

        DeviceActivityLog::create([
            'station_id' => $station->id,
            'event_type' => 'config_updated',
            'description' => sprintf(
                'Pengaturan ambang diperbarui (ON > %d%%, OFF < %d%%, Jarak < %d cm)',
                $station->fan_on_humidity,
                $station->fan_off_humidity,
                $station->detection_distance_cm
            ),
            'occurred_at' => now(),
        ]);

        // Synchronize new thresholds with the ESP32 over MQTT
        $this->mqttBroker->publishThresholdConfig($station);

        return redirect()
            ->route('settings.index')
            ->with('status', 'Pengaturan ambang berhasil disimpan dan disinkronkan ke ESP32.');
    }

    /**
     * Restore factory default threshold settings (65%, 55%, 15 cm, 2 detik, Otomatis).
     */
    public function reset(): RedirectResponse
    {
        $station = $this->telemetryService->getOrSeedDefaultStation();
        $station->update(Station::DEFAULT_THRESHOLDS);

        DeviceActivityLog::create([
            'station_id' => $station->id,
            'event_type' => 'config_updated',
            'description' => 'Pengaturan ambang dikembalikan ke nilai default pabrik',
            'occurred_at' => now(),
        ]);

        $this->mqttBroker->publishThresholdConfig($station);

        return redirect()
            ->route('settings.index')
            ->with('status', 'Pengaturan ambang telah dikembalikan ke nilai default (65% / 55% / 15 cm / 2 detik).');
    }
}
