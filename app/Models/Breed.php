<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Breed extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'category'];

    public function flocks(): HasMany
    {
        return $this->hasMany(Flock::class);
    }

    public function performanceStandards(): HasMany
    {
        return $this->hasMany(BreedPerformanceStandard::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('breeds') // goes into log_name
            ->logOnly([
                'name',
                'category',
            ])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Breed {$this->name} was {$eventName} by ".optional(auth()->user())->name
            );
    }
}
