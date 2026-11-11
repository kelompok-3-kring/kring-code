<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Station;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Behavior-Driven Development (BDD) Feature Specifications
 * for the Helmet Care Dehumidifier Station Web & IoT Platform.
 *
 * Each test method describes a complete end-to-end user or IoT hardware behavior
 * following the Given / When / Then BDD structure.
 */
class HelmetCareBddTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Scenario 1: Operator monitors real-time station telemetry on the Dashboard.
     */
    public function test_scenario_operator_views_realtime_dashboard_station(): void
    {
        // GIVEN Station 01 is online and actively drying a helmet at 61% RH
        $station = Station::where('code', 'station-01')->firstOrFail();
        $this->assertTrue($station->fan_is_on);

        // WHEN the operator visits the Dashboard page
        $response = $this->get(route('dashboard'));

        // THEN the dashboard renders all live telemetry cards, chart, and daily logs
        $response->assertOk()
            ->assertSee('Dashboard Station')
            ->assertSee('ESP32 Online')
            ->assertSee('Sedang Mengeringkan')
            ->assertSee('Kelembapan Saat Ini')
            ->assertSee('61')
            ->assertSee('31,4')
            ->assertSee('Grafik Kelembapan Real-Time')
            ->assertSee('Siklus Pengeringan')
            ->assertSee('Matikan Kipas Manual')
            ->assertSee('Log Pengeringan Harian');
    }

    /**
     * Scenario 2: ESP32 telemetry triggers hysteresis drying completion and standby mode.
     */
    public function test_scenario_esp32_telemetry_completes_drying_cycle_and_enters_standby(): void
    {
        // GIVEN Station 01 is running an active drying cycle (fan ON, RH 61%, threshold OFF < 55%)
        $station = Station::where('code', 'station-01')->firstOrFail();
        $this->assertSame('drying', $station->drying_state);

        // WHEN the ESP32 publishes telemetry inside the hysteresis band (58% RH, between 55% and 65%)
        $inBandResponse = $this->postJson(route('api.iot.telemetry.store'), [
            'station_code' => 'station-01',
            'humidity' => 58.0,
            'temperature' => 31.2,
            'distance_cm' => 8.0,
        ]);

        // THEN the fan remains ON (preventing relay chatter inside the hysteresis band)
        $inBandResponse->assertOk()
            ->assertJsonPath('station.fan_is_on', true)
            ->assertJsonPath('station.led_status', 'red_blinking');

        // WHEN the ESP32 publishes telemetry below the dry threshold (53% RH < 55%)
        $dryResponse = $this->postJson(route('api.iot.telemetry.store'), [
            'station_code' => 'station-01',
            'humidity' => 53.0,
            'temperature' => 30.8,
            'distance_cm' => 8.0,
        ]);

        // THEN the fan turns OFF, LED becomes solid green, and the cycle is marked completed
        $dryResponse->assertOk()
            ->assertJsonPath('station.fan_is_on', false)
            ->assertJsonPath('station.led_status', 'green_solid')
            ->assertJsonPath('station.drying_state', 'completed');

        // WHEN the user removes the helmet from the station (distance 25 cm >= 15 cm threshold)
        $standbyResponse = $this->postJson(route('api.iot.telemetry.store'), [
            'station_code' => 'station-01',
            'humidity' => 54.0,
            'temperature' => 29.5,
            'distance_cm' => 25.0,
        ]);

        // THEN the station enters standby mode with LED off
        $standbyResponse->assertOk()
            ->assertJsonPath('station.helmet_detected', false)
            ->assertJsonPath('station.fan_is_on', false)
            ->assertJsonPath('station.led_status', 'off')
            ->assertJsonPath('station.drying_state', 'standby');
    }

    /**
     * Scenario 3: Operator manually overrides the relay fan from the Dashboard.
     */
    public function test_scenario_operator_toggles_fan_manually_from_dashboard(): void
    {
        // GIVEN the relay fan is initially ON
        $station = Station::where('code', 'station-01')->firstOrFail();
        $this->assertTrue($station->fan_is_on);

        // WHEN the operator clicks "Matikan Kipas Manual"
        $response = $this->post(route('dashboard.toggle-fan'));

        // THEN they are redirected back to the dashboard and the fan is turned OFF
        $response->assertRedirect(route('dashboard'));
        $station->refresh();
        $this->assertFalse($station->fan_is_on);
        $this->assertDatabaseHas('device_activity_logs', [
            'station_id' => $station->id,
            'event_type' => 'manual_override',
        ]);
    }

    /**
     * Scenario 4: Operator inspects 7-day drying history and exports CSV.
     */
    public function test_scenario_operator_views_drying_history_and_exports_csv(): void
    {
        // GIVEN 7 days of drying cycles exist in the database
        // WHEN the operator visits Riwayat Pengeringan
        $response = $this->get(route('history.index'));

        // THEN the 7-day KPIs (7 cycles, 33 mnt avg duration, 24 poin avg RH drop) and chart are shown
        $response->assertOk()
            ->assertSee('Riwayat Pengeringan')
            ->assertSee('Durasi Pengeringan per Hari')
            ->assertSee('33')
            ->assertSee('24')
            ->assertSee('Menampilkan 1–4 dari 7');

        // AND WHEN the operator clicks "Ekspor CSV"
        $csvResponse = $this->get(route('history.export'));

        // THEN a downloadable CSV stream is returned
        $csvResponse->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csvResponse->headers->get('Content-Type'));
        $csvContent = $csvResponse->streamedContent();
        $this->assertStringContainsString('Tanggal,Mulai,Selesai', $csvContent);
        $this->assertStringContainsString('26 Okt 2026', $csvContent);
    }

    /**
     * Scenario 5: Operator views device diagnostics and updates/resets threshold settings.
     */
    public function test_scenario_operator_manages_device_and_threshold_settings(): void
    {
        // GIVEN the operator views the Perangkat page
        $this->get(route('devices.index'))
            ->assertOk()
            ->assertSee('Sensor Ultrasonik HC-SR04')
            ->assertSee('Sensor DHT22')
            ->assertSee('Modul Relay (Kipas)')
            ->assertSee('Aktivitas Perangkat');

        // WHEN the operator updates threshold settings on Pengaturan Ambang
        $updateResponse = $this->put(route('settings.update'), [
            'fan_on_humidity' => 70,
            'fan_off_humidity' => 50,
            'detection_distance_cm' => 12,
            'data_interval_seconds' => 5,
            'auto_mode' => 1,
            'notify_on_complete' => 1,
        ]);

        // THEN the new thresholds are saved to the database
        $updateResponse->assertRedirect(route('settings.index'));
        $this->assertDatabaseHas('stations', [
            'code' => 'station-01',
            'fan_on_humidity' => 70,
            'fan_off_humidity' => 50,
            'detection_distance_cm' => 12,
            'data_interval_seconds' => 5,
            'notify_on_complete' => 1,
        ]);

        // AND WHEN the operator clicks "Kembalikan Default"
        $resetResponse = $this->post(route('settings.reset'));

        // THEN factory default thresholds (65%, 55%, 15 cm, 2 detik) are restored
        $resetResponse->assertRedirect(route('settings.index'));
        $this->assertDatabaseHas('stations', [
            'code' => 'station-01',
            'fan_on_humidity' => 65,
            'fan_off_humidity' => 55,
            'detection_distance_cm' => 15,
            'data_interval_seconds' => 2,
        ]);
    }
}
