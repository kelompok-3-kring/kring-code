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
 * ESP8266 sensor JSON payloads through `TelemetryIngestionService`.
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
    protected $description = 'Subscribe to ESP8266 MQTT telemetry topics and ingest sensor readings into the database';

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
            $this->info('Connected! Waiting for ESP8266 telemetry messages (Press Ctrl+C to stop)...');

            $client->subscribe($topic, function (string $receivedTopic, string $message) use ($telemetryIngestion): void {
                $this->line(sprintf('[%s] Topic: %s | Payload: %s', now()->format('H:i:s'), $receivedTopic, $message));

                $decoded = json_decode($message, true);
                if (! is_array($decoded) || ! isset($decoded['humidity'], $decoded['temperature'], $decoded['distance_cm'])) {
                    $this->warn('Ignored malformed MQTT payload (requires humidity, temperature, distance_cm).');

                    return;
                }

                // Extract station_code from topic before validation so it is
                // preserved when the payload itself omits that field.
                if (empty($decoded['station_code']) && preg_match('#/([^/]+)/telemetry$#', $receivedTopic, $matches)) {
                    $decoded['station_code'] = $matches[1];
                }

                $validator = validator($decoded, [
                    'station_code' => ['nullable', 'string', 'max:64'],
                    'humidity' => ['required', 'numeric', 'between:0,100'],
                    'temperature' => ['required', 'numeric', 'between:-20,85'],
                    'distance_cm' => ['required', 'numeric', 'between:0,400'],
                    'wifi_ssid' => ['nullable', 'string', 'max:100'],
                    'wifi_rssi' => ['nullable', 'integer', 'between:-120,0'],
                    'firmware_version' => ['nullable', 'string', 'max:50'],
                ]);
                if ($validator->fails()) {
                    $this->warn('Ignored invalid MQTT payload: '.$validator->errors()->first());

                    return;
                }

                try {
                    $station = $telemetryIngestion->ingest($validator->validated());
                    $this->info(sprintf(
                        'Ingested -> Station: %s | RH: %.1f%% | Temp: %.1f°C | Dist: %.1fcm | Fan: %s',
                        $station->code,
                        $station->current_humidity,
                        $station->current_temperature,
                        $station->current_distance_cm,
                        $station->fan_is_on ? 'ON' : 'OFF'
                    ));
                } catch (Throwable $exception) {
                    report($exception);
                    $this->error('Failed to ingest MQTT payload: '.$exception->getMessage());
                }
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
