<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: notosansbengali; font-size: 3.1mm; color: #17212B; }
    .head { font-family: notoserifbengali; }
    .muted { color: #566170; }
    td, th { vertical-align: top; }
</style>
</head>
<body>
@foreach ($pages as $page)
    @foreach ($page as $card)
        @php
            $student = $card['student'];
            $oy = $loop->index * 148.5;
            $count = max(1, count($card['routine']));
            $rowHeight = min(6.45, 80 / $count);
            $rowPad = max(0.2, ($rowHeight - 4.4) / 2);
            $rows = [
                [__('exams::pdf.name'), $student->displayName()],
                [__('exams::pdf.student_id'), $student->student_id],
                [__('exams::pdf.class'), $student->schoolClass?->displayName() ?? '—'],
                [__('exams::pdf.section'), $student->section?->displayName() ?? '—'],
                [__('exams::pdf.roll'), $student->roll ? bn_digits($student->roll) : '—'],
                [__('exams::pdf.session'), $exam->session?->name ?? '—'],
            ];
        @endphp
        {{-- watermark, dashed cut line --}}
        <x-core::pdf.at :x="80" :y="$oy + 30" :w="80"><img src="{{ $brand['logo'] }}" style="width: 80mm; height: 75.4mm; opacity: 0.05;"></x-core::pdf.at>
        <x-core::pdf.at :x="0" :y="$oy + 148.2" :w="210" :h="0" style="border-top: 0.3mm dashed #8A939E;"></x-core::pdf.at>

        {{-- letterhead --}}
        <x-core::pdf.at :x="9" :y="$oy + 7" :w="18" :h="18"><img src="{{ $brand['logo'] }}" style="width: 18mm; height: 18mm;"></x-core::pdf.at>
        @if ($brand['name_ar'])<x-core::pdf.at :x="31" :y="$oy + 7.6" :w="148" :h="5" style="text-align: center; font-family: xbriyaz; font-size: 4.2mm; color: #0B6B3A; line-height: 5mm;">{{ $brand['name_ar'] }}</x-core::pdf.at>@endif
        <x-core::pdf.at :x="31" :y="$oy + 13.2" :w="148" :h="6.5" style="text-align: center; font-family: notoserifbengali; font-size: 5.2mm; font-weight: bold; color: #16307A; line-height: 6.5mm;">{{ $brand['name_bn'] }}</x-core::pdf.at>
        <x-core::pdf.at :x="31" :y="$oy + 20.2" :w="148" :h="4.2" style="text-align: center; font-size: 2.8mm; color: #566170; line-height: 4.2mm;">{{ $brand['address'] }}</x-core::pdf.at>
        <x-core::pdf.at :x="183" :y="$oy + 7" :w="17" :h="17" style="border: 0.2mm solid #CBD1C7; padding: 0.5mm; background-color: #ffffff;"><img src="{{ $card['qr'] }}" style="width: 17mm; height: 17mm;"></x-core::pdf.at>
        <x-core::pdf.at :x="9" :y="$oy + 27.4" :w="192" :h="0" style="border-top: 0.6mm solid #0B6B3A;"></x-core::pdf.at>

        {{-- title pill --}}
        <x-core::pdf.at :x="9" :y="$oy + 31" :w="192" :h="7.4">
            <x-core::pdf.pill color="#D6202A" :height="7.4" :padding="6" style="color: #ffffff; font-weight: bold; font-size: 3.6mm; line-height: 5.4mm;">{{ __('exams::pdf.admit_card') }} — {{ $exam->displayName() }}</x-core::pdf.pill>
        </x-core::pdf.at>

        {{-- student details, photo, rules --}}
        <x-core::pdf.at :x="9" :y="$oy + 41.4" :w="83.1">
            <table style="width: 83.1mm;" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width: 56.1mm;">
                        <table style="width: 56.1mm;" cellpadding="0" cellspacing="0">
                            @foreach ($rows as [$label, $value])
                                <tr>
                                    <td class="muted" style="width: 20mm; font-size: 3.1mm; line-height: 4.7mm; padding-bottom: 1mm;">{{ $label }}</td>
                                    <td style="font-size: 3.1mm; line-height: 4.7mm; padding-bottom: 1mm; font-weight: bold;">: {{ $value }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
            </table>
            <div style="margin-top: 2mm; padding: 2mm 2.5mm; border-radius: 1.5mm; background-color: #EEF8F1; font-size: 2.6mm;">
                <div style="font-weight: bold; color: #0B6B3A; line-height: 3.9mm; padding-bottom: 0.6mm;">{{ __('exams::pdf.rules') }}</div>
                @foreach ($rules as $rule)<div style="line-height: 3.9mm; padding-bottom: 0.6mm;">• {{ $rule }}</div>@endforeach
            </div>
        </x-core::pdf.at>

        <x-core::pdf.at :x="68.1" :y="$oy + 41.4" :w="23.2" :h="28.2" style="border: 0.4mm solid #0B6B3A; border-radius: 1.5mm; background-color: #F3F4EE; text-align: center;">
            @if ($card['photo'])
                <img src="{{ $card['photo'] }}" style="width: 23.2mm; height: 28.2mm;">
            @else
                <div style="padding-top: 8mm;"><img src="{{ $placeholder }}" style="width: 8mm; height: 8mm;"></div>
                <div class="muted" style="font-size: 2.6mm; line-height: 4mm;">{{ __('exams::pdf.photo') }}</div>
            @endif
        </x-core::pdf.at>

        {{-- routine --}}
        <x-core::pdf.at :x="97.1" :y="$oy + 41.4" :w="103.9">
            <table style="width: 103.9mm; border-collapse: collapse; font-size: 2.8mm;" cellpadding="0" cellspacing="0">
                <tr style="background-color: #0B6B3A; color: #ffffff;">
                    @foreach ([[__('exams::pdf.date'), 28.5], [__('exams::pdf.day'), 21.5], [__('exams::pdf.subject'), 27.1], [__('exams::pdf.time'), 26.8]] as [$label, $width])
                        <td style="width: {{ $width }}mm; padding: 1.2mm 1.5mm; line-height: 4.2mm; font-weight: bold; text-align: left; color: #ffffff;">{{ $label }}</td>
                    @endforeach
                </tr>
                @foreach ($card['routine'] as $row)
                    <tr>
                        <td style="padding: {{ $rowPad }}mm 1.5mm; line-height: 4.2mm; border-bottom: 0.2mm solid #E3E6DE;">{{ $row['date'] }}</td>
                        <td style="padding: {{ $rowPad }}mm 1.5mm; line-height: 4.2mm; border-bottom: 0.2mm solid #E3E6DE;">{{ $row['day'] }}</td>
                        <td style="padding: {{ $rowPad }}mm 1.5mm; line-height: 4.2mm; border-bottom: 0.2mm solid #E3E6DE; font-weight: bold;">{{ $row['subject'] }}</td>
                        <td style="padding: {{ $rowPad }}mm 1.5mm; line-height: 4.2mm; border-bottom: 0.2mm solid #E3E6DE;">{{ $row['time'] }}</td>
                    </tr>
                @endforeach
            </table>
        </x-core::pdf.at>

        {{-- signatures --}}
        @foreach ([[9, __('exams::pdf.class_teacher')], [85, __('exams::pdf.controller')], [161, __('exams::pdf.principal')]] as [$sx, $caption])
            <x-core::pdf.at :x="$sx" :y="$oy + 135.8" :w="40" :h="4.5" style="border-top: 0.25mm solid #17212B; text-align: center; font-size: 2.8mm; line-height: 4.5mm; padding-top: 0.6mm;">{{ $caption }}</x-core::pdf.at>
        @endforeach
    @endforeach
    @unless ($loop->last)<pagebreak />@endunless
@endforeach
</body>
</html>
