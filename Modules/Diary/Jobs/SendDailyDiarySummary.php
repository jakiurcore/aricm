<?php

namespace Modules\Diary\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Modules\Diary\Services\DailyDiarySummary;

class SendDailyDiarySummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $date) {}

    public function handle(DailyDiarySummary $summary): void
    {
        $summary->send(Carbon::parse($this->date));
    }
}
