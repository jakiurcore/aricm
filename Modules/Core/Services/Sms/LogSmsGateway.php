<?php

namespace Modules\Core\Services\Sms;

use Illuminate\Support\Facades\Log;
use Modules\Core\Contracts\SmsGateway;

class LogSmsGateway implements SmsGateway
{
    public function send(string $to, string $message): bool
    {
        Log::info('[sms:log] to '.$to.': '.$message);

        return true;
    }
}
