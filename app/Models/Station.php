<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Station Model
 *
 * Represents a physical ESP32 Helmet Care Dehumidifier Station.
 * Stores real-time telemetry snapshots (DHT22 & HC-SR04), actuator status
 * (Relay fan & LED indicator), network metadata, and hysteresis threshold settings.
 */
class Station extends Model
{
    use HasFactory;

    /**
     * Default factory threshold settings for a station.
     */
    public const DEFAULT_THRESHOLDS = [
        'fan_on_humidity' => 65,
        'fan_off_humidity' => 55,
        'detection_distance_cm' => 15,
        'data_interval_seconds' => 2,
        'auto_mode' => true,
        'notify_on_complete' => false,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'mcu_type',
        'status',
        'drying_state',
        'current_humidity',
        'current_temperature',
        'current_distance_cm',
        'helmet_detected',
        'fan_is_on',
        'fan_turned_on_at',
        'led_status',
        'ultrasonic_status',
        'dht_status',
        'wifi_ssid',
        'wifi_rssi',
        'server_endpoint',
        'firmware_version',
        'last_seen_at',
        'fan_on_humidity',
        'fan_off_humidity',
        'detection_distance_cm',
        'data_interval_seconds',
        'auto_mode',
        'notify_on_complete',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_humidity' => 'float',
            'current_temperature' => 'float',
            'current_distance_cm' => 'float',
            'helmet_detected' => 'boolean',
            'fan_is_on' => 'boolean',
            'fan_turned_on_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'fan_on_humidity' => 'integer',
            'fan_off_humidity' => 'integer',
            'detection_distance_cm' => 'integer',
            'data_interval_seconds' => 'integer',
            'auto_mode' => 'boolean',
            'notify_on_complete' => 'boolean',
        ];
    }

    /**
     * All drying cycles recorded by this station.
     */
    public function dryingCycles(): HasMany
    {
        return $this->hasMany(DryingCycle::class)->orderByDesc('started_at');
    }

    /**
     * The currently running drying cycle, if any.
     */
    public function activeCycle(): HasOne
    {
        return $this->hasOne(DryingCycle::class)
            ->where('status', 'running')
            ->latestOfMany('started_at');
    }

    /**
     * Time-series sensor readings from DHT22 and HC-SR04.
     */
    public function sensorReadings(): HasMany
    {
        return $this->hasMany(SensorReading::class)->orderByDesc('recorded_at');
    }

    /**
     * Hardware and automation activity logs for this station.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(DeviceActivityLog::class)->orderByDesc('occurred_at');
    }

    /**
     * Human-readable Indonesian label for the LED indicator state.
     */
    public function getLedLabelAttribute(): string
    {
        return match ($this->led_status) {
            'red_blinking' => 'Merah berkedip',
            'green_solid' => 'Hijau menyala',
            default => 'Mati',
        };
    }

    /**
     * Human-readable description of the LED mode.
     */
    public function getLedModeDescriptionAttribute(): string
    {
        return match ($this->led_status) {
            'red_blinking' => 'Mode pengeringan',
            'green_solid' => 'Helm kering, siap dipakai',
            default => 'Standby, tidak ada helm',
        };
    }

    /**
     * Human-readable station drying state badge text.
     */
    public function getDryingStateLabelAttribute(): string
    {
        return match ($this->drying_state) {
            'drying' => 'Sedang Mengeringkan',
            'completed' => 'Selesai Kering',
            default => 'Standby',
        };
    }

    /**
     * Calculate progress percentage (0 to 100) toward the target humidity `< fan_off_humidity`.
     */
    public function calculateDryingProgress(?DryingCycle $cycle = null): int
    {
        $cycle ??= $this->activeCycle;

        if (! $cycle) {
            return $this->current_humidity <= $this->fan_off_humidity ? 100 : 0;
        }

        $initial = (float) $cycle->initial_humidity;
        $target = (float) $this->fan_off_humidity;
        $current = (float) $this->current_humidity;

        if ($initial <= $target) {
            return 100;
        }

        // In the mockup, 79% -> 61% toward <55% shows ~75% visual bar fill
        $totalDropNeeded = $initial - $target;
        $currentDrop = max(0.0, $initial - $current);
        $percentage = (int) round(($currentDrop / $totalDropNeeded) * 100);

        return max(5, min(100, $percentage));
    }
}
