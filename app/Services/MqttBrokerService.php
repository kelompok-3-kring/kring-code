<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Station;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use Throwable;

/**
 * MqttBrokerService
 *
 * Manages MQTT communication between the Laravel application and ESP32
 * Helmet Care Dehumidifier Stations using the `php-mqtt/client` library.
 *
 * Topic Contract:
 * - `helmet-care/stations/{code}/telemetry` (ESP32 -> Laravel: JSON sensor readings)
 * - `helmet-care/stations/{code}/commands`  (Laravel -> ESP32: JSON relay/LED commands)
 * - `helmet-care/stations/{code}/config`    (Laravel -> ESP32: JSON threshold settings)
 */
class MqttBrokerService
{
    /**
     * Build the full MQTT topic string for a given station and channel.
     */
    public function topicFor(string $stationCode, string $channel): string
    {
        $prefix = rtrim((string) config('mqtt.topic_prefix', 'helmet-care/stations'), '/');

        return sprintf('%s/%s/%s', $prefix, $stationCode, ltrim($channel, '/'));
    }

    /**
     * Publish an actuator command (e.g. manual fan relay override) to an ESP32 station.
     *
     * @param  array<string, mixed>  $commandPayload
     */
    public function publishCommand(Station $station, array $commandPayload): bool
    {
        $topic = $this->topicFor($station->code, 'commands');

        return $this->publish($topic, array_merge([
            'station_code' => $station->code,
            'timestamp' => now()->toIso8601String(),
        ], $commandPayload));
    }

    /**
     * Publish updated hysteresis threshold settings to an ESP32 station.
     */
    public function publishThresholdConfig(Station $station): bool
    {
        $topic = $this->topicFor($station->code, 'config');

        return $this->publish($topic, [
            'station_code' => $station->code,
            'fan_on_humidity' => $station->fan_on_humidity,
            'fan_off_humidity' => $station->fan_off_humidity,
            'detection_distance_cm' => $station->detection_distance_cm,
            'data_interval_seconds' => $station->data_interval_seconds,
            'auto_mode' => $station->auto_mode,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Publish a JSON payload to the configured MQTT broker.
     *
     * When `MQTT_ENABLED=false`, payloads are logged to the application log so
     * local development and automated tests work without requiring an external broker.
     *
     * @param  array<string, mixed>  $payload
     */
    public function publish(string $topic, array $payload): bool
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (! config('mqtt.enabled', false)) {
            Log::info('[MQTT Simulated Publish]', [
                'topic' => $topic,
                'payload' => $payload,
            ]);

            return true;
        }

        try {
            $client = $this->createClient('-publisher-'.uniqid());
            $settings = $this->createConnectionSettings();

            $client->connect($settings, true);
            $client->publish($topic, $json, (int) config('mqtt.qos', 0));
            $client->disconnect();

            return true;
        } catch (Throwable $exception) {
            Log::warning('[MQTT Publish Failed]', [
                'topic' => $topic,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create a new `MqttClient` instance using configuration values.
     */
    public function createClient(string $clientSuffix = ''): MqttClient
    {
        $host = (string) config('mqtt.host', 'broker.hivemq.com');
        $port = (int) config('mqtt.port', 1883);
        $clientId = (string) config('mqtt.client_id', 'helmet-care-laravel-server').$clientSuffix;

        return new MqttClient($host, $port, $clientId);
    }

    /**
     * Build `ConnectionSettings` for the MQTT client.
     */
    public function createConnectionSettings(): ConnectionSettings
    {
        $username = config('mqtt.username');
        $password = config('mqtt.password');
        $useTls = (bool) config('mqtt.use_tls', false);

        return (new ConnectionSettings)
            ->setUsername(is_string($username) && $username !== '' ? $username : null)
            ->setPassword(is_string($password) && $password !== '' ? $password : null)
            ->setUseTls($useTls)
            ->setConnectTimeout(5);
    }
}
