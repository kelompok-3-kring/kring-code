<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DeviceActivityLog;
use App\Models\DryingCycle;
use App\Models\SensorReading;
use App\Models\Station;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder for Helmet Care Dehumidifier Station.
 *
 * Seeds realistic baseline data matching the product specification and UI/UX
 * reference for Station 01, including:
 * - Active ESP32 station state & hysteresis thresholds (65% ON, 55% OFF, 15cm, 2s)
 * - 7 days of drying cycle history (20 Okt - 26 Okt 2026) with 33 mnt avg duration & 24 pt avg RH drop
 * - Real-time DHT22 & HC-SR04 sensor trajectory from 80% down to 61%
 * - Device activity timeline events
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $station = Station::updateOrCreate(
            ['code' => 'station-01'],
            [
                'name' => 'Station 01',
                'mcu_type' => 'ESP32',
                'status' => 'online',
                'drying_state' => 'drying',
                'current_humidity' => 61.0,
                'current_temperature' => 31.4,
                'current_distance_cm' => 8.0,
                'helmet_detected' => true,
                'fan_is_on' => true,
                'fan_turned_on_at' => Carbon::parse('2026-10-26 08:04:00'),
                'led_status' => 'red_blinking',
                'ultrasonic_status' => 'Aktif',
                'dht_status' => 'Aktif',
                'wifi_ssid' => 'HelmetCare-IoT-Lab',
                'wifi_rssi' => -54,
                'server_endpoint' => url('/api/iot/telemetry'),
                'firmware_version' => 'v1.4.2-esp32',
                'last_seen_at' => now()->subSeconds(2),
                'fan_on_humidity' => 65,
                'fan_off_humidity' => 55,
                'detection_distance_cm' => 15,
                'data_interval_seconds' => 2,
                'auto_mode' => true,
                'notify_on_complete' => false,
            ]
        );

        // Clear existing child records to keep seeding idempotent
        $station->sensorReadings()->delete();
        $station->dryingCycles()->delete();
        $station->activityLogs()->delete();

        // 7-day drying cycle dataset (20 Okt 2026 -> 26 Okt 2026)
        // Average duration: (31 + 39 + 28 + 42 + 34 + 39 + 18) / 7 = 231 / 7 = 33 mnt
        // Average RH drop:  (23 + 24 + 21 + 31 + 23 + 28 + 18) / 7 = 168 / 7 = 24 poin
        $cyclesData = [
            [
                'started_at' => '2026-10-20 16:15:00',
                'ended_at' => '2026-10-20 16:46:00',
                'duration_minutes' => 31,
                'initial_humidity' => 77.0,
                'final_humidity' => 54.0,
                'avg_temperature' => 30.5,
                'mode' => 'Otomatis',
                'status' => 'completed',
            ],
            [
                'started_at' => '2026-10-21 17:10:00',
                'ended_at' => '2026-10-21 17:49:00',
                'duration_minutes' => 39,
                'initial_humidity' => 78.0,
                'final_humidity' => 54.0,
                'avg_temperature' => 30.9,
                'mode' => 'Otomatis',
                'status' => 'completed',
            ],
            [
                'started_at' => '2026-10-22 15:30:00',
                'ended_at' => '2026-10-22 15:58:00',
                'duration_minutes' => 28,
                'initial_humidity' => 75.0,
                'final_humidity' => 54.0,
                'avg_temperature' => 30.4,
                'mode' => 'Otomatis',
                'status' => 'completed',
            ],
            [
                'started_at' => '2026-10-23 18:05:00',
                'ended_at' => '2026-10-23 18:47:00',
                'duration_minutes' => 42,
                'initial_humidity' => 85.0,
                'final_humidity' => 54.0,
                'avg_temperature' => 31.0,
                'mode' => 'Otomatis',
                'status' => 'completed',
            ],
            [
                'started_at' => '2026-10-24 16:10:00',
                'ended_at' => '2026-10-24 16:44:00',
                'duration_minutes' => 34,
                'initial_humidity' => 76.0,
                'final_humidity' => 53.0,
                'avg_temperature' => 30.2,
                'mode' => 'Otomatis',
                'status' => 'completed',
            ],
            [
                'started_at' => '2026-10-25 17:42:00',
                'ended_at' => '2026-10-25 18:21:00',
                'duration_minutes' => 39,
                'initial_humidity' => 82.0,
                'final_humidity' => 54.0,
                'avg_temperature' => 30.8,
                'mode' => 'Otomatis',
                'status' => 'completed',
            ],
            [
                'started_at' => '2026-10-26 08:04:00',
                'ended_at' => null,
                'duration_minutes' => 18,
                'initial_humidity' => 79.0,
                'final_humidity' => 61.0,
                'avg_temperature' => 31.4,
                'mode' => 'Otomatis',
                'status' => 'running',
            ],
        ];

        $activeCycle = null;

        foreach ($cyclesData as $cycleData) {
            $cycle = DryingCycle::create(array_merge($cycleData, [
                'station_id' => $station->id,
            ]));

            if ($cycle->status === 'running') {
                $activeCycle = $cycle;
            }
        }

        // Seed real-time humidity curve readings for the active session (08:00 -> 08:22)
        $readings = [
            ['time' => '2026-10-26 08:00:00', 'humidity' => 80.0, 'temperature' => 30.6, 'distance_cm' => 8.0],
            ['time' => '2026-10-26 08:04:00', 'humidity' => 79.0, 'temperature' => 30.8, 'distance_cm' => 8.0],
            ['time' => '2026-10-26 08:08:00', 'humidity' => 75.0, 'temperature' => 31.0, 'distance_cm' => 8.0],
            ['time' => '2026-10-26 08:10:00', 'humidity' => 72.5, 'temperature' => 31.1, 'distance_cm' => 8.0],
            ['time' => '2026-10-26 08:14:00', 'humidity' => 68.0, 'temperature' => 31.2, 'distance_cm' => 8.0],
            ['time' => '2026-10-26 08:17:00', 'humidity' => 65.0, 'temperature' => 31.3, 'distance_cm' => 8.0],
            ['time' => '2026-10-26 08:20:00', 'humidity' => 62.5, 'temperature' => 31.4, 'distance_cm' => 8.0],
            ['time' => '2026-10-26 08:22:00', 'humidity' => 61.0, 'temperature' => 31.4, 'distance_cm' => 8.0],
        ];

        foreach ($readings as $reading) {
            SensorReading::create([
                'station_id' => $station->id,
                'drying_cycle_id' => $activeCycle?->id,
                'humidity' => $reading['humidity'],
                'temperature' => $reading['temperature'],
                'distance_cm' => $reading['distance_cm'],
                'fan_is_on' => true,
                'led_status' => 'red_blinking',
                'recorded_at' => Carbon::parse($reading['time']),
            ]);
        }

        // Seed Device Activity Logs matching Page 3 (Perangkat)
        $activities = [
            [
                'event_type' => 'helmet_detected',
                'description' => 'Helm terdeteksi, sistem aktif',
                'occurred_at' => '2026-10-26 08:04:10',
            ],
            [
                'event_type' => 'fan_on',
                'description' => 'RH > 65%, kipas dinyalakan',
                'occurred_at' => '2026-10-26 08:04:12',
            ],
            [
                'event_type' => 'drying_completed',
                'description' => 'Pengeringan selesai (kemarin)',
                'occurred_at' => '2026-10-25 18:21:00',
            ],
            [
                'event_type' => 'standby',
                'description' => 'Helm diambil, kembali standby',
                'occurred_at' => '2026-10-25 18:30:00',
            ],
        ];

        foreach ($activities as $activity) {
            DeviceActivityLog::create([
                'station_id' => $station->id,
                'event_type' => $activity['event_type'],
                'description' => $activity['description'],
                'occurred_at' => Carbon::parse($activity['occurred_at']),
            ]);
        }
    }
}
