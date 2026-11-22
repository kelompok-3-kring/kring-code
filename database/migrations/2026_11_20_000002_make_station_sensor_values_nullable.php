<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table): void {
            $table->decimal('current_humidity', 5, 2)->nullable()->change();
            $table->decimal('current_temperature', 5, 2)->nullable()->change();
            $table->decimal('current_distance_cm', 5, 2)->nullable()->change();
            $table->string('wifi_ssid')->nullable()->change();
            $table->integer('wifi_rssi')->nullable()->change();
            $table->string('firmware_version')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table): void {
            $table->decimal('current_humidity', 5, 2)->default(61.00)->change();
            $table->decimal('current_temperature', 5, 2)->default(31.40)->change();
            $table->decimal('current_distance_cm', 5, 2)->default(400.00)->change();
        });
    }
};
