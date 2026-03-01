<?php

namespace App\Providers;

use App\Models\Farm;
use App\Models\FarmManager;
use App\Models\FarmStaff;
use App\Observers\FarmManagerObserver;
use App\Observers\FarmObserver;
use App\Observers\FarmStaffObserver;
use App\Services\FcmService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FcmService::class, function () {
            return new FcmService(
                config('services.fcm.project_id'),
                base_path(config('services.fcm.sa_path'))
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Farm::observe(FarmObserver::class);
        FarmManager::observe(FarmManagerObserver::class);
        FarmStaff::observe(FarmStaffObserver::class);
    }
}
