<?php

namespace App\Models;

// use App\Models\Scopes\FarmScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\FarmManager;
use App\Models\FarmStaff;

class Farm extends Model
{
    use HasFactory;
    use LogsActivity;

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        //        static::addGlobalScope(new FarmScope);
    }

    protected $fillable = [
        'name',
        'province_id',
        'district_id',
        'city_id',
        'address',
        'owner_id',
        'country',
        'phone_number',
        'contact_person',
        'alerts',
        'notifications',
    ];

    protected $casts = [
        'alerts'        => 'boolean',
        'notifications' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id')
            ->withDefault([
                'name' => '---',
                'email' => '---',
            ]);
    }

    public function sheds(): HasMany
    {
        return $this->hasMany(Shed::class);
    }

    public function managers()
    {
        return $this->belongsToMany(User::class, 'farm_managers', 'farm_id', 'manager_id')
            ->using(FarmManager::class)
            ->withPivot('link_date');
    }

    /**
     * Get the latest manager linked to this farm.
     */
    public function latestManager()
    {
        return $this->managers()
            ->orderByPivot('link_date', 'desc')
            ->first();
    }

    public function staff()
    {
        return $this->belongsToMany(User::class, 'farm_staff', 'farm_id', 'worker_id')
            ->using(FarmStaff::class)
            ->withPivot('link_date');
    }

    public function getFlocksCountAttribute(): int
    {
        $count = 0;

        foreach ($this->sheds as $shed) {
            // Latest flock per shed
            $latestFlock = $shed->flocks->sortByDesc('id')->first();

            // Count only if active (no end_date)
            if ($latestFlock && is_null($latestFlock->end_date)) {
                $count++;
            }
        }

        return $count;
    }

    public function getBirdsCountAttribute(): int
    {
        if (! $this->relationLoaded('sheds')) {
            return 0;
        }

        $totalBirds = 0;

        foreach ($this->sheds as $shed) {
            // Get the latest flock in this shed
            $latestFlock = $shed->flocks->sortByDesc('id')->first();

            // Only count if the flock is active (no end_date)
            if ($latestFlock && is_null($latestFlock->end_date)) {
                $totalBirds += $latestFlock->chicken_count;
            }
        }

        return $totalBirds;
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(Tehsil::class, 'city_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('farms') // goes into log_name
            ->logOnly([
                'name',
                'province_id',
                'district_id',
                'city_id',
                'address',
                'owner_id',
                'country',
                'phone_number',
                'contact_person',
                'alerts',
                'notifications',
            ])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Farm {$this->name} was {$eventName} by ".optional(auth()->user())->name);
    }
}
