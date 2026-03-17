<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'shed_id',
        'flock_id',
        'production_log_date',
        'age',
        'day_mortality_count',
        'night_mortality_count',
        'todate_mortality_count',
        'net_count',
        'livability',
        'day_feed_consumed',
        'night_feed_consumed',
        'todate_feed_consumed',
        'avg_feed_consumed',
        'day_water_consumed',
        'night_water_consumed',
        'todate_water_consumed',
        'avg_water_consumed',
        'day_medicine',
        'night_medicine',
        'is_vaccinated',
        'user_id',
    ];

    protected $casts = [
        'production_log_date' => 'datetime',
    ];

    public function flock(): BelongsTo
    {
        return $this->belongsTo(Flock::class);
    }

    public function shed(): BelongsTo
    {
        return $this->belongsTo(Shed::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function weightLog(): HasOne
    {
        return $this->hasOne(WeightLog::class);
    }
}
