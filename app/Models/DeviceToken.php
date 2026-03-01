<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Farm;

class DeviceToken extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'farm_id',
        'token',
        'platform',
        'device_id',
        'device_model',
        'app_version',
        'last_updated_at',
        'revoked_at',
        'last_error',
    ];

    protected $casts = [
        'last_updated_at' => 'datetime',
        'revoked_at'      => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    /**
     * User ke tamam device tokens ka farm_id ek saath update karo.
     * Farm unassign hone par null pass karo.
     */
    public static function syncFarmForUser(int $userId, ?int $farmId): void
    {
        static::where('user_id', $userId)->update(['farm_id' => $farmId]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('fcm_tokens')
            ->logOnly([
                'user_id',
                'farm_id',
                'token',
                'platform',
                'device_id',
                'device_model',
                'app_version',
                'last_updated_at',
                'revoked_at',
                'last_error',
            ])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "DeviceToken {$this->name} was {$eventName} by ".optional(auth()->user())->name
            );
    }
}
