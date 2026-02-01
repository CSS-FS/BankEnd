<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('weather:fetch')->everyFifteenMinutes();

Schedule::command('iot:aggregate-data')->hourly();

Schedule::command('push:notification --limit=200')->everyMinute()->withoutOverlapping();


