<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled jobs
|--------------------------------------------------------------------------
|
| Recurring billing lifecycle. `renewDue` issues renewal invoices for
| subscriptions whose term has ended (moves them to past_due with a grace
| window); `expire` revokes access for subscriptions whose grace window
| lapsed unpaid or that ended without auto-renew.
|
| Run `php artisan schedule:work` locally (or a cron entry pointing at
| `schedule:run`) to enable these.
*/

Schedule::call(function () {
    app(\Modules\CORE\Services\RecurringBillingService::class)->renewDue();
})->daily()->name('billing.renewals');

Schedule::call(function () {
    app(\Modules\CORE\Services\RecurringBillingService::class)->expire();
})->daily()->name('billing.expirations');
