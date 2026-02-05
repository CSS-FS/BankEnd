<?php

namespace App\Models;

use Carbon\Carbon;
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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    /**
     * Scope for un-read alerts
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read alerts
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope for active alerts (not dismissed)
     */
    public function scopeActive($query)
    {
        return $query->where('is_dismissed', false);
    }

    /**
     * Scope for dismissed alerts
     */
    public function scopeDismissed($query)
    {
        return $query->where('is_dismissed', true);
    }

    public function scopeSeverity($query, string $level)
    {
        return $query->where('severity', $level);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for alerts by channel
     */
    public function scopeWithChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope for farm alerts
     */
    public function scopeForFarm($query, $farmId)
    {
        return $query->where('farm_id', $farmId);
    }

    /**
     * Scope for shed alerts
     */
    public function scopeForShed($query, $shedId)
    {
        return $query->where('shed_id', $shedId);
    }

    /**
     * Scope for flock alerts
     */
    public function scopeForFlock($query, $flockId)
    {
        return $query->where('flock_id', $flockId);
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

    /**
     * Check if alert is actionable (not dismissed and not resolved)
     */
    public function isActionable(): bool
    {
        return ! $this->is_dismissed &&
            $this->status !== 'resolved' &&
            $this->status !== 'cancelled';
    }

    /**
     * Get alert priority score (higher = more urgent)
     */
    public function getPriorityScore(): int
    {
        $severityScores = [
            'critical' => 100,
            'warning' => 70,
            'success' => 30,
            'info' => 10,
        ];

        $score = $severityScores[$this->severity] ?? 50;

        // Add points for unread
        if (! $this->is_read) {
            $score += 20;
        }

        // Add points for not dismissed
        if (! $this->is_dismissed) {
            $score += 15;
        }

        // Reduce points for age (older alerts less urgent)
        $ageInHours = $this->created_at?->diffInHours(Carbon::now());
        if ($ageInHours > 24) {
            $score -= min(30, ($ageInHours - 24));
        }

        return max(0, $score);
    }
}
