<?php

use Modules\Core\Support\SignedHash;
use Modules\Fees\Services\InvoicePdf;
use Modules\Fees\Services\InvoiceVerification;

it('prints a due notice listing the open invoices of a student', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    invoicesFor($student, ['2026-01-01', '2026-02-01']);

    $response = $this->actingAs(userWithRole('accountant'))->get(route('fees.api.collection.due-notice', $student))->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('application/pdf')->and($response->getContent())->toStartWith('%PDF');
});

it('keeps the due notice behind the collection permission', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);

    $this->actingAs(userWithRole('teacher'))->get(route('fees.api.collection.due-notice', $student))->assertForbidden();
});

it('prints an invoice as a single A5 landscape page', function () {
    $p = placement();
    feeSetup($p);
    $invoice = invoicesFor(enrolled($p), ['2026-01-01'])[0];

    $pdf = app(InvoicePdf::class)->render($invoice);

    expect($pdf)->toStartWith('%PDF')->and(preg_match_all('/\/Type\s*\/Page[^s]/', $pdf))->toBe(1)->and($pdf)->toContain('/MediaBox [0 0 595.280 419.530]');
});

it('verifies an invoice through its signed hash and refuses a forged one', function () {
    $p = placement();
    feeSetup($p);
    $invoice = invoicesFor(enrolled($p), ['2026-01-01'])[0];

    $result = (new InvoiceVerification)->verify(SignedHash::make('invoice', $invoice->id));

    expect($result?->details)->toHaveKey(__('fees::pdf.verify.invoice'))->and((new InvoiceVerification)->verify($invoice->id.'-forged0000000000'))->toBeNull();
});
