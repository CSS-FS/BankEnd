<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class FarmStaff extends Pivot
{
    protected $table = 'farm_staff';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = ['farm_id', 'worker_id', 'link_date'];
}
