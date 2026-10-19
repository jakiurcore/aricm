<?php

namespace Modules\Fees\Contracts;

use Illuminate\Http\Request;
use Modules\Fees\Models\Payment;
use Modules\Fees\Support\GatewayResult;

/**
 * An online payment provider. A payment is approved only by `verify()`, which must ask the provider's own server
 * (never trust a browser redirect or an IPN body) and confirm success, the exact amount, BDT, and our reference.
 */
interface PaymentGateway
{
    public function name(): string;

    /**
     * Whether the credentials needed to talk to the provider are present.
     */
    public function isConfigured(): bool;

    /**
     * Register the payment with the provider and return the URL to send the payer to.
     * The driver stores whatever it needs to verify later in `gateway_payload`.
     */
    public function initiate(Payment $payment): string;

    /**
     * The payer's browser comes back. Find our payment (never from an amount in the request) and say what to do.
     */
    public function handleCallback(Request $request): GatewayResult;

    /**
     * A server-to-server notification, for providers that send one.
     */
    public function handleIpn(Request $request): GatewayResult;

    /**
     * Ask the provider whether this payment succeeded for exactly the amount we expect.
     */
    public function verify(Payment $payment): bool;
}
