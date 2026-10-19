<?php

namespace Modules\Fees\Support;

use Modules\Fees\Models\Payment;

/**
 * What a callback or IPN found: our payment (null when the request did not identify one) and the outcome the browser
 * should be told about. It carries no verdict about money: that comes from the driver's verify().
 */
final readonly class GatewayResult
{
    public function __construct(public ?Payment $payment, public string $outcome = 'unknown') {}
}
