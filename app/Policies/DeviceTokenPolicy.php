<?php

namespace App\Policies;

use App\Models\User;

class DeviceTokenPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function viewAny($user): bool
    {
        return $user->can('device_tokens.view');
    }

    public function manage($user): bool
    {
        return $user->can('device_tokens.manage');
    }
}
