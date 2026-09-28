<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Satu cron Laravel menjalankan backup pada jam yang dapat diatur dari .env.
Schedule::command('geartrack:backup')
    ->dailyAt((string) config('backup.automatic_time', '01:30'))
    ->withoutOverlapping(120);
