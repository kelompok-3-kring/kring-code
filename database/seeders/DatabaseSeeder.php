<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Station;
use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder for Helmet Care Dehumidifier Station.
 *
 * Seeds realistic baseline data matching the product specification and UI/UX
 * reference for Station 01, including:
 * - Active ESP8266 station state & hysteresis thresholds (65% ON, 55% OFF, 15cm, 2s)
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
                'mcu_type' => 'ESP8266',
                'status' => 'offline',
                'drying_state' => 'standby',
                'current_humidity' => null,
                'current_temperature' => null,
                'current_distance_cm' => null,
                'helmet_detected' => false,
                'fan_is_on' => false,
                'fan_turned_on_at' => null,
                'led_status' => 'off',
                'ultrasonic_status' => 'Aktif',
                'dht_status' => 'Aktif',
                'wifi_ssid' => null,
                'wifi_rssi' => null,
                'server_endpoint' => url('/api/iot/telemetry'),
                'firmware_version' => 'Belum terhubung',
                'last_seen_at' => null,
                'fan_on_humidity' => 65,
                'fan_off_humidity' => 55,
                'detection_distance_cm' => 15,
                'data_interval_seconds' => 2,
                'auto_mode' => true,
                'notify_on_complete' => false,
            ]
        );

    }
}
