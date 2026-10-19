<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: notosansbengali; font-size: 9pt; color: #17212B; }
</style>
</head>
<body>
<div style="border: 0.6mm solid #0B6B3A; padding: 4mm;">
    <x-core::pdf.letterhead :brand="$brand" :title="__('fees::pdf.receipt')" />

    <table style="width: 100%; margin-top: 2mm; font-size: 9pt;" cellpadding="1mm" cellspacing="0">
        <tr>
            <td style="width: 22mm; color: #566170;">{{ __('fees::pdf.receipt_no') }}</td><td><b>: {{ $payment->receipt_no }}</b></td>
            <td style="width: 18mm; color: #566170;">{{ __('fees::pdf.date') }}</td><td><b>: {{ bn_date($payment->paid_at) }}</b></td>
        </tr>
        <tr>
            <td style="color: #566170;">{{ __('fees::pdf.student') }}</td><td><b>: {{ $payment->student->displayName() }}</b></td>
            <td style="color: #566170;">{{ __('fees::pdf.student_id') }}</td><td><b>: {{ $payment->student->student_id }}</b></td>
        </tr>
        <tr>
            <td style="color: #566170;">{{ __('fees::pdf.class') }}</td><td><b>: {{ $payment->student->schoolClass?->displayName() }}{{ $payment->student->section ? ' / '.$payment->student->section->displayName() : '' }}</b></td>
            <td style="color: #566170;">{{ __('fees::pdf.roll') }}</td><td><b>: {{ $payment->student->roll ? bn_digits($payment->student->roll) : '—' }}</b></td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; margin-top: 3mm; font-size: 8.5pt;" cellpadding="0" cellspacing="0">
        <thead>
            <tr style="background-color: #0B6B3A; color: #ffffff;">
                <th style="padding: 1.4mm; text-align: left;">{{ __('fees::pdf.invoice') }}</th>
                <th style="padding: 1.4mm; text-align: left;">{{ __('fees::pdf.description') }}</th>
                <th style="padding: 1.4mm; text-align: right;">{{ __('fees::pdf.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td style="padding: 1.3mm; border-bottom: 0.2mm solid #CBD1C7;">{{ $line['invoice_no'] }}</td>
                    <td style="padding: 1.3mm; border-bottom: 0.2mm solid #CBD1C7;">{{ $line['description'] }}</td>
                    <td style="padding: 1.3mm; text-align: right; border-bottom: 0.2mm solid #CBD1C7;">{{ $line['amount']->format() }}</td>
                </tr>
            @endforeach
            <tr style="background-color: #EEF8F1; font-weight: bold;">
                <td colspan="2" style="padding: 1.6mm;">{{ __('fees::pdf.total') }}</td>
                <td style="padding: 1.6mm; text-align: right;">{{ $payment->amount->format() }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 2mm; font-size: 9pt;"><span style="color: #566170;">{{ __('fees::pdf.in_words') }}:</span> <b>{{ $payment->amount->inWords() }}</b></div>

    <table style="width: 100%; margin-top: 2mm; font-size: 8.5pt;" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div><span style="color: #566170;">{{ __('fees::pdf.method') }}:</span> <b>{{ $payment->method->label() }}</b>@if ($payment->transaction_id) · {{ $payment->transaction_id }}@endif</div>
                <div><span style="color: #566170;">{{ __('fees::pdf.received_by') }}:</span> <b>{{ $payment->receiver?->name ?? $payment->verifier?->name ?? '—' }}</b></div>
            </td>
            <td style="width: 24mm; text-align: center;"><img src="{{ $qr }}" style="width: 20mm; height: 20mm;"><div style="font-size: 6pt; color: #566170;">{{ __('fees::pdf.verify_hint') }}</div></td>
        </tr>
    </table>

    <table style="width: 100%; margin-top: 8mm; font-size: 8.5pt;" cellpadding="0" cellspacing="0">
        <tr>
            <td><span style="border-top: 0.25mm solid #17212B; padding-top: 1mm;">&nbsp;&nbsp;{{ __('fees::pdf.payer') }}&nbsp;&nbsp;</span></td>
            <td style="text-align: right;"><span style="border-top: 0.25mm solid #17212B; padding-top: 1mm;">&nbsp;&nbsp;{{ __('fees::pdf.accountant') }}&nbsp;&nbsp;</span></td>
        </tr>
    </table>

    @if ($footer)<div style="margin-top: 3mm; text-align: center; font-size: 7.5pt; color: #566170;">{{ $footer }}</div>@endif
</div>
</body>
</html>
