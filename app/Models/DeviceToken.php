<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DeviceToken extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'device_id',
        'device_model',
        'app_version',
        'last_seen_at',
        'revoked_at',
        'last_error',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('fcm_tokens') // goes into log_name
            ->logOnly([
                'user_id',
                'token',
                'platform',
                'device_id',
                'device_model',
                'app_version',
                'last_seen_at',
                'revoked_at',
                'last_error',
            ])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "DeviceToken {$this->name} was {$eventName} by ".optional(auth()->user())->name
            );
    }
}
