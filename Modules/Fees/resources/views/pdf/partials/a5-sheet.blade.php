@php
    use Modules\Core\Support\Bangla;

    $kind = $doc['kind'];
    $grey = '#475467';
    $rule = '#D0D5DD';
    $rightWidth = $doc['rightWidth'] ?? 54;
    $words = fn (string $text): int => max(1, (int) ceil(mb_strlen($text) / 36));
    $mainTop = 56.7;
    $paragraphLines = max(2, (int) ceil(mb_strlen(__('fees::pdf.notice_body')) / (Bangla::isBangla() ? 140 : 100)));

    if ($kind === 'notice') {
        $count = max(1, count($doc['rows']));
        $rowHeight = min(8.15, max(5.5, (135 - 4 - $mainTop - 5 - 3 - 4.97 * $paragraphLines - 3 - 8 - 9.7 - 3 - 4.6) / $count));
        $mainHeight = 5 + 3 + 4.97 * $paragraphLines + 3 + 8 + $count * $rowHeight + 9.7 + 3 + 4.6;
    } else {
        $count = max(1, count($doc['rows']));
        $rowHeight = 7.75;
        $sumHeight = collect($doc['sums'])->sum(fn (array $sum): float => 2.4 + 1.5 * $sum['size'] + ($sum['rule'] ?? 0) * 0.5);
        $tableHeight = 8 + $count * $rowHeight + $sumHeight;
        $payCount = count($doc['pay']);
        $rightHeight = 18.9 + 4 * $words($doc['boxWords']) + 2.5 + $payCount * 7.1 + 20;
        $mainHeight = max($tableHeight, $rightHeight);
    }

    $footerTop = min($mainTop + $mainHeight + 4, 135);
    $stamp = $doc['stamp'] ?? null;
    $stampWidth = $stamp ? 13.6 + 4.2 * mb_strlen($stamp['text']) : 0;
    $stampHeight = 14.3;
    $boxWidth = $stamp ? 0.970 * $stampWidth + 0.242 * $stampHeight : 0;
    $boxHeight = $stamp ? 0.242 * $stampWidth + 0.970 * $stampHeight : 0;
@endphp
<x-core::pdf.at :x="65" :y="39.3" :w="80"><img src="{{ $brand['logo'] }}" style="width: 80mm; height: 75.4mm; opacity: 0.05;"></x-core::pdf.at>

{{-- letterhead --}}
<x-core::pdf.at :x="11" :y="9" :w="20" :h="20"><img src="{{ $brand['logo'] }}" style="width: 20mm; height: 20mm;"></x-core::pdf.at>
@if ($brand['name_ar'])<x-core::pdf.at :x="35" :y="9.5" :w="125" :h="5.2" style="font-family: xbriyaz; font-size: 3.97mm; color: #0B6B3A; line-height: 5.2mm; text-align: left;">{{ $brand['name_ar'] }}</x-core::pdf.at>@endif
<x-core::pdf.at :x="35" :y="14.6" :w="131.8" :h="6.5" style="font-family: notoserifbengali; font-size: 5.03mm; font-weight: bold; color: #0B6B3A; line-height: 6.5mm;">{{ $brand['name_bn'] }}</x-core::pdf.at>
<x-core::pdf.at :x="35" :y="21.1" :w="131.8" :h="3.6" style="font-size: 2.78mm; font-weight: bold; color: #16307A; line-height: 3.6mm;">{{ $brand['name_en'] }}</x-core::pdf.at>
<x-core::pdf.at :x="35" :y="24.8" :w="131.8" :h="3.8" style="font-size: 2.91mm; color: {{ $grey }}; line-height: 3.8mm; white-space: nowrap;">{{ $brand['address'] }}{{ $brand['phones'] ? ' · '.$brand['phones'] : '' }}</x-core::pdf.at>
<x-core::pdf.at :x="120" :y="9.3" :w="79" :h="9.5">
    <x-core::pdf.pill align="right" :color="$doc['titleColor']" :height="9.5" :padding="5" style="color: #ffffff; font-weight: bold; font-size: 3.7mm; line-height: 5.6mm;">{{ $doc['title'] }}</x-core::pdf.pill>
</x-core::pdf.at>
<x-core::pdf.at :x="120" :y="19.4" :w="79" :h="4.4" style="text-align: right; font-size: 2.91mm; font-weight: bold; line-height: 4.4mm;">{{ $doc['number'] }}</x-core::pdf.at>
<x-core::pdf.at :x="120" :y="24.3" :w="79" :h="4.4" style="text-align: right; font-size: 2.91mm; line-height: 4.4mm;">{{ __('fees::pdf.date') }}: {{ $doc['date'] }}</x-core::pdf.at>
<x-core::pdf.at :x="11" :y="32" :w="188" :h="0" style="border-top: 0.79mm solid #0B6B3A;"></x-core::pdf.at>

{{-- student strip --}}
<x-core::pdf.at :x="11" :y="36.8" :w="188" :h="15.9" style="background-color: #F1F7F2; border-radius: 3mm;"></x-core::pdf.at>
@foreach ($doc['student'] as $index => [$label, $value])
    <x-core::pdf.at :x="15 + ($index % 3) * 62" :y="39.3 + intdiv($index, 3) * 5.9" :w="60" :h="5" style="font-size: 3.31mm; line-height: 5mm; white-space: nowrap;"><span style="color: {{ $grey }};">{{ $label }}:</span> <b>{{ $value }}</b></x-core::pdf.at>
@endforeach

@if ($kind === 'notice')
<x-core::pdf.at :x="11" :y="$mainTop" :w="188" style="font-size: 3.31mm;">
        <div style="line-height: 5mm;">{{ __('fees::pdf.notice_greeting') }}</div>
        <div style="margin-top: 3mm; line-height: 4.97mm; ">{!! __('fees::pdf.notice_body', ['date' => '<b>'.e($doc['deadline']).'</b>']) !!}</div>
        <table style="width: 188mm; border-collapse: collapse; margin-top: 3mm;" cellpadding="0" cellspacing="0">
            <tr style="background-color: #0B6B3A;">
                <td style="width: 92.6mm; height: 8mm; padding: 0 3mm; color: #ffffff; font-weight: bold;">{{ __('fees::pdf.notice_invoice') }}</td>
                <td style="width: 58.7mm; padding: 0 3mm; color: #ffffff; font-weight: bold;">{{ __('fees::pdf.due_date_label_short') }}</td>
                <td style="width: 36.7mm; padding: 0 3mm; color: #ffffff; font-weight: bold; text-align: right;">{{ __('fees::pdf.amount') }}</td>
            </tr>
            @foreach ($doc['rows'] as $row)
                <tr>
                    <td style="height: {{ $rowHeight }}mm; padding: 0 3mm; border-bottom: 0.26mm solid {{ $rule }};">{{ $row['label'] }}</td>
                    <td style="padding: 0 3mm; border-bottom: 0.26mm solid {{ $rule }};">{{ $row['date'] }}</td>
                    <td style="padding: 0 3mm; border-bottom: 0.26mm solid {{ $rule }}; text-align: right; white-space: nowrap;">{{ $row['amount'] }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="2" style="height: 9.7mm; padding: 0 3mm; font-weight: bold; font-size: 3.7mm;">{{ __('fees::pdf.notice_total') }} <span style="font-weight: normal; font-size: 2.91mm;">({{ $doc['totalWords'] }})</span></td>
                <td style="padding: 0 3mm; text-align: right; font-weight: bold; font-size: 3.7mm; color: #D6202A; white-space: nowrap;">{{ $doc['total'] }}</td>
            </tr>
        </table>
        <div style="margin-top: 3mm; font-size: 3.04mm; line-height: 4.6mm; color: {{ $grey }};">{{ __('fees::pdf.notice_footnote', ['bkash' => $doc['bkash'], 'nagad' => $doc['nagad']]) }}</div>
</x-core::pdf.at>
@else
<x-core::pdf.at :x="11" :y="$mainTop" :w="128">        <table style="width: 128mm; border-collapse: collapse; font-size: 3.31mm;" cellpadding="0" cellspacing="0">
            <tr style="background-color: #0B6B3A;">
                <td style="width: 11.3mm; height: 8mm; padding: 0 3mm; color: #ffffff; font-weight: bold;">{{ __('fees::pdf.serial') }}</td>
                <td style="padding: 0 3mm; color: #ffffff; font-weight: bold; line-height: 4.5mm;">{{ $doc['tableLabel'] }}</td>
                <td style="width: {{ $doc['tableAmountWidth'] }}mm; padding: 0 3mm; color: #ffffff; font-weight: bold; text-align: right;">{{ __('fees::pdf.amount') }}</td>
            </tr>
            @foreach ($doc['rows'] as $index => $row)
                <tr>
                    <td style="height: {{ $rowHeight }}mm; padding: 0 3mm; border-bottom: 0.26mm solid {{ $rule }};">{{ bn_digits($index + 1) }}</td>
                    <td style="padding: 0 3mm; border-bottom: 0.26mm solid {{ $rule }};">{{ $row['label'] }}</td>
                    <td style="padding: 0 3mm; border-bottom: 0.26mm solid {{ $rule }}; text-align: right; white-space: nowrap;">{{ $row['amount'] }}</td>
                </tr>
            @endforeach
            @foreach ($doc['sums'] as $sum)
                <tr>
                    <td></td>
                    <td style="height: {{ round(2.4 + 1.5 * $sum['size'], 2) }}mm; padding: 0 3mm; text-align: right; font-size: {{ $sum['size'] }}mm; font-weight: {{ $sum['weight'] }};">{{ $sum['label'] }}</td>
                    <td style="padding: 0 3mm; text-align: right; white-space: nowrap; font-size: {{ $sum['size'] }}mm; font-weight: {{ $sum['weight'] }}; color: {{ $sum['color'] }}; {{ $sum['rule'] ? 'border-top: '.$sum['rule'].'mm solid #17212B;' : '' }}">{{ $sum['value'] }}</td>
                </tr>
            @endforeach
        </table>
</x-core::pdf.at>
<x-core::pdf.at :x="145" :y="$mainTop" :w="$rightWidth">        <div style="border: 0.4mm solid #0B6B3A; border-radius: 3mm; padding: 2.4mm 2.6mm 2.4mm 2.6mm;">
            <div style="font-size: 2.91mm; color: {{ $grey }}; line-height: 4.4mm;">{{ $doc['boxLabel'] }}</div>
            <div style="font-family: notoserifbengali; font-size: 5.82mm; font-weight: bold; color: #0B6B3A; line-height: 7mm; padding-top: 0.6mm;">{{ $doc['boxAmount'] }}</div>
            <div style="font-size: 2.78mm; line-height: 4mm; padding-top: 0.6mm;">{{ __('fees::pdf.in_words') }}: {{ $doc['boxWords'] }}</div>
        </div>
        <div style="height: 2.5mm;"></div>
        <table style="width: {{ $rightWidth }}mm; font-size: 3.04mm;" cellpadding="0" cellspacing="0">
            @foreach ($doc['pay'] as [$label, $value])
                <tr>
                    <td style="color: {{ $grey }}; line-height: 4.6mm; padding-bottom: 2.5mm;">{{ $label }}</td>
                    <td style="text-align: right; font-weight: bold; line-height: 4.6mm; padding-bottom: 2.5mm;">{{ $value }}</td>
                </tr>
            @endforeach
        </table>
        <table style="width: {{ $rightWidth }}mm;" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width: 23mm;"><img src="{{ $doc['qr'] }}" style="width: 20mm; height: 20mm;"></td>
                <td style="font-size: 2.51mm; color: {{ $grey }}; line-height: 3.5mm;">{{ __('fees::pdf.verify_hint') }}<br>{{ $doc['verifyUrl'] }}</td>
            </tr>
        </table>
</x-core::pdf.at>
@endif

{{-- footer --}}
<x-core::pdf.at :x="11" :y="$footerTop" :w="188" :h="0" style="border-top: 0.26mm solid {{ $rule }};"></x-core::pdf.at>
<x-core::pdf.at :x="11" :y="$footerTop + 5.3" :w="100" :h="7.7" style="font-size: 2.65mm; color: {{ $grey }}; line-height: 3.84mm;">{{ $doc['footer'] }}</x-core::pdf.at>
@foreach ([[121, $doc['signLeft']], [165, $doc['signRight']]] as [$sx, [$signName, $signRole]])
    @if ($signName)<x-core::pdf.at :x="$sx" :y="$footerTop + 2.2" :w="34" :h="5.2" style="text-align: center; font-family: notoserifbengali; font-style: italic; font-size: 3.44mm; color: #16307A; line-height: 5.2mm;">{{ $signName }}</x-core::pdf.at>@endif
    <x-core::pdf.at :x="$sx" :y="$footerTop + 7.4" :w="34" :h="5.6" style="border-top: 0.26mm solid #17212B; text-align: center; font-size: 2.91mm; line-height: 3.8mm; padding-top: 1mm;">{{ $signRole }}</x-core::pdf.at>
@endforeach

@if ($stamp)
    <x-core::pdf.at :x="192 - $stampWidth / 2 - $boxWidth / 2" :y="46 + $stampHeight / 2 - $boxHeight / 2" :w="$boxWidth" :h="$boxHeight"><img src="{{ \Modules\Fees\Services\PrintDocumentFactory::stamp($stamp['text'], $stamp['color'], $stampWidth, $stampHeight) }}" style="width: {{ round($boxWidth, 2) }}mm; height: {{ round($boxHeight, 2) }}mm;"></x-core::pdf.at>
@endif
