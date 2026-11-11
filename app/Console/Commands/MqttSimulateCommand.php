<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MqttBrokerService;
use App\Services\TelemetryIngestionService;
use Illuminate\Console\Command;

/**
 * Artisan Command: `php artisan mqtt:simulate`
 *
 * Simulates an ESP32 station transmitting DHT22 (humidity & temperature) and
 * HC-SR04 (ultrasonic distance) sensor telemetry for testing the web dashboard
 * before physical hardware is connected.
 */
class MqttSimulateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:simulate
                            {--humidity=59.5 : Simulated Relative Humidity (% RH)}
                            {--temperature=31.5 : Simulated Temperature (°C)}
                            {--distance=8.0 : Simulated Ultrasonic Distance (cm)}
                            {--station=station-01 : Target station code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate an ESP32 telemetry payload for testing MQTT/IoT ingestion';

    /**
     * Execute the console command.
     */
    public function handle(
        MqttBrokerService $mqttBroker,
        TelemetryIngestionService $telemetryIngestion
    ): int {
        $payload = [
            'station_code' => (string) $this->option('station'),
            'humidity' => (float) $this->option('humidity'),
            'temperature' => (float) $this->option('temperature'),
            'distance_cm' => (float) $this->option('distance'),
            'wifi_ssid' => 'HelmetCare-IoT-Lab',
            'wifi_rssi' => -51,
            'firmware_version' => 'v1.4.2-esp32',
        ];

        $topic = $mqttBroker->topicFor($payload['station_code'], 'telemetry');
        $mqttBroker->publish($topic, $payload);
        $station = $telemetryIngestion->ingest($payload);

        $this->info(sprintf(
            'Simulated ESP32 Telemetry [%s] -> RH: %.1f%% | Suhu: %.1f°C | Jarak: %.1f cm | Kipas: %s | LED: %s',
            $station->name,
            $station->current_humidity,
            $station->current_temperature,
            $station->current_distance_cm,
            $station->fan_is_on ? 'ON' : 'OFF',
            $station->led_label
        ));

        return self::SUCCESS;
    }
}
