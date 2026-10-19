<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: notosansbengali; font-size: 9pt; color: #17212B; }
</style>
</head>
<body>
<div style="border: 0.6mm solid #0B6B3A; padding: 4mm;">
    <x-core::pdf.letterhead :brand="$brand" :title="__('fees::pdf.invoice_title')" />

    <table style="width: 100%; margin-top: 2mm;" cellpadding="1mm" cellspacing="0">
        <tr>
            <td style="width: 22mm; color: #566170;">{{ __('fees::pdf.invoice') }}</td><td><b>: {{ $invoice->invoice_no }}</b></td>
            <td style="width: 22mm; color: #566170;">{{ __('fees::pdf.status') }}</td><td><b>: {{ $invoice->status->label() }}</b></td>
        </tr>
        <tr>
            <td style="color: #566170;">{{ __('fees::pdf.student') }}</td><td><b>: {{ $invoice->student->displayName() }}</b></td>
            <td style="color: #566170;">{{ __('fees::pdf.student_id') }}</td><td><b>: {{ $invoice->student->student_id }}</b></td>
        </tr>
        <tr>
            <td style="color: #566170;">{{ __('fees::pdf.class') }}</td><td><b>: {{ $invoice->student->schoolClass?->displayName() }}</b></td>
            <td style="color: #566170;">{{ __('fees::pdf.due_date') }}</td><td><b>: {{ bn_date($invoice->due_date) }}</b></td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; margin-top: 3mm; font-size: 8.5pt;" cellpadding="0" cellspacing="0">
        <thead>
            <tr style="background-color: #0B6B3A; color: #ffffff;">
                <th style="padding: 1.4mm; text-align: left;">{{ __('fees::pdf.description') }}</th>
                <th style="padding: 1.4mm; text-align: right;">{{ __('fees::pdf.amount') }}</th>
                <th style="padding: 1.4mm; text-align: right;">{{ __('fees::pdf.discount') }}</th>
                <th style="padding: 1.4mm; text-align: right;">{{ __('fees::pdf.net') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td style="padding: 1.3mm; border-bottom: 0.2mm solid #CBD1C7;">{{ $item->head->displayName() }}{{ $item->description ? ' — '.$item->description : '' }}@if ($invoice->billing_month) ({{ bn_date($invoice->billing_month->startOfMonth(), true) }})@endif</td>
                    <td style="padding: 1.3mm; text-align: right; border-bottom: 0.2mm solid #CBD1C7;">{{ $item->amount->format() }}</td>
                    <td style="padding: 1.3mm; text-align: right; border-bottom: 0.2mm solid #CBD1C7;">{{ $item->discount->format() }}</td>
                    <td style="padding: 1.3mm; text-align: right; border-bottom: 0.2mm solid #CBD1C7;">{{ $item->net->format() }}</td>
                </tr>
            @endforeach
            @if ($invoice->late_fine->isPositive())
                <tr><td colspan="3" style="padding: 1.3mm;">{{ __('fees::pdf.late_fine') }}</td><td style="padding: 1.3mm; text-align: right;">{{ $invoice->late_fine->format() }}</td></tr>
            @endif
            <tr style="background-color: #EEF8F1; font-weight: bold;"><td colspan="3" style="padding: 1.6mm;">{{ __('fees::pdf.total') }}</td><td style="padding: 1.6mm; text-align: right;">{{ $invoice->total->format() }}</td></tr>
            <tr><td colspan="3" style="padding: 1.3mm;">{{ __('fees::pdf.paid') }}</td><td style="padding: 1.3mm; text-align: right;">{{ $invoice->paid->format() }}</td></tr>
            <tr style="font-weight: bold; color: #B0141D;"><td colspan="3" style="padding: 1.3mm;">{{ __('fees::pdf.due') }}</td><td style="padding: 1.3mm; text-align: right;">{{ $invoice->due->format() }}</td></tr>
        </tbody>
    </table>
</div>
</body>
</html>
