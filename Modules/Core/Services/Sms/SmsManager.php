<?php

namespace Modules\Core\Services\Sms;

use InvalidArgumentException;
use Modules\Core\Contracts\SmsGateway;

/**
 * Picks the SMS driver from config('modules.core.sms.driver') and sends through it.
 */
class SmsManager
{
    public function gateway(): SmsGateway
    {
        return match ($driver = config('modules.core.sms.driver', 'log')) {
            'log' => new LogSmsGateway,
            'bulk' => new BulkSmsGateway,
            default => throw new InvalidArgumentException("Unknown SMS driver [{$driver}]."),
        };
    }

    public function send(?string $to, string $message): bool
    {
        $number = self::normalize((string) $to);

        return $number !== null && $this->gateway()->send($number, $message);
    }

    /**
     * 01712345678 / +8801712345678 / 8801712345678 → 8801712345678; null when it is not a mobile number.
     */
    public static function normalize(string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', strtr($number, ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9']));

        if (str_starts_with($digits, '880')) {
            $digits = substr($digits, 2);
        }

        return preg_match('/^01[3-9]\d{8}$/', $digits) ? '88'.$digits : null;
    }
}
