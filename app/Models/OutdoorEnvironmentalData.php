<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutdoorEnvironmentalData extends Model
{
    protected $table = 'outdoor_environmental_data';

    protected $fillable = [
        'recorded_at',
        'temperature',
        'humidity',
        'wind_speed',
        'pressure',
        'extra_metrics',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'temperature' => 'decimal:2',
        'humidity' => 'decimal:2',
        'wind_speed' => 'decimal:2',
        'pressure' => 'decimal:2',
        'extra_metrics' => 'array',
    ];
}
