<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SensorReading Model
 *
 * Represents a single time-series telemetry reading captured by the ESP32
 * from the DHT22 (temperature & relative humidity) and HC-SR04 (ultrasonic distance)
 * sensors, transmitted via MQTT or HTTP webhook.
 */
class SensorReading extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'station_id',
        'drying_cycle_id',
        'humidity',
        'temperature',
        'distance_cm',
        'fan_is_on',
        'led_status',
        'recorded_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'humidity' => 'float',
            'temperature' => 'float',
            'distance_cm' => 'float',
            'fan_is_on' => 'boolean',
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * The station that produced this sensor reading.
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /**
     * The drying cycle during which this reading was captured, if any.
     */
    public function dryingCycle(): BelongsTo
    {
        return $this->belongsTo(DryingCycle::class);
    }
}
