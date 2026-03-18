<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DeviceEvent extends Model
{
    use LogsActivity;

    protected $fillable = [
        'device_id',
        'event_type',
        'severity',
        'details',
        'occurred_at',
    ];

    protected $casts = [
        'details' => 'array',
        'occurred_at' => 'datetime',
    ];

    // Relationship to device
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('device_events')
            ->logOnly(['device_id', 'event_type', 'severity', 'details', 'occurred_at'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(function (string $eventName) {
                $data = is_array($this->details) ? $this->details : json_decode($this->details, true) ?? [];
                $device = Device::find($this->device_id);
                $shed = Shed::with('farm')->find($data['shed_id'] ?? null);

                $desc = "Device Event [{$this->event_type}] ({$this->severity}) was {$eventName}";
                if ($device) $desc .= " | Device: {$device->serial_no}";
                if ($shed) $desc .= " | Shed: {$shed->name} | Farm: {$shed->farm?->name}";
                $desc .= " by " . optional(auth()->user())->name;

                return $desc;
            });
    }
}
