<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: notosansbengali; font-size: 2.91mm; color: #000000; }
</style>
</head>
<body>
@php
    $y = 5.0;
    $row = function (string $left, string $right, bool $bold = true, float $size = 2.91) use (&$y): array {
        $result = ['y' => $y, 'left' => $left, 'right' => $right, 'bold' => $bold, 'size' => $size];
        $y += 4.4 + 1.06;

        return $result;
    };
@endphp
<x-core::pdf.at :x="32" :y="5" :w="16" :h="16"><img src="{{ $brand['logo_gray'] }}" style="width: 16mm; height: 16mm;"></x-core::pdf.at>
<x-core::pdf.at :x="4" :y="21.3" :w="72" :h="4.6" style="text-align: center; font-size: 3.44mm; font-weight: bold; line-height: 4.6mm;">{{ $brand['name_bn'] }}</x-core::pdf.at>
<x-core::pdf.at :x="4" :y="26.2" :w="72" :h="4" style="text-align: center; font-size: 2.65mm; line-height: 4mm;">{{ $brand['address'] }}</x-core::pdf.at>
<x-core::pdf.at :x="4" :y="30.4" :w="72" :h="4" style="text-align: center; font-size: 2.65mm; line-height: 4mm;">{{ $brand['phones'] }}</x-core::pdf.at>
<x-core::pdf.at :x="4" :y="35.4" :w="72" :h="5.8">
    <table align="center" style="border: 0.26mm solid #000000; border-collapse: collapse;" cellpadding="0" cellspacing="0"><tr><td style="padding: 0.26mm 2.6mm; font-size: 3.18mm; font-weight: bold; line-height: 5.2mm;">{{ __('fees::pdf.receipt') }}</td></tr></table>
</x-core::pdf.at>
@php($y = 43.1)
<x-core::pdf.at :x="4" :y="$y" :w="72" :h="0" style="border-top: 0.2mm dashed #000000;"></x-core::pdf.at>
@php($y = 45.2)
@foreach ($doc['meta'] as [$label, $value])
    @php($line = $row($label, $value))
    <x-core::pdf.at :x="4" :y="$line['y']" :w="72" :h="4.4"><table style="width: 72mm;" cellpadding="0" cellspacing="0"><tr><td style="line-height: 4.4mm;">{{ $label }}</td><td style="text-align: right; font-weight: bold; line-height: 4.4mm;">{{ $value }}</td></tr></table></x-core::pdf.at>
@endforeach
@php($y += 0.0)
@php($divider = $y - 1.06 + 0.8)
<x-core::pdf.at :x="4" :y="$divider" :w="72" :h="0" style="border-top: 0.2mm dashed #000000;"></x-core::pdf.at>
@php($y = $divider + 0.26 + 0.8 + 1.06)
@foreach ($doc['lines'] as [$label, $value])
    @php($line = $row($label, $value))
    <x-core::pdf.at :x="4" :y="$line['y']" :w="72" :h="4.4"><table style="width: 72mm;" cellpadding="0" cellspacing="0"><tr><td style="line-height: 4.4mm;">{{ $label }}</td><td style="text-align: right; white-space: nowrap; line-height: 4.4mm;">{{ $value }}</td></tr></table></x-core::pdf.at>
@endforeach
@php($divider = $y - 1.06 + 0.8)
<x-core::pdf.at :x="4" :y="$divider" :w="72" :h="0" style="border-top: 0.26mm solid #000000;"></x-core::pdf.at>
@php($y = $divider + 0.26 + 0.8 + 1.06)
<x-core::pdf.at :x="4" :y="$y" :w="72" :h="5.6"><table style="width: 72mm;" cellpadding="0" cellspacing="0"><tr><td style="font-size: 3.7mm; font-weight: bold; line-height: 5.6mm;">{{ __('fees::pdf.total_paid') }}</td><td style="text-align: right; font-size: 3.7mm; font-weight: bold; line-height: 5.6mm;">{{ $doc['amount'] }}</td></tr></table></x-core::pdf.at>
@php($y += 5.6 + 1.06)
<x-core::pdf.at :x="4" :y="$y" :w="72" :style="'font-size: 2.65mm; line-height: 4mm;'">{{ __('fees::pdf.in_words') }}: {{ $doc['words'] }}</x-core::pdf.at>
@php($y += 4 + 1.06)
@php($divider = $y + 0.8)
<x-core::pdf.at :x="4" :y="$divider" :w="72" :h="0" style="border-top: 0.2mm dashed #000000;"></x-core::pdf.at>
@php($y = $divider + 0.26 + 0.8 + 1.06)
@php($payLines = array_values(array_filter([[__('fees::pdf.method'), $doc['method']], $doc['transaction'] ? [__('fees::pdf.txn_id'), $doc['transaction']] : null, [__('fees::pdf.received_by'), $doc['receiver']]])))
@foreach ($payLines as [$label, $value])
    @php($line = $row($label, $value))
    <x-core::pdf.at :x="4" :y="$line['y']" :w="72" :h="4.4"><table style="width: 72mm;" cellpadding="0" cellspacing="0"><tr><td style="line-height: 4.4mm;">{{ $label }}</td><td style="text-align: right; font-weight: bold; line-height: 4.4mm;">{{ $value }}</td></tr></table></x-core::pdf.at>
@endforeach
@php($y += 1.06)
<x-core::pdf.at :x="29" :y="$y" :w="22" :h="22"><img src="{{ $doc['qr'] }}" style="width: 22mm; height: 22mm;"></x-core::pdf.at>
@php($y += 22 + 0.5)
<x-core::pdf.at :x="4" :y="$y" :w="72" :h="3.6" style="text-align: center; font-size: 2.38mm; line-height: 3.6mm;">{{ __('fees::pdf.verify_short') }}: {{ $doc['verifyUrl'] }}</x-core::pdf.at>
@php($y += 3.6 + 1.06 + 4.3)
@if ($doc['footer'])<x-core::pdf.at :x="4" :y="$y" :w="72" style="text-align: center; font-size: 2.38mm; line-height: 3.55mm;">{{ $doc['footer'] }}</x-core::pdf.at>@endif
</body>
</html>
