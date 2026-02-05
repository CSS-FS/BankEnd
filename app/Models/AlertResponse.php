<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertResponse extends Model
{
    protected $fillable = [
        'alert_id',
        'creator_id',
        'responder_id',
        'responded_at',
        'action_type',
        'action_details',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responder_id');
    }

    public function scopePending($query)
    {
        return $query->whereNull('responded_at');
    }

    /**
     * Scope for specific action types
     */
    public function scopeWithActionType($query, $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Scope for responses after a certain date
     */
    public function scopeAfter($query, $date)
    {
        return $query->where('responded_at', '>=', $date);
    }

    /**
     * Scope for responses before a certain date
     */
    public function scopeBefore($query, $date)
    {
        return $query->where('responded_at', '<=', $date);
    }

    /**
     * Check if response resolves the alert
     */
    public function isResolving(): bool
    {
        return $this->action_type === 'Resolved';
    }

    /**
     * Check if response escalates the alert
     */
    public function isEscalating(): bool
    {
        return $this->action_type === 'Escalated';
    }
}
