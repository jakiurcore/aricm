<?php

namespace Modules\Fees\Services\Gateway;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Fees\Contracts\PaymentGateway;
use Modules\Fees\Models\Payment;
use Modules\Fees\Support\GatewayResult;
use RuntimeException;

/**
 * SSLCOMMERZ (v4 hosted checkout). Written from the provider's current documentation
 * (https://developer.sslcommerz.com/doc/v4/): a session is created with `store_id`/`store_passwd`, the payer is sent to
 * `GatewayPageURL`, and a payment counts only after the Order Validation API confirms `status` VALID/VALIDATED, our
 * `tran_id`, `currency_type` BDT and the exact amount. Test it against the sandbox before using live credentials.
 */
class SslCommerzGateway implements PaymentGateway
{
    private const array ENDPOINTS = [
        'sandbox' => ['session' => 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php', 'validate' => 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php'],
        'live' => ['session' => 'https://securepay.sslcommerz.com/gwprocess/v4/api.php', 'validate' => 'https://securepay.sslcommerz.com/validator/api/validationserverAPI.php'],
    ];

    public function name(): string
    {
        return 'sslcommerz';
    }

    public function isConfigured(): bool
    {
        return filled($this->config('store_id')) && filled($this->config('store_password'));
    }

    public function initiate(Payment $payment): string
    {
        $payment->loadMissing('student.schoolClass');
        $student = $payment->student;
        $callback = fn (string $outcome): string => route('fees.gateway.callback', ['driver' => $this->name(), 'outcome' => $outcome]);

        $response = Http::asForm()->timeout(30)->post($this->endpoint('session'), [
            'store_id' => $this->config('store_id'),
            'store_passwd' => $this->config('store_password'),
            'total_amount' => number_format($payment->amount->toTaka(), 2, '.', ''),
            'currency' => 'BDT',
            'tran_id' => $payment->idempotency_key,
            'success_url' => $callback('success'), 'fail_url' => $callback('fail'), 'cancel_url' => $callback('cancel'),
            'ipn_url' => route('fees.gateway.ipn', ['driver' => $this->name()]),
            'cus_name' => $student->name_en ?: $student->name_bn, 'cus_email' => config('mail.from.address') ?: 'noreply@example.com',
            'cus_add1' => 'Gaibandha', 'cus_city' => 'Gaibandha', 'cus_postcode' => '5700', 'cus_country' => 'Bangladesh',
            'cus_phone' => $student->guardian_phone ?: '01700000000',
            'shipping_method' => 'NO', 'num_of_item' => 1,
            'product_name' => 'School fees', 'product_category' => 'Education', 'product_profile' => 'non-physical-goods',
            'value_a' => (string) $student->student_id,
        ]);

        $data = $response->json() ?? [];

        if (($data['status'] ?? null) !== 'SUCCESS' || blank($data['GatewayPageURL'] ?? null)) {
            Log::warning('[sslcommerz] session failed: '.($data['failedreason'] ?? $response->status()));

            throw new RuntimeException(__('fees::messages.gateway_unavailable'));
        }

        $payment->forceFill(['gateway' => $this->name(), 'gateway_payload' => ['session_key' => $data['sessionkey'] ?? null]])->save();

        return $data['GatewayPageURL'];
    }

    public function handleCallback(Request $request): GatewayResult
    {
        return $this->resultFrom($request, (string) $request->route('outcome', 'unknown'));
    }

    public function handleIpn(Request $request): GatewayResult
    {
        return $this->resultFrom($request, 'ipn');
    }

    /**
     * Success only when the Validation API agrees on everything we know independently of the request.
     */
    public function verify(Payment $payment): bool
    {
        $valId = $payment->gateway_payload['val_id'] ?? null;

        if (blank($valId)) {
            return false;
        }

        try {
            $data = Http::timeout(30)->get($this->endpoint('validate'), [
                'val_id' => $valId, 'store_id' => $this->config('store_id'), 'store_passwd' => $this->config('store_password'), 'format' => 'json',
            ])->json() ?? [];
        } catch (ConnectionException) {
            return false;
        }

        return in_array($data['status'] ?? null, ['VALID', 'VALIDATED'], true)
            && ($data['tran_id'] ?? null) === $payment->idempotency_key
            && ($data['currency_type'] ?? null) === 'BDT'
            && is_numeric($data['amount'] ?? null)
            && (int) round((float) $data['amount'] * 100) === $payment->amount->poisha
            && (string) ($data['risk_level'] ?? '0') !== '1';
    }

    protected function resultFrom(Request $request, string $outcome): GatewayResult
    {
        $payment = Payment::query()->where(['gateway' => $this->name(), 'idempotency_key' => (string) $request->input('tran_id')])->first();

        if ($payment && filled($request->input('val_id')) && empty($payment->gateway_payload['val_id'])) {
            $payment->forceFill(['gateway_payload' => [...($payment->gateway_payload ?? []), 'val_id' => (string) $request->input('val_id')]])->save();
        }

        return new GatewayResult($payment, $outcome);
    }

    protected function endpoint(string $key): string
    {
        return self::ENDPOINTS[$this->config('sandbox') ? 'sandbox' : 'live'][$key];
    }

    protected function config(string $key): mixed
    {
        return config("modules.fees.gateway.sslcommerz.{$key}");
    }
}
