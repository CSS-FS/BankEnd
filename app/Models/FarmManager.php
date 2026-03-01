<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class FarmManager extends Pivot
{
    protected $table = 'farm_managers';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = ['farm_id', 'manager_id', 'link_date'];
}
