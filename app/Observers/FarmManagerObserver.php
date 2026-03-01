<?php

namespace App\Observers;

use App\Models\DeviceToken;
use App\Models\FarmManager;

class FarmManagerObserver
{
    /**
     * Manager kisi farm se assign hua → us ke tokens update karo.
     */
    public function created(FarmManager $pivot): void
    {
        DeviceToken::syncFarmForUser($pivot->manager_id, $pivot->farm_id);
    }

    /**
     * Manager farm se remove hua → us ke tokens null karo.
     */
    public function deleted(FarmManager $pivot): void
    {
        DeviceToken::syncFarmForUser($pivot->manager_id, null);
    }
}
