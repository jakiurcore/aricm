<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: notosansbengali; font-size: 3mm; color: #17212B; }
    .head { font-family: notoserifbengali; }
    .muted { color: #566170; }
    td, th { vertical-align: middle; }
</style>
</head>
<body>
@foreach ($sheets as $sheet)
    @php
        $result = $sheet['result'];
        $student = $sheet['student'];
        $subjects = $sheet['subjects'];
        $count = max(1, count($subjects));
        $rowHeight = min(7.7, 127.4 / $count);
        $rowPad = max(0.1, ($rowHeight - 4.7) / 2);
        $tableTop = 83.6;
        $tableBottom = $tableTop + 7.7 + $count * $rowHeight + 8.2;
        $summaryTop = $tableBottom + 4;
        $scaleTop = $summaryTop + 15.3 + 4;
        $passed = $result->is_passed;
        $info = [
            [__('results::pdf.name'), $student->displayName(), __('results::pdf.student_id'), $student->student_id],
            [__('results::pdf.father'), $student->father_name ?: '—', __('results::pdf.class'), $student->schoolClass?->displayName() ?? '—'],
            [__('results::pdf.mother'), $student->mother_name ?: '—', __('results::pdf.roll'), $result->roll ? bn_digits($result->roll) : '—'],
            [__('results::pdf.dob'), $student->date_of_birth ? bn_date($student->date_of_birth) : '—', __('results::pdf.session'), $result->exam->session?->name ?? '—'],
        ];
        $summary = [
            [__('results::pdf.total_marks'), bn_number($result->total_marks), '#ffffff', '#16307A', '#CBD1C7', '#566170'],
            [__('results::pdf.gpa'), bn_number($result->gpa, 2), '#0B6B3A', '#ffffff', '#0B6B3A', '#E6F2EA'],
            [__('results::pdf.final_grade'), $result->grade, '#ffffff', '#16307A', '#CBD1C7', '#566170'],
            [__('results::pdf.position'), $sheet['position'], '#ffffff', '#16307A', '#CBD1C7', '#566170'],
            [__('results::pdf.result'), $passed ? __('results::messages.passed') : __('results::messages.failed'), $passed ? '#E6F6EC' : '#FDECEC', $passed ? '#15803D' : '#B0141D', '#CBD1C7', '#566170'],
        ];
        $bands = count($sheet['scale']);
    @endphp
    {{-- frame and watermark --}}
    <x-core::pdf.at :x="3.5" :y="3.5" :w="201.4" :h="288.4" style="border: 0.8mm solid #0B6B3A; border-radius: 2mm;"></x-core::pdf.at>
    <x-core::pdf.at :x="5" :y="5" :w="199.5" :h="286.5" style="border: 0.25mm solid #FFD21F; border-radius: 1.5mm;"></x-core::pdf.at>
    <x-core::pdf.at :x="42.5" :y="95.6" :w="125"><img src="{{ $brand['logo'] }}" style="width: 125mm; height: 117.8mm; opacity: 0.06;"></x-core::pdf.at>

    {{-- letterhead --}}
    <x-core::pdf.at :x="11" :y="10" :w="24" :h="24"><img src="{{ $brand['logo'] }}" style="width: 24mm; height: 24mm;"></x-core::pdf.at>
    @if ($brand['name_ar'])<x-core::pdf.at :x="39" :y="9.1" :w="132" :h="6.6" style="text-align: center; font-family: xbriyaz; font-size: 5.5mm; color: #0B6B3A; line-height: 6.6mm;">{{ $brand['name_ar'] }}</x-core::pdf.at>@endif
    <x-core::pdf.at :x="39" :y="16.3" :w="132" :h="8" style="text-align: center; font-family: notoserifbengali; font-size: 6.4mm; font-weight: bold; color: #16307A; line-height: 8mm;">{{ $brand['name_bn'] }}</x-core::pdf.at>
    <x-core::pdf.at :x="39" :y="24.9" :w="132" :h="5.1" style="text-align: center; font-size: 3.4mm; font-weight: bold; color: #D6202A; letter-spacing: 0.02em; line-height: 5.1mm;">{{ mb_strtoupper($brand['name_en']) }}</x-core::pdf.at>
    <x-core::pdf.at :x="39" :y="30.6" :w="132" :h="4.3" style="text-align: center; font-size: 2.9mm; color: #566170; line-height: 4.3mm;">{{ $brand['address'] }}{{ $brand['phones'] ? ' · '.$brand['phones'] : '' }}</x-core::pdf.at>
    <x-core::pdf.at :x="177" :y="9" :w="21.2" :h="25.2" style="border: 0.4mm solid #0B6B3A; border-radius: 1.5mm; background-color: #F3F4EE; text-align: center;">
        @if ($sheet['photo'])
            <img src="{{ $sheet['photo'] }}" style="width: 21.2mm; height: 25.2mm;">
        @else
            <div style="padding-top: 6.5mm;"><img src="{{ $placeholder }}" style="width: 8mm; height: 8mm;"></div>
            <div class="muted" style="font-size: 2.6mm; line-height: 4mm;">{{ __('results::pdf.photo') }}</div>
        @endif
    </x-core::pdf.at>

    <x-core::pdf.at :x="11" :y="39" :w="188" :h="8.4">
        <x-core::pdf.pill color="#0B6B3A" :height="8.4" :padding="8" style="color: #ffffff; font-weight: bold; font-size: 4mm; line-height: 6mm;">{{ __('results::pdf.transcript') }} — {{ $result->exam->displayName() }}</x-core::pdf.pill>
    </x-core::pdf.at>

    {{-- student details --}}
    <x-core::pdf.at :x="11" :y="51.4" :w="188" :h="28.2" style="background-color: #F1F9F4; border-radius: 2mm;"></x-core::pdf.at>
    @foreach ($info as $index => [$leftLabel, $leftValue, $rightLabel, $rightValue])
        <x-core::pdf.at :x="15" :y="54.4 + $index * 5.8" :w="28" :h="4.8" style="font-size: 3.2mm; line-height: 4.8mm; color: #566170;">{{ $leftLabel }}</x-core::pdf.at>
        <x-core::pdf.at :x="43" :y="54.4 + $index * 5.8" :w="58" :h="4.8" style="font-size: 3.2mm; line-height: 4.8mm; font-weight: bold; white-space: nowrap;">: {{ $leftValue }}</x-core::pdf.at>
        <x-core::pdf.at :x="109" :y="54.4 + $index * 5.8" :w="28" :h="4.8" style="font-size: 3.2mm; line-height: 4.8mm; color: #566170;">{{ $rightLabel }}</x-core::pdf.at>
        <x-core::pdf.at :x="137" :y="54.4 + $index * 5.8" :w="58" :h="4.8" style="font-size: 3.2mm; line-height: 4.8mm; font-weight: bold; white-space: nowrap;">: {{ $rightValue }}</x-core::pdf.at>
    @endforeach

    {{-- marks table --}}
    <x-core::pdf.at :x="11" :y="$tableTop" :w="188">
        <table style="width: 188mm; border-collapse: collapse; font-size: 3mm;" cellpadding="0" cellspacing="0">
            <tr style="background-color: #16307A;">
                @foreach ([[__('results::pdf.subject'), 37, 'left'], [__('results::pdf.full'), 20.6, 'center'], [__('results::pdf.pass_marks'), 14, 'center'], [__('results::pdf.parts'), 68.3, 'left'], [__('results::pdf.obtained'), 16.1, 'center'], [__('results::pdf.grade'), 15.4, 'center'], ['GP', 16.5, 'center']] as [$label, $width, $align])
                    <td style="width: {{ $width }}mm; height: 7.7mm; padding: 0 1.6mm; color: #ffffff; font-weight: bold; text-align: {{ $align }}; line-height: 4.5mm;">{{ $label }}</td>
                @endforeach
            </tr>
            @foreach ($subjects as $index => $subject)
                @php($rowStyle = 'height: '.$rowHeight.'mm; padding: '.$rowPad.'mm 1.5mm; line-height: 4.5mm; border-bottom: 0.2mm solid #CBD1C7;')
                <tr style="{{ $subject['passed'] ? ($index % 2 ? 'background-color: #F5F6F1;' : '') : 'background-color: #FDECEC;' }}">
                    <td style="{{ $rowStyle }} font-weight: bold;">{{ $subject['name'] }}@if ($subject['isOptional']) <span style="font-size: 2.2mm; font-weight: normal; color: #566170;">({{ __('results::pdf.optional') }})</span>@endif</td>
                    <td style="{{ $rowStyle }} text-align: center;">{{ bn_number($subject['fullMarks']) }}</td>
                    <td style="{{ $rowStyle }} text-align: center;">{{ $subject['passMarks'] !== null ? bn_number($subject['passMarks']) : '' }}</td>
                    <td style="{{ $rowStyle }} font-size: 2.7mm; color: #566170;">{{ $subject['parts'] }}</td>
                    <td style="{{ $rowStyle }} text-align: center; font-weight: bold;">{{ $subject['absent'] ? __('results::pdf.absent') : bn_number($subject['total']) }}</td>
                    <td style="{{ $rowStyle }} text-align: center; font-weight: bold;">{{ $subject['grade'] }}</td>
                    <td style="{{ $rowStyle }} text-align: center;">{{ bn_number($subject['point'], 2) }}</td>
                </tr>
            @endforeach
            <tr style="background-color: #EEF8F1;">
                <td style="height: 8.2mm; padding: 0 1.8mm; font-weight: bold;">{{ __('results::pdf.total') }}</td>
                <td style="text-align: center; font-weight: bold;">{{ bn_number($result->full_marks) }}</td>
                <td></td><td></td>
                <td style="text-align: center; font-weight: bold;">{{ bn_number($result->total_marks) }}</td>
                <td></td><td></td>
            </tr>
        </table>
    </x-core::pdf.at>

    {{-- summary cards --}}
    @foreach ($summary as $index => [$label, $value, $background, $color, $border, $labelColor])
        <x-core::pdf.at :x="11 + $index * 38" :y="$summaryTop" :w="35.4" :h="14.7" style="border: 0.3mm solid {{ $border }}; border-radius: 2mm; background-color: {{ $background }}; text-align: center;">
            <div style="height: 2mm;"></div>
            <div style="font-size: 2.6mm; line-height: 3.9mm; color: {{ $labelColor }};">{{ $label }}</div>
            <div style="font-size: 4.6mm; line-height: 6.9mm; font-weight: bold; color: {{ $color }};">{{ $value }}</div>
        </x-core::pdf.at>
    @endforeach

    {{-- grading scale and QR --}}
    <x-core::pdf.at :x="11" :y="$scaleTop" :w="160" :h="4.5" style="font-size: 3mm; font-weight: bold; color: #0B6B3A; line-height: 4.5mm;">{{ __('results::pdf.scale') }}</x-core::pdf.at>
    @if ($bands > 0)
        <x-core::pdf.at :x="11" :y="$scaleTop + 5.5" :w="160">
            <table style="width: 160mm; border-collapse: collapse; font-size: 2.6mm; text-align: center;" cellpadding="0" cellspacing="0">
                <tr>@foreach ($sheet['scale'] as $band)<td style="width: {{ 160 / $bands }}mm; border: 0.2mm solid #CBD1C7; background-color: #F3F4EE; padding: 0.6mm; font-weight: bold; line-height: 3.3mm;">{{ $band['grade'] }}</td>@endforeach</tr>
                <tr>@foreach ($sheet['scale'] as $band)<td style="border: 0.2mm solid #CBD1C7; border-bottom: 0; border-top: 0; padding: 0.4mm; line-height: 3.5mm;">{{ $band['range'] }}</td>@endforeach</tr>
                <tr>@foreach ($sheet['scale'] as $band)<td style="border: 0.2mm solid #CBD1C7; border-top: 0; padding: 0.4mm; line-height: 3.5mm; color: #566170;">{{ $band['point'] }}</td>@endforeach</tr>
            </table>
        </x-core::pdf.at>
    @endif
    <x-core::pdf.at :x="177" :y="$scaleTop" :w="20.6" :h="20.6" style="border: 0.2mm solid #CBD1C7; padding: 0.6mm; background-color: #ffffff;"><img src="{{ $sheet['qr'] }}" style="width: 20.6mm; height: 20.6mm;"></x-core::pdf.at>
    <x-core::pdf.at :x="173" :y="$scaleTop + 22.5" :w="30" style="text-align: center; font-size: 2.2mm; line-height: 3.3mm; color: #566170;">{{ __('results::pdf.verify') }}</x-core::pdf.at>

    {{-- signatures --}}
    <x-core::pdf.at :x="15" :y="280.2" :w="50" :h="5.8" style="border-top: 0.25mm solid #17212B; text-align: center; font-size: 3mm; line-height: 4mm; padding-top: 1mm;">{{ __('results::pdf.class_teacher') }}</x-core::pdf.at>
    <x-core::pdf.at :x="75" :y="282.1" :w="60" :h="3.9" style="text-align: center; font-size: 2.6mm; line-height: 3.9mm; color: #566170;">{{ __('results::pdf.issued') }}: {{ $issued }}</x-core::pdf.at>
    @if ($brand['signature'])<x-core::pdf.at :x="160" :y="268.5" :w="20"><img src="{{ $brand['signature'] }}" style="height: 10mm;"></x-core::pdf.at>@endif
    <x-core::pdf.at :x="145" :y="280.2" :w="50" :h="5.8" style="border-top: 0.25mm solid #17212B; text-align: center; font-size: 3mm; line-height: 4mm; padding-top: 1mm;">{{ __('results::pdf.principal') }}</x-core::pdf.at>
    @unless ($loop->last)<pagebreak />@endunless
@endforeach
</body>
</html>
