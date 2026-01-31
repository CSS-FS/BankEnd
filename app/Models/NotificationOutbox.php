<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationOutbox extends Model
{
    protected $table = 'notification_outbox';

    protected $fillable = [
        'target_type',
        'target_id',
        'target_topic',
        'title',
        'body',
        'data',
        'scheduled_at',
        'sent_at', 'status',
        'attempts',
        'max_attempts',
        'next_retry_at',
        'last_error',
    ];

    protected $casts = [
        'data' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];
}
