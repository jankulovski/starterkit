<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Monthly credits are handled via Stripe webhook (invoice.payment_succeeded)
// This scheduled job runs daily as a backup to catch any missed webhook allocations
Schedule::command('billing:reset-monthly-credits')->daily()->at('03:00');

// Send plan change reminders to users with upcoming plan changes (within 3 days)
Schedule::command('billing:send-plan-change-reminders')->daily()->at('09:00');

// Check webhook health and alert if issues are detected
Schedule::command('billing:check-webhook-health --alert')->daily()->at('10:00');
