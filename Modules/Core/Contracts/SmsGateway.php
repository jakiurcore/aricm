<?php

namespace Modules\Core\Contracts;

/**
 * Sends one SMS. Drivers: "log" (default, writes to the log) and a placeholder for a Bangladeshi bulk-SMS provider.
 */
interface SmsGateway
{
    /**
     * @param  string  $to  A Bangladeshi mobile number (01XXXXXXXXX or 8801XXXXXXXXX).
     * @return bool Whether the provider accepted the message.
     */
    public function send(string $to, string $message): bool;
}
