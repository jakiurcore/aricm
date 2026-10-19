<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| The single cPanel cron line is `* * * * * php /path/to/artisan schedule:run`. Everything below runs from it.
| The fee commands decide for themselves whether they have work (automatic generation and SMS reminders are off by default).
*/
Schedule::command('fees:generate-monthly')->monthlyOn(1, '06:00');
Schedule::command('fees:apply-late-fines')->dailyAt('01:00');
Schedule::command('fees:due-reminders')->dailyAt('09:00');

// Diary: the 6 PM summary to guardians and the reminder to teachers who have not written yet (both off until switched on in the diary settings).
Schedule::command('diary:send-daily-summary')->dailyAt('18:00');
