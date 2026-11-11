<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * DryingCycle Model
 *
 * Represents a single helmet dehumidification cycle initiated when a helmet is
 * detected and internal relative humidity exceeds the configured upper threshold.
 */
class DryingCycle extends Model
{
    use HasFactory;

    /**
     * Indonesian short month names for UI formatting (matching design: `26 Okt 2026`).
     *
     * @var array<int, string>
     */
    public const INDONESIAN_SHORT_MONTHS = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'Mei',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Agu',
        9 => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'station_id',
        'started_at',
        'ended_at',
        'duration_minutes',
        'initial_humidity',
        'final_humidity',
        'avg_temperature',
        'mode',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_minutes' => 'integer',
            'initial_humidity' => 'float',
            'final_humidity' => 'float',
            'avg_temperature' => 'float',
        ];
    }

    /**
     * The station that recorded this drying cycle.
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /**
     * Sensor readings captured during this drying cycle.
     */
    public function sensorReadings(): HasMany
    {
        return $this->hasMany(SensorReading::class);
    }

    /**
     * Format date with year in Indonesian short format (e.g., `26 Okt 2026`).
     */
    public function getFormattedDateAttribute(): string
    {
        /** @var Carbon $date */
        $date = $this->started_at;
        $month = self::INDONESIAN_SHORT_MONTHS[(int) $date->format('n')] ?? $date->format('M');

        return sprintf('%s %s %s', $date->format('d'), $month, $date->format('Y'));
    }

    /**
     * Format date without year in Indonesian short format (e.g., `26 Okt`).
     */
    public function getShortDateAttribute(): string
    {
        /** @var Carbon $date */
        $date = $this->started_at;
        $month = self::INDONESIAN_SHORT_MONTHS[(int) $date->format('n')] ?? $date->format('M');

        return sprintf('%s %s', $date->format('d'), $month);
    }

    /**
     * Format start time as `HH:mm` (e.g., `08:04`).
     */
    public function getStartTimeAttribute(): string
    {
        return $this->started_at ? $this->started_at->format('H:i') : '—';
    }

    /**
     * Format end time as `HH:mm` or `—` when still running.
     */
    public function getEndTimeAttribute(): string
    {
        if ($this->status === 'running' || ! $this->ended_at) {
            return '—';
        }

        return $this->ended_at->format('H:i');
    }

    /**
     * Human-readable status label in Indonesian (`Berjalan`, `Selesai`, `Dihentikan`).
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'running' => 'Berjalan',
            'completed' => 'Selesai',
            default => 'Dihentikan',
        };
    }

    /**
     * Calculate relative humidity reduction points (`initial_humidity - final_humidity`).
     */
    public function getHumidityDropAttribute(): float
    {
        if ($this->final_humidity === null) {
            return 0.0;
        }

        return max(0.0, (float) $this->initial_humidity - (float) $this->final_humidity);
    }
}
