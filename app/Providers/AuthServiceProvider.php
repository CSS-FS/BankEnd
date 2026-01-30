<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\DeviceToken;
use App\Models\NotificationTopic;
use App\Models\NotificationOutbox;
use App\Policies\DeviceTokenPolicy;
use App\Policies\NotificationTopicPolicy;
use App\Policies\NotificationOutboxPolicy;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $policies = [
            DeviceToken::class => DeviceTokenPolicy::class,
            NotificationTopic::class => NotificationTopicPolicy::class,
            NotificationOutbox::class => NotificationOutboxPolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}
