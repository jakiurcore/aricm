<?php

namespace Modules\Fees\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Modules\Fees\Services\Gateway\GatewayManager;
use Modules\Fees\Services\GatewayPayments;

/**
 * Where providers send the payer's browser (callback) and their servers (IPN). These routes are excluded from CSRF
 * because providers cannot send a token; everything they say is re-checked with the provider before any money is
 * recorded, so a forged request can at worst trigger a (harmless) verification.
 */
class GatewayCallbackController
{
    public function callback(Request $request, string $driver, GatewayManager $gateways, GatewayPayments $payments): RedirectResponse
    {
        try {
            $result = $gateways->driver($driver)->handleCallback($request);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        $status = 'failed';

        if ($result->payment) {
            if (in_array($result->outcome, ['fail', 'cancel'], true)) {
                $payments->fail($result->payment, $result->outcome);
            } else {
                $status = $payments->settle($result->payment)->status->value;
            }
        }

        $target = Route::has('portal.fees') ? route('portal.fees') : url('/');

        return redirect($target)->with('payment_status', $status);
    }

    public function ipn(Request $request, string $driver, GatewayManager $gateways, GatewayPayments $payments): JsonResponse
    {
        try {
            $result = $gateways->driver($driver)->handleIpn($request);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        if ($result->payment) {
            $payments->settle($result->payment);
        }

        return response()->json(['received' => true]);
    }
}
