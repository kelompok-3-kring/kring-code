<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MqttBrokerService;
use App\Services\TelemetryIngestionService;
use Illuminate\View\View;

/**
 * DeviceController
 *
 * Handles the "Perangkat" page (`/perangkat`), displaying:
 * - Station 01 online banner and last telemetry timestamp
 * - Individual component status cards (HC-SR04 Ultrasonic, DHT22 Sensor, Relay Fan, LED)
 * - Network & MQTT Connection metadata (Wi-Fi SSID, RSSI, Endpoint URL, Firmware Version)
 * - Chronological Device Activity Log (`Aktivitas Perangkat`)
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
}
