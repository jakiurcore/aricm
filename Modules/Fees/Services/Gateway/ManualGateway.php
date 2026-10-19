<?php

namespace Modules\Fees\Services\Gateway;

use Illuminate\Http\Request;
use LogicException;
use Modules\Fees\Contracts\PaymentGateway;
use Modules\Fees\Models\Payment;
use Modules\Fees\Support\GatewayResult;

/**
 * The "no gateway" driver: guardians pay by mobile wallet or bank and a person verifies it.
 */
class ManualGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'manual';
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function initiate(Payment $payment): string
    {
        throw new LogicException('The manual driver has no online checkout.');
    }

    public function handleCallback(Request $request): GatewayResult
    {
        return new GatewayResult(null);
    }

    public function handleIpn(Request $request): GatewayResult
    {
        return new GatewayResult(null);
    }

    public function verify(Payment $payment): bool
    {
        return false;
    }
}
