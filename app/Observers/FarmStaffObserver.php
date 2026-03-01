<?php

namespace App\Observers;

use App\Models\DeviceToken;
use App\Models\FarmStaff;

class FarmStaffObserver
{
    /**
     * Worker kisi farm se assign hua → us ke tokens update karo.
     */
    public function created(FarmStaff $pivot): void
    {
        DeviceToken::syncFarmForUser($pivot->worker_id, $pivot->farm_id);
    }

    /**
     * Worker farm se remove hua → us ke tokens null karo.
     */
    public function deleted(FarmStaff $pivot): void
    {
        DeviceToken::syncFarmForUser($pivot->worker_id, null);
    }
}
