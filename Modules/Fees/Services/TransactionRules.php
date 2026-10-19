<?php

namespace Modules\Fees\Services;

use Modules\Fees\Enums\PaymentMethod;

/**
 * The shape a transaction id must have for each manual payment method, so obvious typos are caught before a
 * human has to look at the payment.
 */
final class TransactionRules
{
    public static function pattern(PaymentMethod $method): ?string
    {
        return match ($method) {
            PaymentMethod::Bkash, PaymentMethod::Nagad => '/^[A-Z0-9]{8,12}$/',
            PaymentMethod::Rocket => '/^[A-Z0-9]{8,14}$/',
            PaymentMethod::Bank => '/^[A-Z0-9\-\/]{4,30}$/',
            default => null,
        };
    }

    public static function normalize(string $transactionId): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($transactionId)));
    }

    public static function isValid(PaymentMethod $method, string $transactionId): bool
    {
        $pattern = self::pattern($method);

        return $pattern === null || preg_match($pattern, self::normalize($transactionId)) === 1;
    }

    public static function needsSender(PaymentMethod $method): bool
    {
        return in_array($method, [PaymentMethod::Bkash, PaymentMethod::Nagad, PaymentMethod::Rocket], true);
    }
}
