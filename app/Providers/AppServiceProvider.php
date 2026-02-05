<?php

namespace App\Providers;

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
    public function boot(): void {}
}
