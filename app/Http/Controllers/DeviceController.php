<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MqttBrokerService;
use App\Services\TelemetryIngestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * DeviceController
 *
 * Handles the "Perangkat" page (`/perangkat`), displaying:
 * - Station 01 online banner and last telemetry timestamp
 * - Individual component status cards (HC-SR04 Ultrasonic, DHT22 Sensor, Relay Fan, LED)
 * - Network & MQTT Connection metadata (Wi-Fi SSID, RSSI, Endpoint URL, Firmware Version)
 * - Chronological Device Activity Log (`Aktivitas Perangkat`)
 * - Interactive MQTT Telemetry Simulator for testing before physical ESP32 deployment
 */
class DeviceController extends Controller
{
    public function __construct(
        private readonly TelemetryIngestionService $telemetryService,
        private readonly MqttBrokerService $mqttBroker
    ) {}

    /**
     * Display the Device status & components page.
     */
    public function index(): View
    {
        $station = $this->telemetryService->getOrSeedDefaultStation();
        $activities = $station->activityLogs()
            ->orderByDesc('occurred_at')
            ->limit(8)
            ->get();

        return view('devices.index', [
            'station' => $station,
            'activities' => $activities,
            'mqttTopic' => $this->mqttBroker->topicFor($station->code, 'telemetry'),
        ]);
    }

    /**
     * Simulate an incoming MQTT telemetry frame from the ESP32 right from the web UI.
     */
    public function simulate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'humidity' => ['required', 'numeric', 'min:0', 'max:100'],
            'temperature' => ['required', 'numeric', 'min:-10', 'max:80'],
            'distance_cm' => ['required', 'numeric', 'min:0', 'max:400'],
        ]);

        $station = $this->telemetryService->getOrSeedDefaultStation();
        $topic = $this->mqttBroker->topicFor($station->code, 'telemetry');

        $payload = [
            'station_code' => $station->code,
            'humidity' => (float) $validated['humidity'],
            'temperature' => (float) $validated['temperature'],
            'distance_cm' => (float) $validated['distance_cm'],
        ];

        $this->mqttBroker->publish($topic, $payload);
        $updated = $this->telemetryService->ingest($payload, $station);

        return redirect()
            ->route('devices.index')
            ->with(
                'status',
                sprintf(
                    'Payload MQTT berhasil disimulasikan (RH: %.0f%%, Suhu: %.1f°C, Jarak: %.0f cm → Kipas: %s).',
                    $updated->current_humidity,
                    $updated->current_temperature,
                    $updated->current_distance_cm,
                    $updated->fan_is_on ? 'Menyala' : 'Mati'
                )
            );
    }
}
