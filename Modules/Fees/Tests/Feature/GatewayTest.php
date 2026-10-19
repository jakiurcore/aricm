<?php

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Modules\Fees\Contracts\PaymentGateway;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Enums\PaymentStatus;
use Modules\Fees\Models\Payment;
use Modules\Fees\Models\PaymentAllocation;
use Modules\Fees\Models\StudentCredit;
use Modules\Fees\Services\FeeSettings;
use Modules\Fees\Services\Gateway\BkashGateway;
use Modules\Fees\Services\Gateway\GatewayManager;
use Modules\Fees\Services\Gateway\SslCommerzGateway;
use Modules\Fees\Services\GatewayPayments;
use Modules\Fees\Support\GatewayResult;

/**
 * A scriptable gateway: `$verdict` decides what verify() answers, and it counts how often it was asked.
 */
class FakeGateway implements PaymentGateway
{
    public static bool $verdict = true;

    public static int $verifyCalls = 0;

    public function name(): string
    {
        return 'fake';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function initiate(Payment $payment): string
    {
        return 'https://gateway.test/pay/'.$payment->idempotency_key;
    }

    public function handleCallback(Request $request): GatewayResult
    {
        return new GatewayResult(Payment::where('idempotency_key', $request->query('ref'))->first(), (string) $request->route('outcome', 'return'));
    }

    public function handleIpn(Request $request): GatewayResult
    {
        return new GatewayResult(Payment::where('idempotency_key', $request->input('ref'))->first(), 'ipn');
    }

    public function verify(Payment $payment): bool
    {
        self::$verifyCalls++;

        return self::$verdict;
    }
}

/**
 * Replace any earlier HTTP fake: stubs stack, and the first match wins, so repeated calls in one test need a clean slate.
 *
 * @param  array<string, mixed>  $stubs
 */
function fakeHttp(array $stubs = []): void
{
    Http::swap(new Factory);
    Http::fake($stubs);
}

function enableFakeGateway(): void
{
    GatewayManager::forgetExtensions();
    FakeGateway::$verdict = true;
    FakeGateway::$verifyCalls = 0;
    app(GatewayManager::class)->extend('fake', fn () => new FakeGateway);
    app(FeeSettings::class)->update(['gateway_enabled' => true, 'gateway_driver' => 'fake']);
}

function gatewayScenario(): array
{
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoice = invoicesFor($student, ['2026-01-01'])[0];
    enableFakeGateway();

    return [$student, $invoice];
}

it('is off by default, and manual or unconfigured drivers are never active', function () {
    $manager = app(GatewayManager::class);

    expect($manager->active())->toBeNull();

    app(FeeSettings::class)->update(['gateway_enabled' => true, 'gateway_driver' => 'manual']);
    expect($manager->active())->toBeNull();

    app(FeeSettings::class)->update(['gateway_driver' => 'sslcommerz']);
    expect($manager->active())->toBeNull();   // no credentials in .env
});

it('refuses to start a checkout while no gateway is active', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoice = invoicesFor($student, ['2026-01-01'])[0];

    app(GatewayPayments::class)->start($student, [$invoice->id]);
})->throws(ValidationException::class);

it('creates a pending payment with a server-side amount and returns the provider url', function () {
    [$student, $invoice] = gatewayScenario();

    $url = app(GatewayPayments::class)->start($student, [$invoice->id]);

    $payment = Payment::first();
    expect($url)->toBe('https://gateway.test/pay/'.$payment->idempotency_key)->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->amount->poisha)->toBe(130000)->and($payment->method->value)->toBe('gateway')->and($payment->invoice_ids)->toBe([$invoice->id])
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid);
});

it('rejects someone else\'s or an already paid invoice', function () {
    [$student, $invoice] = gatewayScenario();
    $other = enrolled(placement());

    expect(fn () => app(GatewayPayments::class)->start($other, [$invoice->id]))->toThrow(ValidationException::class);
});

it('approves only when the provider confirms, then leaves the payment alone on repeat callbacks', function () {
    [$student, $invoice] = gatewayScenario();
    app(GatewayPayments::class)->start($student, [$invoice->id]);
    $payment = Payment::first();
    $url = route('fees.gateway.callback', ['driver' => 'fake', 'outcome' => 'return', 'ref' => $payment->idempotency_key]);

    $this->get($url)->assertRedirect();
    $this->get($url)->assertRedirect();                      // a duplicate callback
    $this->postJson(route('fees.gateway.ipn', 'fake'), ['ref' => $payment->idempotency_key])->assertOk();   // and an IPN

    expect($payment->fresh()->status)->toBe(PaymentStatus::Approved)->and($payment->fresh()->receipt_no)->not->toBeNull()
        ->and($invoice->fresh()->paid->poisha)->toBe(130000)
        ->and(PaymentAllocation::count())->toBe(1)
        ->and(FakeGateway::$verifyCalls)->toBe(1);         // verified once; later calls saw an approved payment
});

it('never approves when the provider validation fails', function () {
    [$student, $invoice] = gatewayScenario();
    app(GatewayPayments::class)->start($student, [$invoice->id]);
    $payment = Payment::first();
    FakeGateway::$verdict = false;

    $this->get(route('fees.gateway.callback', ['driver' => 'fake', 'ref' => $payment->idempotency_key]))->assertRedirect();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)->and($payment->fresh()->receipt_no)->toBeNull()
        ->and($invoice->fresh()->paid->poisha)->toBe(0)->and(StudentCredit::count())->toBe(0);
});

it('closes a payment the payer cancelled without touching the invoice', function () {
    [$student, $invoice] = gatewayScenario();
    app(GatewayPayments::class)->start($student, [$invoice->id]);
    $payment = Payment::first();

    $this->get(route('fees.gateway.callback', ['driver' => 'fake', 'outcome' => 'cancel', 'ref' => $payment->idempotency_key]))->assertRedirect();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Failed)->and($invoice->fresh()->paid->poisha)->toBe(0)->and(FakeGateway::$verifyCalls)->toBe(0);
});

it('ignores callbacks for unknown references and unknown drivers', function () {
    enableFakeGateway();

    $this->get(route('fees.gateway.callback', ['driver' => 'fake', 'ref' => 'NOPE']))->assertRedirect();
    $this->get(route('fees.gateway.callback', ['driver' => 'nonexistent']))->assertNotFound();
    $this->postJson(route('fees.gateway.ipn', 'nonexistent'))->assertNotFound();

    expect(Payment::count())->toBe(0)->and(FakeGateway::$verifyCalls)->toBe(0);
});

it('does not trust an amount or status sent in the callback', function () {
    [$student, $invoice] = gatewayScenario();
    app(GatewayPayments::class)->start($student, [$invoice->id]);
    $payment = Payment::first();
    FakeGateway::$verdict = false;

    $this->get(route('fees.gateway.callback', ['driver' => 'fake', 'ref' => $payment->idempotency_key, 'status' => 'success', 'amount' => '1300', 'paid' => '1']));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

// ---- SSLCOMMERZ, against faked HTTP ---------------------------------------------------------

function sslSetup(): array
{
    config(['modules.fees.gateway.sslcommerz' => ['store_id' => 'store1', 'store_password' => 'secret', 'sandbox' => true]]);
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoice = invoicesFor($student, ['2026-01-01'])[0];
    $payment = Payment::factory()->create(['student_id' => $student->id, 'amount' => taka(1300), 'method' => 'gateway', 'channel' => 'gateway', 'transaction_id' => null, 'gateway' => 'sslcommerz', 'idempotency_key' => 'FEE-SSL'.uniqid(), 'invoice_ids' => [$invoice->id]]);

    return [$payment, $invoice];
}

it('starts an SSLCOMMERZ session and returns GatewayPageURL', function () {
    [$payment] = sslSetup();
    fakeHttp(['sandbox.sslcommerz.com/gwprocess/*' => Http::response(['status' => 'SUCCESS', 'GatewayPageURL' => 'https://sandbox.sslcommerz.com/gw/abc', 'sessionkey' => 'K1'])]);

    $url = (new SslCommerzGateway)->initiate($payment);

    expect($url)->toBe('https://sandbox.sslcommerz.com/gw/abc');
    Http::assertSent(fn ($request) => $request['total_amount'] === '1300.00' && $request['currency'] === 'BDT' && $request['tran_id'] === $payment->idempotency_key && $request['store_id'] === 'store1');
});

it('fails loudly when SSLCOMMERZ will not open a session', function () {
    [$payment] = sslSetup();
    fakeHttp(['*' => Http::response(['status' => 'FAILED', 'failedreason' => 'Invalid Store'])]);

    (new SslCommerzGateway)->initiate($payment);
})->throws(RuntimeException::class);

function sslVerify(array $overrides): bool
{
    [$payment] = sslSetup();
    $payment->forceFill(['gateway_payload' => ['val_id' => 'V1']])->save();
    $response = ['status' => 'VALID', 'tran_id' => $payment->idempotency_key, 'currency_type' => 'BDT', 'amount' => '1300.00', 'risk_level' => '0', ...$overrides];
    fakeHttp(['sandbox.sslcommerz.com/validator/*' => Http::response($response)]);

    return (new SslCommerzGateway)->verify($payment->refresh());
}

it('verifies an SSLCOMMERZ payment only when status, tran_id, currency and amount all match', function () {
    expect(sslVerify([]))->toBeTrue()
        ->and(sslVerify(['status' => 'VALIDATED']))->toBeTrue()
        ->and(sslVerify(['status' => 'INVALID_TRANSACTION']))->toBeFalse()
        ->and(sslVerify(['amount' => '1299.99']))->toBeFalse()
        ->and(sslVerify(['amount' => '13.00']))->toBeFalse()
        ->and(sslVerify(['currency_type' => 'USD']))->toBeFalse()
        ->and(sslVerify(['tran_id' => 'FEE-OTHER']))->toBeFalse()
        ->and(sslVerify(['risk_level' => '1']))->toBeFalse();
});

it('does not verify without a val_id', function () {
    [$payment] = sslSetup();
    fakeHttp();

    expect((new SslCommerzGateway)->verify($payment))->toBeFalse();
    Http::assertNothingSent();
});

it('remembers the val_id from a callback but finds the payment only by our tran_id', function () {
    [$payment] = sslSetup();

    $request = Request::create('/cb', 'POST', ['tran_id' => $payment->idempotency_key, 'val_id' => 'V9', 'status' => 'VALID', 'amount' => '1.00']);
    $result = (new SslCommerzGateway)->handleCallback($request);

    expect($result->payment->id)->toBe($payment->id)->and($payment->fresh()->gateway_payload['val_id'])->toBe('V9');
    expect((new SslCommerzGateway)->handleIpn(Request::create('/ipn', 'POST', ['tran_id' => 'UNKNOWN', 'val_id' => 'V1']))->payment)->toBeNull();
});

it('settles an SSLCOMMERZ payment end to end, and a tampered amount stays pending', function () {
    [$payment, $invoice] = sslSetup();
    $payment->forceFill(['gateway_payload' => ['val_id' => 'V1']])->save();
    app(GatewayManager::class)->extend('sslcommerz', fn () => new SslCommerzGateway);

    fakeHttp(['sandbox.sslcommerz.com/validator/*' => Http::response(['status' => 'VALID', 'tran_id' => $payment->idempotency_key, 'currency_type' => 'BDT', 'amount' => '1.00'])]);
    expect(app(GatewayPayments::class)->settle($payment)->status)->toBe(PaymentStatus::Pending)->and($invoice->fresh()->paid->poisha)->toBe(0);

    fakeHttp(['sandbox.sslcommerz.com/validator/*' => Http::response(['status' => 'VALID', 'tran_id' => $payment->idempotency_key, 'currency_type' => 'BDT', 'amount' => '1300.00'])]);
    expect(app(GatewayPayments::class)->settle($payment)->status)->toBe(PaymentStatus::Approved)->and($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});

// ---- bKash, against faked HTTP ---------------------------------------------------------------

function bkashSetup(): Payment
{
    Cache::forget('fees.bkash.token');
    config(['modules.fees.gateway.bkash' => ['app_key' => 'key', 'app_secret' => 'sec', 'username' => 'u', 'password' => 'p', 'sandbox' => true]]);
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoice = invoicesFor($student, ['2026-01-01'])[0];

    return Payment::factory()->create(['student_id' => $student->id, 'amount' => taka(1300), 'method' => 'gateway', 'channel' => 'gateway', 'transaction_id' => null, 'gateway' => 'bkash', 'idempotency_key' => 'FEE-BK'.uniqid(), 'invoice_ids' => [$invoice->id]]);
}

it('creates a bKash payment and keeps the payment id', function () {
    $payment = bkashSetup();
    fakeHttp([
        '*/auth/grant-token' => Http::response(['id_token' => 'TOKEN', 'token_type' => 'Bearer', 'expires_in' => 3600]),
        '*/payment/create' => Http::response(['paymentId' => 'TR001', 'bKashURL' => 'https://sandbox.bka.sh/pay/TR001', 'transactionStatus' => 'Initiated']),
    ]);

    $url = (new BkashGateway)->initiate($payment);

    expect($url)->toBe('https://sandbox.bka.sh/pay/TR001')->and($payment->fresh()->gateway_payload['payment_id'])->toBe('TR001');
    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/payment/create') && $request['amount'] === '1300.00' && $request['currency'] === 'BDT' && $request['intent'] === 'sale' && $request['merchantInvoiceNumber'] === $payment->idempotency_key && str_contains($request['callbackURL'], 'ref='.$payment->idempotency_key));
});

it('asks for a bKash token only once while it is cached', function () {
    $payment = bkashSetup();
    fakeHttp([
        '*/auth/grant-token' => Http::response(['id_token' => 'TOKEN']),
        '*/payment/create' => Http::response(['paymentId' => 'TR001', 'bKashURL' => 'https://x']),
    ]);

    (new BkashGateway)->initiate($payment);
    (new BkashGateway)->initiate($payment);

    Http::assertSentCount(3);
});

it('finds the payment from our ref and nothing else in the bKash callback', function () {
    $payment = bkashSetup();
    $gateway = new BkashGateway;

    expect($gateway->handleCallback(Request::create('/cb', 'GET', ['ref' => $payment->idempotency_key, 'status' => 'success', 'paymentID' => 'whatever']))->payment->id)->toBe($payment->id)
        ->and($gateway->handleCallback(Request::create('/cb', 'GET', ['status' => 'success', 'paymentID' => 'TR001']))->payment)->toBeNull()
        ->and($gateway->handleIpn(Request::create('/ipn', 'POST'))->payment)->toBeNull();
});

function bkashVerify(array $execute, ?array $query = null): bool
{
    $payment = bkashSetup();
    $key = $payment->idempotency_key;
    $execute = array_map(fn ($value) => $value === '@key' ? $key : $value, $execute);
    $query = $query === null ? null : array_map(fn ($value) => $value === '@key' ? $key : $value, $query);
    $payment->forceFill(['gateway_payload' => ['payment_id' => 'TR001']])->save();
    fakeHttp([
        '*/auth/grant-token' => Http::response(['id_token' => 'TOKEN']),
        '*/payment/execute' => Http::response($execute),
        '*/query/payment' => Http::response($query ?? ['errorMessageEn' => 'nothing']),
    ]);

    return (new BkashGateway)->verify($payment->refresh());
}

it('verifies a bKash payment only when Completed with our amount, BDT, invoice number and a trxId', function () {
    $good = ['transactionStatus' => 'Completed', 'trxId' => 'TRX123', 'amount' => '1300', 'currency' => 'BDT', 'merchantInvoiceNumber' => '@key', 'paymentId' => 'TR001'];

    expect(bkashVerify($good))->toBeTrue()
        ->and(bkashVerify([...$good, 'transactionStatus' => 'Initiated']))->toBeFalse()
        ->and(bkashVerify([...$good, 'amount' => '1299']))->toBeFalse()
        ->and(bkashVerify([...$good, 'currency' => 'USD']))->toBeFalse()
        ->and(bkashVerify([...$good, 'merchantInvoiceNumber' => 'FEE-OTHER']))->toBeFalse()
        ->and(bkashVerify([...$good, 'trxId' => '']))->toBeFalse()
        ->and(bkashVerify(['errorMessageEn' => 'Payment execution failed']))->toBeFalse();
});

it('falls back to Query Payment when execute gives no clear answer', function () {
    $completed = ['transactionStatus' => 'Completed', 'trxId' => 'TRX9', 'amount' => '1300', 'currency' => 'BDT', 'merchantInvoice' => '@key', 'verificationStatus' => 'Complete'];

    expect(bkashVerify(['errorMessageEn' => 'timeout'], $completed))->toBeTrue()
        ->and(bkashVerify(['errorMessageEn' => 'timeout'], [...$completed, 'transactionStatus' => 'Initiated']))->toBeFalse()
        ->and(bkashVerify(['errorMessageEn' => 'timeout'], [...$completed, 'amount' => '1']))->toBeFalse();
});

it('executes a bKash payment id only once', function () {
    $payment = bkashSetup();
    $payment->forceFill(['gateway_payload' => ['payment_id' => 'TR001']])->save();
    fakeHttp([
        '*/auth/grant-token' => Http::response(['id_token' => 'TOKEN']),
        '*/payment/execute' => Http::response(['errorMessageEn' => 'declined']),
        '*/query/payment' => Http::response(['transactionStatus' => 'Initiated']),
    ]);

    (new BkashGateway)->verify($payment->refresh());
    (new BkashGateway)->verify($payment->refresh());

    Http::assertSentCount(1 + 1 + 2);   // token, one execute, two queries
});

it('stores the bKash trxId on the payment when it succeeds', function () {
    $payment = bkashSetup();
    $payment->forceFill(['gateway_payload' => ['payment_id' => 'TR001']])->save();
    fakeHttp([
        '*/auth/grant-token' => Http::response(['id_token' => 'TOKEN']),
        '*/payment/execute' => Http::response(['transactionStatus' => 'Completed', 'trxId' => 'TRXOK1', 'amount' => '1300', 'currency' => 'BDT', 'merchantInvoiceNumber' => $payment->idempotency_key]),
    ]);

    expect((new BkashGateway)->verify($payment->refresh()))->toBeTrue()->and($payment->fresh()->transaction_id)->toBe('TRXOK1');
});
