<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Station;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder for Helmet Care Dehumidifier Station.
 *
 * Seeds realistic baseline data matching the product specification and UI/UX
 * reference for Station 01, including:
 * - Active ESP32 station state & hysteresis thresholds (65% ON, 55% OFF, 15cm, 2s)
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
    }
}
