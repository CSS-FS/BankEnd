<?php

namespace App\Providers;

use App\Events\NotificationTriggered;
use App\Events\ParameterThresholdBreached;
use App\Listeners\CreateGenericNotification;
use App\Listeners\SendParameterAlertNotification;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Events\PasswordReset;
use App\Listeners\SendPasswordResetSuccessNotification;
use App\Listeners\SendEmailVerifiedNotification;

class EventServiceProvider extends ServiceProvider
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
        Event::listen(
            Registered::class,
            SendEmailVerificationNotification::class,
        );

        Event::listen(
            Verified::class,
            SendEmailVerifiedNotification::class,
        );

        Event::listen(
            PasswordReset::class,
            SendPasswordResetSuccessNotification::class,
        );

        Event::listen(
            NotificationTriggered::class,
            CreateGenericNotification::class
        );

        Event::listen(
            ParameterThresholdBreached::class,
            SendParameterAlertNotification::class
        );
    }
}
