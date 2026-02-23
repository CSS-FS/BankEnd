<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Country extends Model
{
    use LogsActivity;

    protected $fillable = [
        'country',
        'alpha_2_code',
        'alpha_3_code',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('countries') // goes into log_name
            ->logOnly([
                'country',
                'alpha_2_code',
                'alpha_3_code',
            ])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Country {$this->name} was {$eventName} by ".optional(auth()->user())->name
            );
    }
}
