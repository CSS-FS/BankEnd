<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicSubscription extends Model
{
    protected $guarded = [];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(NotificationTopic::class);
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(DeviceToken::class);
    }
}
