<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule monthly credit reset (runs on the 1st of each month at midnight)
Schedule::command('billing:reset-monthly-credits')
    ->monthly()
    ->at('00:00')
    ->timezone('UTC');
