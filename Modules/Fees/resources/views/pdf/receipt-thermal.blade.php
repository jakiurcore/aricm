<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: notosansbengali; font-size: 8pt; color: #000000; }
</style>
</head>
<body>
<div style="text-align: center;">
    <img src="{{ $brand['logo'] }}" style="width: 12mm; height: 12mm;"><br>
    <b style="font-size: 9.5pt;">{{ $brand['name_bn'] }}</b><br>
    <span style="font-size: 6.5pt;">{{ $brand['address'] }}</span><br>
    <span style="font-size: 6.5pt;">{{ $brand['phones'] }}</span>
</div>
<div style="border-top: 0.3mm dashed #000000; margin: 2mm 0;"></div>
<div style="text-align: center; font-weight: bold;">{{ __('fees::pdf.receipt') }}</div>
<table style="width: 100%; font-size: 7.5pt;" cellpadding="0.4mm" cellspacing="0">
    <tr><td>{{ __('fees::pdf.receipt_no') }}</td><td style="text-align: right;"><b>{{ $payment->receipt_no }}</b></td></tr>
    <tr><td>{{ __('fees::pdf.date') }}</td><td style="text-align: right;">{{ bn_date($payment->paid_at) }}</td></tr>
    <tr><td>{{ __('fees::pdf.student') }}</td><td style="text-align: right;">{{ $payment->student->displayName() }}</td></tr>
    <tr><td>{{ __('fees::pdf.student_id') }}</td><td style="text-align: right;">{{ $payment->student->student_id }}</td></tr>
    <tr><td>{{ __('fees::pdf.class') }}</td><td style="text-align: right;">{{ $payment->student->schoolClass?->displayName() }}</td></tr>
</table>
<div style="border-top: 0.3mm dashed #000000; margin: 2mm 0;"></div>
<table style="width: 100%; font-size: 7.5pt;" cellpadding="0.4mm" cellspacing="0">
    @foreach ($lines as $line)
        <tr><td>{{ $line['description'] ?: $line['invoice_no'] }}</td><td style="text-align: right;">{{ $line['amount']->format() }}</td></tr>
    @endforeach
    <tr><td><b>{{ __('fees::pdf.total') }}</b></td><td style="text-align: right;"><b>{{ $payment->amount->format() }}</b></td></tr>
</table>
<div style="font-size: 7pt; margin-top: 1mm;">{{ $payment->amount->inWords() }}</div>
<div style="font-size: 7pt;">{{ $payment->method->label() }}@if ($payment->transaction_id) · {{ $payment->transaction_id }}@endif</div>
<div style="text-align: center; margin-top: 2mm;"><img src="{{ $qr }}" style="width: 18mm; height: 18mm;"></div>
@if ($footer)<div style="text-align: center; font-size: 6.5pt; margin-top: 1mm;">{{ $footer }}</div>@endif
</body>
</html>
