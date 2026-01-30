<?php

namespace App\Policies;

class NotificationOutboxPolicy
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
        return $user->can('notifications.logs.view');
    }

    public function send($user): bool
    {
        return $user->can('notifications.send');
    }
}
