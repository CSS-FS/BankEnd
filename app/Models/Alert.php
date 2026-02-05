<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alert extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'farm_id', 'shed_id', 'flock_id',
        'title', 'message', 'type', 'severity', 'channel', 'data',
        'status', 'scheduled_at', 'sent_at', 'is_read', 'read_at', 'is_dismissed', 'dismissed_at',
    ];

    protected $casts = [
        'data' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'is_dismissed' => 'boolean',
        'dismissed_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function shed(): BelongsTo
    {
        return $this->belongsTo(Shed::class);
    }

    public function flock(): BelongsTo
    {
        return $this->belongsTo(Flock::class);
    }

    /**
     * Get all responses for this alert
     */
    public function responses(): HasMany
    {
        return $this->hasMany(AlertResponse::class);
    }

    /**
     * Get the latest response for this alert
     */
    public function latestResponse(): HasOne
    {
        return $this->hasOne(AlertResponse::class)->latestOfMany();
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeSeverity($query, string $level)
    {
        return $query->where('severity', $level);
    }

    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Helpers
    public function markAsRead(): self
    {
        $this->forceFill(['is_read' => true, 'read_at' => now()])->save();

        return $this;
    }

    public function markAsUnread(): self
    {
        $this->forceFill(['is_read' => false, 'read_at' => null])->save();

        return $this;
    }

    public function dismiss(): self
    {
        $this->forceFill(['is_dismissed' => true, 'dismissed_at' => now()])->save();

        return $this;
    }

    public function undismiss(): self
    {
        $this->forceFill(['is_dismissed' => false, 'dismissed_at' => null])->save();

        return $this;
    }
}
