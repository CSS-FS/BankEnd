<?php

namespace App\Observers;

use App\Models\DeviceToken;
use App\Models\Farm;

class FarmObserver
{
    /**
     * Owner ne farm banayi → us ke device tokens farm se link karo.
     */
    public function created(Farm $farm): void
    {
        if ($farm->owner_id) {
            DeviceToken::syncFarmForUser($farm->owner_id, $farm->id);
        }
    }

    /**
     * Handle the Farm "updated" event.
     */
    public function updated(Farm $farm): void
    {
        //
    }

    /**
     * Handle the Farm "deleted" event.
     */
    public function deleted(Farm $farm): void
    {
        //
    }

    /**
     * Handle the Farm "restored" event.
     */
    public function restored(Farm $farm): void
    {
        //
    }

    /**
     * Handle the Farm "force deleted" event.
     */
    public function forceDeleted(Farm $farm): void
    {
        //
    }
}
