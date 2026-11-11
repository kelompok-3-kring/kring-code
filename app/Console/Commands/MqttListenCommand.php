<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MqttBrokerService;
use App\Services\TelemetryIngestionService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Artisan Command: `php artisan mqtt:listen`
 *
 * Connects to the configured MQTT broker (e.g., HiveMQ, Mosquitto, EMQX),
 * subscribes to `helmet-care/stations/+/telemetry`, and processes incoming
 * ESP32 sensor JSON payloads through `TelemetryIngestionService`.
 */
class MqttListenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:listen {--station= : Filter to a specific station code (default: wildcard +)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscribe to ESP32 MQTT telemetry topics and ingest sensor readings into the database';

    /**
     * Execute the console command.
     */
    public function handle(
        MqttBrokerService $mqttBroker,
        TelemetryIngestionService $telemetryIngestion
    ): int {
        $stationCode = (string) ($this->option('station') ?: '+');
        $topic = $mqttBroker->topicFor($stationCode, 'telemetry');

        $this->info(sprintf(
            'Connecting to MQTT Broker [%s:%d] and subscribing to [%s]...',
            config('mqtt.host'),
            config('mqtt.port'),
            $topic
        ));

        try {
            $client = $mqttBroker->createClient('-subscriber-'.uniqid());
            $settings = $mqttBroker->createConnectionSettings();

            $client->connect($settings, true);
            $this->info('Connected! Waiting for ESP32 telemetry messages (Press Ctrl+C to stop)...');

            $client->subscribe($topic, function (string $receivedTopic, string $message) use ($telemetryIngestion): void {
                $this->line(sprintf('[%s] Topic: %s | Payload: %s', now()->format('H:i:s'), $receivedTopic, $message));

                $decoded = json_decode($message, true);
                if (! is_array($decoded) || ! isset($decoded['humidity'], $decoded['temperature'], $decoded['distance_cm'])) {
                    $this->warn('Ignored malformed MQTT payload (requires humidity, temperature, distance_cm).');

                    return;
                }

                // Extract station_code from topic if not explicitly in payload
                if (empty($decoded['station_code']) && preg_match('#/([^/]+)/telemetry$#', $receivedTopic, $matches)) {
                    $decoded['station_code'] = $matches[1];
                }

                $station = $telemetryIngestion->ingest($decoded);
                $this->info(sprintf(
                    'Ingested -> Station: %s | RH: %.1f%% | Temp: %.1f°C | Dist: %.1fcm | Fan: %s',
                    $station->code,
                    $station->current_humidity,
                    $station->current_temperature,
                    $station->current_distance_cm,
                    $station->fan_is_on ? 'ON' : 'OFF'
                ));
            }, (int) config('mqtt.qos', 0));

            $client->loop(true);
            $client->disconnect();

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('MQTT Listener error: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
