<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DeviceActivityLog Model
 *
 * Stores chronological hardware and automation events for a dehumidifier station,
 * such as helmet detection, relay activation, cycle completion, and standby transitions.
 */
class DeviceActivityLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'station_id',
        'event_type',
        'description',
        'occurred_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * The station associated with this activity log entry.
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
}
