<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DeviceActivityLog;
use App\Models\DryingCycle;
use App\Models\SensorReading;
use App\Models\Station;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

/**
 * TelemetryIngestionService
 *
 * Core domain service that processes incoming IoT telemetry from ESP32 stations
 * (whether delivered via MQTT subscriber or HTTP REST endpoint).
 *
 * Responsibilities:
 * 1. Evaluate ultrasonic helmet presence (`distance_cm < detection_distance_cm`).
 * 2. Apply hysteresis control logic (`fan_on_humidity` vs `fan_off_humidity`) when in Automatic mode.
 * 3. Manage the lifecycle of `DryingCycle` records (start, update duration/RH, complete).
 * 4. Persist `SensorReading` time-series points and `DeviceActivityLog` transitions.
 */
class TelemetryIngestionService
{
    public function __construct(
        private readonly MqttBrokerService $mqttBroker
    ) {}

    /**
     * Retrieve the primary station (`station-01`), seeding baseline data if the database is empty.
     */
    public function getOrSeedDefaultStation(?string $stationCode = null): Station
    {
        $code = $stationCode ?: (string) config('mqtt.default_station', 'station-01');

        $station = Station::where('code', $code)->first();

        if (! $station) {
            app(DatabaseSeeder::class)->run();
            $station = Station::where('code', $code)->firstOrFail();
        }

        return $station;
    }

    /**
     * Process an incoming telemetry payload from an ESP32 station.
     *
     * @param  array{
     *     station_code?: string,
     *     humidity: float|int|numeric-string,
     *     temperature: float|int|numeric-string,
     *     distance_cm: float|int|numeric-string,
     *     wifi_ssid?: string|null,
     *     wifi_rssi?: int|null,
     *     firmware_version?: string|null
     * }  $payload
     */
    public function ingest(array $payload, ?Station $station = null): Station
    {
        return DB::transaction(function () use ($payload, $station): Station {
            $station ??= $this->getOrSeedDefaultStation($payload['station_code'] ?? null);

            $humidity = round((float) $payload['humidity'], 1);
            $temperature = round((float) $payload['temperature'], 1);
            $distanceCm = round((float) $payload['distance_cm'], 1);

            $wasHelmetDetected = (bool) $station->helmet_detected;
            $helmetDetected = $distanceCm < (float) $station->detection_distance_cm;

            $fanIsOn = (bool) $station->fan_is_on;
            $ledStatus = (string) $station->led_status;
            $dryingState = (string) $station->drying_state;

            /** @var DryingCycle|null $activeCycle */
            $activeCycle = $station->activeCycle()->first();

            if (! $helmetDetected) {
                // Helmet removed -> Standby state
                $fanIsOn = false;
                $ledStatus = 'off';
                $dryingState = 'standby';

                if ($activeCycle) {
                    $duration = max(1, (int) $activeCycle->started_at->diffInMinutes(now()));
                    $activeCycle->update([
                        'ended_at' => now(),
                        'duration_minutes' => $activeCycle->duration_minutes ?: $duration,
                        'final_humidity' => $humidity,
                        'status' => $humidity <= (float) $station->fan_off_humidity ? 'completed' : 'stopped',
                    ]);
                    $activeCycle = null;
                }

                if ($wasHelmetDetected) {
                    DeviceActivityLog::create([
                        'station_id' => $station->id,
                        'event_type' => 'standby',
                        'description' => 'Helm diambil, kembali standby',
                        'occurred_at' => now(),
                    ]);
                }
            } else {
                // Helmet is present (< detection_distance_cm)
                if (! $wasHelmetDetected) {
                    DeviceActivityLog::create([
                        'station_id' => $station->id,
                        'event_type' => 'helmet_detected',
                        'description' => 'Helm terdeteksi, sistem aktif',
                        'occurred_at' => now(),
                    ]);
                }

                if ($station->auto_mode) {
                    if ($humidity > (float) $station->fan_on_humidity) {
                        // Upper hysteresis threshold exceeded -> Turn Fan ON & start cycle
                        if (! $fanIsOn) {
                            $fanIsOn = true;
                            $station->fan_turned_on_at = now();

                            DeviceActivityLog::create([
                                'station_id' => $station->id,
                                'event_type' => 'fan_on',
                                'description' => sprintf('RH > %d%%, kipas dinyalakan', $station->fan_on_humidity),
                                'occurred_at' => now(),
                            ]);
                        }

                        $ledStatus = 'red_blinking';
                        $dryingState = 'drying';

                        if (! $activeCycle) {
                            $activeCycle = DryingCycle::create([
                                'station_id' => $station->id,
                                'started_at' => now(),
                                'duration_minutes' => 1,
                                'initial_humidity' => $humidity,
                                'final_humidity' => $humidity,
                                'avg_temperature' => $temperature,
                                'mode' => 'Otomatis',
                                'status' => 'running',
                            ]);
                        }
                    } elseif ($humidity < (float) $station->fan_off_humidity) {
                        // Lower hysteresis threshold reached -> Helmet is dry, turn Fan OFF
                        $wasFanOn = $fanIsOn;
                        $fanIsOn = false;
                        $ledStatus = 'green_solid';
                        $dryingState = 'completed';

                        if ($activeCycle) {
                            $elapsed = max(1, (int) $activeCycle->started_at->diffInMinutes(now()));
                            $activeCycle->update([
                                'ended_at' => now(),
                                'duration_minutes' => max($activeCycle->duration_minutes, $elapsed),
                                'final_humidity' => $humidity,
                                'avg_temperature' => round((($activeCycle->avg_temperature + $temperature) / 2), 1),
                                'status' => 'completed',
                            ]);
                            $activeCycle = null;
                        }

                        if ($wasFanOn) {
                            DeviceActivityLog::create([
                                'station_id' => $station->id,
                                'event_type' => 'drying_completed',
                                'description' => sprintf('Pengeringan selesai (RH %.0f%% < %d%%)', $humidity, $station->fan_off_humidity),
                                'occurred_at' => now(),
                            ]);
                        }
                    } else {
                        // Inside the hysteresis band (between fan_off_humidity and fan_on_humidity):
                        // Maintain current actuator state to prevent rapid relay chattering.
                        if ($fanIsOn) {
                            $ledStatus = 'red_blinking';
                            $dryingState = 'drying';
                        } else {
                            $ledStatus = 'green_solid';
                            $dryingState = 'completed';
                        }
                    }
                }

                // Update running cycle metrics if still active
                if ($activeCycle) {
                    $elapsed = max($activeCycle->duration_minutes, (int) $activeCycle->started_at->diffInMinutes(now()));
                    $activeCycle->update([
                        'final_humidity' => $humidity,
                        'duration_minutes' => max(1, $elapsed),
                        'avg_temperature' => round((($activeCycle->avg_temperature + $temperature) / 2), 1),
                    ]);
                }
            }

            // Update station snapshot
            $station->fill([
                'status' => 'online',
                'drying_state' => $dryingState,
                'current_humidity' => $humidity,
                'current_temperature' => $temperature,
                'current_distance_cm' => $distanceCm,
                'helmet_detected' => $helmetDetected,
                'fan_is_on' => $fanIsOn,
                'fan_turned_on_at' => $fanIsOn ? ($station->fan_turned_on_at ?? now()) : null,
                'led_status' => $ledStatus,
                'wifi_ssid' => $payload['wifi_ssid'] ?? $station->wifi_ssid,
                'wifi_rssi' => isset($payload['wifi_rssi']) ? (int) $payload['wifi_rssi'] : $station->wifi_rssi,
                'firmware_version' => $payload['firmware_version'] ?? $station->firmware_version,
                'last_seen_at' => now(),
            ]);
            $station->save();

            // Record time-series sensor reading
            SensorReading::create([
                'station_id' => $station->id,
                'drying_cycle_id' => $activeCycle?->id,
                'humidity' => $humidity,
                'temperature' => $temperature,
                'distance_cm' => $distanceCm,
                'fan_is_on' => $fanIsOn,
                'led_status' => $ledStatus,
                'recorded_at' => now(),
            ]);

            return $station->fresh(['activeCycle']);
        });
    }

    /**
     * Toggle the relay fan manually from the web dashboard ("Matikan Kipas Manual" / "Nyalakan Kipas Manual")
     * and publish the command to the ESP32 over MQTT.
     */
    public function toggleManualFan(Station $station): Station
    {
        $newFanState = ! $station->fan_is_on;
        $activeCycle = $station->activeCycle()->first();

        if (! $newFanState) {
            // Turning fan OFF manually
            $station->update([
                'fan_is_on' => false,
                'drying_state' => $station->helmet_detected ? 'completed' : 'standby',
                'led_status' => $station->helmet_detected ? 'green_solid' : 'off',
            ]);

            if ($activeCycle) {
                $activeCycle->update([
                    'ended_at' => now(),
                    'final_humidity' => $station->current_humidity,
                    'mode' => 'Manual',
                    'status' => 'completed',
                ]);
            }

            DeviceActivityLog::create([
                'station_id' => $station->id,
                'event_type' => 'manual_override',
                'description' => 'Kipas dimatikan secara manual dari dashboard',
                'occurred_at' => now(),
            ]);
        } else {
            // Turning fan ON manually
            $station->update([
                'fan_is_on' => true,
                'fan_turned_on_at' => now(),
                'drying_state' => 'drying',
                'led_status' => 'red_blinking',
            ]);

            if (! $activeCycle) {
                DryingCycle::create([
                    'station_id' => $station->id,
                    'started_at' => now(),
                    'duration_minutes' => 1,
                    'initial_humidity' => $station->current_humidity,
                    'final_humidity' => $station->current_humidity,
                    'avg_temperature' => $station->current_temperature,
                    'mode' => 'Manual',
                    'status' => 'running',
                ]);
            }

            DeviceActivityLog::create([
                'station_id' => $station->id,
                'event_type' => 'manual_override',
                'description' => 'Kipas dinyalakan secara manual dari dashboard',
                'occurred_at' => now(),
            ]);
        }

        // Publish relay command to ESP32 via MQTT
        $this->mqttBroker->publishCommand($station, [
            'action' => 'set_fan',
            'fan_is_on' => $newFanState,
            'led_status' => $station->led_status,
        ]);

        return $station->fresh(['activeCycle']);
    }
}
