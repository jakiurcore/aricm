<?php

namespace Modules\Fees\Services\Gateway;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Fees\Contracts\PaymentGateway;
use Modules\Fees\Models\Payment;
use Modules\Fees\Support\GatewayResult;
use RuntimeException;

/**
 * bKash Tokenized Checkout, without agreement. Written from bKash's current documentation
 * (https://developer.bka.sh/docs): grant token → create payment → payer approves on bKash → execute payment, with
 * query payment as the fallback. The callback query string is not documented, so it is never trusted: our own
 * reference rides in the callback URL (`ref`), and the verdict comes from bKash's execute/query response matched
 * against our amount, BDT and our invoice number.
 *
 * Unverified against a live sandbox here (no credentials): confirm the `Authorization` header form and the production
 * base URL with bKash before going live. The token is cached because the grant API is limited to two calls an hour.
 */
class BkashGateway implements PaymentGateway
{
    private const array BASE = [
        'sandbox' => 'https://tokenized.sandbox.bka.sh/v2/tokenized-checkout',
        'live' => 'https://tokenized.pay.bka.sh/v2/tokenized-checkout',
    ];

    private const string TOKEN_CACHE = 'fees.bkash.token';

    public function name(): string
    {
        return 'bkash';
    }

    public function isConfigured(): bool
    {
        return filled($this->config('app_key')) && filled($this->config('app_secret')) && filled($this->config('username')) && filled($this->config('password'));
    }

    public function initiate(Payment $payment): string
    {
        $payment->loadMissing('student');

        $data = $this->call('payment/create', [
            'payerReference' => $payment->student->guardian_phone ?: $payment->student->student_id,
            'callbackURL' => route('fees.gateway.callback', ['driver' => $this->name(), 'outcome' => 'return', 'ref' => $payment->idempotency_key]),
            'amount' => number_format($payment->amount->toTaka(), 2, '.', ''),
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => $payment->idempotency_key,
        ]);

        $url = $data['bKashURL'] ?? $data['bkashURL'] ?? null;

        if (blank($url) || blank($data['paymentId'] ?? null)) {
            Log::warning('[bkash] create failed: '.($data['errorMessageEn'] ?? 'no payment id'));

            throw new RuntimeException(__('fees::messages.gateway_unavailable'));
        }

        $payment->forceFill(['gateway' => $this->name(), 'gateway_payload' => ['payment_id' => $data['paymentId']]])->save();

        return $url;
    }

    public function handleCallback(Request $request): GatewayResult
    {
        // The query string bKash appends is undocumented: only our own `ref` is used to find the payment.
        $payment = Payment::query()->where(['gateway' => $this->name(), 'idempotency_key' => (string) $request->query('ref')])->first();

        return new GatewayResult($payment, 'return');
    }

    public function handleIpn(Request $request): GatewayResult
    {
        return new GatewayResult(null);
    }

    /**
     * Execute the payment (once, and only after the payer came back), then fall back to Query Payment if the execute
     * did not give a clear answer.
     */
    public function verify(Payment $payment): bool
    {
        $paymentId = $payment->gateway_payload['payment_id'] ?? null;

        if (blank($paymentId)) {
            return false;
        }

        $result = null;

        if (empty($payment->gateway_payload['executed'])) {
            // A payment id can be executed only once, whatever the outcome: remember that we tried.
            $payment->forceFill(['gateway_payload' => [...$payment->gateway_payload, 'executed' => true]])->save();

            try {
                $result = $this->call('payment/execute', ['paymentId' => $paymentId]);
            } catch (ConnectionException) {
                $result = null;
            }
        }

        if (! $this->isCompleted($result, $payment)) {
            try {
                $result = $this->call('query/payment', ['paymentId' => $paymentId]);
            } catch (ConnectionException) {
                return false;
            }
        }

        if (! $this->isCompleted($result, $payment)) {
            return false;
        }

        $payment->forceFill(['transaction_id' => $result['trxId'] ?? null])->save();

        return true;
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    protected function isCompleted(?array $data, Payment $payment): bool
    {
        return is_array($data)
            && ($data['transactionStatus'] ?? null) === 'Completed'
            && ($data['currency'] ?? null) === 'BDT'
            && is_numeric($data['amount'] ?? null) && (int) round((float) $data['amount'] * 100) === $payment->amount->poisha
            && ($data['merchantInvoiceNumber'] ?? $data['merchantInvoice'] ?? null) === $payment->idempotency_key
            && filled($data['trxId'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    protected function call(string $path, array $body): array
    {
        return Http::timeout(30)->acceptJson()->withHeaders(['Authorization' => $this->token(), 'X-APP-Key' => $this->config('app_key')])
            ->post($this->base().'/'.$path, $body)->json() ?? [];
    }

    protected function token(): string
    {
        return Cache::remember(self::TOKEN_CACHE, now()->addMinutes(50), function (): string {
            $data = Http::timeout(30)->acceptJson()->withHeaders(['username' => $this->config('username'), 'password' => $this->config('password')])
                ->post($this->base().'/auth/grant-token', ['app_key' => $this->config('app_key'), 'app_secret' => $this->config('app_secret')])->json() ?? [];

            if (blank($data['id_token'] ?? null)) {
                throw new RuntimeException(__('fees::messages.gateway_unavailable'));
            }

            return $data['id_token'];
        });
    }

    protected function base(): string
    {
        return self::BASE[$this->config('sandbox') ? 'sandbox' : 'live'];
    }

    protected function config(string $key): mixed
    {
        return config("modules.fees.gateway.bkash.{$key}");
    }
}
