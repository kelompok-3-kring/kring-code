<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drying_cycles', function (Blueprint $table): void {
            $table->index(['station_id', 'started_at'], 'drying_cycles_station_started_index');
            $table->index(['station_id', 'status', 'started_at'], 'drying_cycles_station_status_started_index');
        });

        Schema::table('sensor_readings', function (Blueprint $table): void {
            $table->index(['station_id', 'recorded_at'], 'sensor_readings_station_recorded_index');
        });

        Schema::table('device_activity_logs', function (Blueprint $table): void {
            $table->index(['station_id', 'occurred_at'], 'activity_logs_station_occurred_index');
        });
    }

    public function down(): void
    {
        Schema::table('device_activity_logs', function (Blueprint $table): void {
            $table->dropIndex('activity_logs_station_occurred_index');
        });

        Schema::table('sensor_readings', function (Blueprint $table): void {
            $table->dropIndex('sensor_readings_station_recorded_index');
        });

        Schema::table('drying_cycles', function (Blueprint $table): void {
            $table->dropIndex('drying_cycles_station_status_started_index');
            $table->dropIndex('drying_cycles_station_started_index');
        });
    }
};
