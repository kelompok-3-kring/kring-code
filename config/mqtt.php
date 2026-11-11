<?php

declare(strict_types=1);

/**
 * MQTT Configuration for Helmet Care Dehumidifier Station (ESP32 IoT).
 *
 * This configuration defines the connection parameters and topic naming conventions
 * used to exchange real-time sensor telemetry (DHT22 & HC-SR04), actuator commands
 * (Relay fan control & LED indicators), and threshold settings with ESP32 stations.
 *
 * Security Note:
 * Never hardcode broker credentials or API tokens in this file. Always use environment
 * variables defined in `.env` (which is excluded from version control via `.gitignore`).
 */
return [
    /*
    |--------------------------------------------------------------------------
    | MQTT Live Broker Toggle
    |--------------------------------------------------------------------------
    |
    | When enabled (`MQTT_ENABLED=true`), outbound threshold changes and manual
    | fan commands from the web dashboard will be published directly to the
    | configured MQTT broker in addition to being stored in the database.
    |
    */
    'enabled' => (bool) env('MQTT_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Broker Connection Settings
    |--------------------------------------------------------------------------
    */
    'host' => env('MQTT_HOST', 'broker.hivemq.com'),
    'port' => (int) env('MQTT_PORT', 1883),
    'client_id' => env('MQTT_CLIENT_ID', 'helmet-care-laravel-server'),
    'username' => env('MQTT_USERNAME'),
    'password' => env('MQTT_PASSWORD'),
    'use_tls' => (bool) env('MQTT_USE_TLS', false),
    'qos' => (int) env('MQTT_QOS', 0),

    /*
    |--------------------------------------------------------------------------
    | Topic Structure & Default Station
    |--------------------------------------------------------------------------
    |
    | Topics follow the pattern: `{topic_prefix}/{station_code}/{channel}`
    | - Telemetry (ESP32 -> Server): `helmet-care/stations/station-01/telemetry`
    | - Status    (ESP32 -> Server): `helmet-care/stations/station-01/status`
    | - Commands  (Server -> ESP32): `helmet-care/stations/station-01/commands`
    | - Config    (Server -> ESP32): `helmet-care/stations/station-01/config`
    |
    */
    'topic_prefix' => env('MQTT_TOPIC_PREFIX', 'helmet-care/stations'),
    'default_station' => env('MQTT_DEFAULT_STATION', 'station-01'),

    /*
    |--------------------------------------------------------------------------
    | Optional HTTP/Webhook Ingestion Token
    |--------------------------------------------------------------------------
    |
    | Optional shared secret header (`X-IoT-Token`) when the ESP32 or an MQTT
    | bridge forwards telemetry over HTTP POST `/api/iot/telemetry`.
    |
    */
    'api_token' => env('IOT_API_TOKEN'),
];
