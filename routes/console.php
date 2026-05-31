<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('mail:collect-logs --path=storage/logs/mailserver-docker.log --limit=30000')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('mail:aggregate-stats')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('mail:queue-snapshot')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('mail:check-health')->everyFiveMinutes()->withoutOverlapping();
