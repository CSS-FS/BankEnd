<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTopic extends Model
{
    protected $fillable = ['name', 'title', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
