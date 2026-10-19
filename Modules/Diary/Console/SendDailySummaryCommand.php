<?php

namespace Modules\Diary\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Modules\Diary\Jobs\SendDailyDiarySummary;

class SendDailySummaryCommand extends Command
{
    protected $signature = 'diary:send-daily-summary {--date= : The day to summarise (default: today)}';

    protected $description = 'Send the 6 PM diary SMS to guardians and the reminder to teachers (when enabled in the diary settings)';

    public function handle(): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();

        SendDailyDiarySummary::dispatch($date->toDateString());
        $this->info('Diary summary queued for '.$date->toDateString().'.');

        return self::SUCCESS;
    }
}
