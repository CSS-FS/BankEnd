<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreedPerformanceStandard extends Model
{
    protected $fillable = [
        'breed_id',
        'type',
        'day',
        'weight_g',
        'daily_gain_g',
        'avg_daily_gain_g',
        'daily_intake_g',
        'cum_intake_g',
        'fcr',
    ];

    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    /**
     * Get the standard for a specific breed, day, and type.
     */
    public static function getStandard(int $breedId, int $day, string $type = 'as_hatched'): ?self
    {
        return static::where('breed_id', $breedId)
            ->where('type', $type)
            ->where('day', $day)
            ->first();
    }
}
