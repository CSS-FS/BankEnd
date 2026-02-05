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
}
