<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for the Helmet Care Dehumidifier Station IoT system.
 *
 * Creates the core domain tables:
 * 1. `stations`             - Device metadata, live sensor snapshot, Wi-Fi/MQTT info, and threshold configs.
 * 2. `drying_cycles`        - Historical and active drying sessions for daily logs and analytics.
 * 3. `sensor_readings`      - Time-series telemetry received from ESP32 (DHT22 + HC-SR04) via MQTT/HTTP.
 * 4. `device_activity_logs` - Chronological hardware and automation event logs.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stations', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique()->comment('Unique identifier used in MQTT topics, e.g., station-01');
            $table->string('name')->default('Station 01');
            $table->string('mcu_type')->default('ESP32');
            $table->string('status')->default('online')->comment('online | offline');
            $table->string('drying_state')->default('drying')->comment('drying | completed | standby');

            // Live sensor snapshot (DHT22 & HC-SR04)
            $table->decimal('current_humidity', 5, 2)->default(61.00);
            $table->decimal('current_temperature', 5, 2)->default(31.40);
            $table->decimal('current_distance_cm', 5, 2)->default(8.00);
            $table->boolean('helmet_detected')->default(true);

            // Actuator states (Relay Fan & Status LED)
            $table->boolean('fan_is_on')->default(true);
            $table->timestamp('fan_turned_on_at')->nullable();
            $table->string('led_status')->default('red_blinking')->comment('red_blinking | green_solid | off');
            $table->string('ultrasonic_status')->default('Aktif');
            $table->string('dht_status')->default('Aktif');

            // Network & Firmware Metadata
            $table->string('wifi_ssid')->default('HelmetCare-Lab-WiFi');
            $table->integer('wifi_rssi')->default(-52);
            $table->string('server_endpoint')->default('mqtt://broker.hivemq.com:1883 · /api/iot/telemetry');
            $table->string('firmware_version')->default('v1.4.2-esp32');
            $table->timestamp('last_seen_at')->nullable();

            // Configurable Hysteresis Thresholds & Operation Mode
            $table->unsignedTinyInteger('fan_on_humidity')->default(65)->comment('Turn fan ON above this % RH');
            $table->unsignedTinyInteger('fan_off_humidity')->default(55)->comment('Turn fan OFF below this % RH');
            $table->unsignedSmallInteger('detection_distance_cm')->default(15)->comment('Helmet present when distance < this cm');
            $table->unsignedSmallInteger('data_interval_seconds')->default(2)->comment('Telemetry reporting interval in seconds');
            $table->boolean('auto_mode')->default(true)->comment('Whether fan follows RH thresholds automatically');
            $table->boolean('notify_on_complete')->default(false)->comment('Send notification when drying cycle finishes');

            $table->timestamps();
        });

        Schema::create('drying_cycles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('station_id')->constrained('stations')->cascadeOnDelete();
            $table->timestamp('started_at')->index();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->decimal('initial_humidity', 5, 2);
            $table->decimal('final_humidity', 5, 2)->nullable();
            $table->decimal('avg_temperature', 5, 2)->default(31.00);
            $table->string('mode')->default('Otomatis')->comment('Otomatis | Manual');
            $table->string('status')->default('running')->index()->comment('running | completed | stopped');
            $table->timestamps();
        });

        Schema::create('sensor_readings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('station_id')->constrained('stations')->cascadeOnDelete();
            $table->foreignId('drying_cycle_id')->nullable()->constrained('drying_cycles')->nullOnDelete();
            $table->decimal('humidity', 5, 2);
            $table->decimal('temperature', 5, 2);
            $table->decimal('distance_cm', 5, 2);
            $table->boolean('fan_is_on')->default(false);
            $table->string('led_status')->default('off');
            $table->timestamp('recorded_at')->index();
            $table->timestamps();
        });

        Schema::create('device_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('station_id')->constrained('stations')->cascadeOnDelete();
            $table->string('event_type')->default('info');
            $table->string('description');
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_activity_logs');
        Schema::dropIfExists('sensor_readings');
        Schema::dropIfExists('drying_cycles');
        Schema::dropIfExists('stations');
    }
};
