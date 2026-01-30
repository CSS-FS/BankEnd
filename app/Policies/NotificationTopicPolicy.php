<?php

namespace App\Policies;

class NotificationTopicPolicy
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
        return $user->can('topics.view');
    }

    public function manage($user): bool
    {
        return $user->can('topics.manage');
    }
}
