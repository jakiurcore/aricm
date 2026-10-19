<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: notosansbengali; font-size: 6pt; color: #17212B; }
    .head { font-family: notoserifbengali; }
    .mark { position: absolute; background-color: #8A939E; }
    .muted { color: #566170; }
</style>
</head>
<body>
@php
    $stripe = function (float $x, float $y, bool $top) {
        $parts = [];
        $at = 0.0;
        while ($at < 54) {
            foreach ([['#0B6B3A', 6], ['#FFD21F', 3], ['#D6202A', 3]] as [$color, $width]) {
                if ($at >= 54) {
                    break;
                }
                $width = min($width, 54 - $at);
                $parts[] = ['x' => $x + $at, 'w' => $width, 'color' => $color, 'first' => $at == 0, 'last' => $at + $width >= 54];
                $at += $width;
            }
        }

        return $parts;
    };
@endphp
@foreach ($sheets as $sheet)
    {{-- FRONT --}}
    @foreach ($marks as $mark)
        <div class="mark" style="left: {{ $mark['x'] }}mm; top: {{ $mark['y'] }}mm; width: {{ $mark['w'] }}mm; height: {{ $mark['h'] }}mm;"></div>
    @endforeach

    @foreach ($sheet['front'] as $card)
        @php
            $student = $card['student'];
            $cx = $card['x'];
            $cy = $card['y'];
            $name = $student->displayName();
            $nameSize = mb_strlen($name) > 30 ? 2.5 : (mb_strlen($name) > 24 ? 2.75 : 3);
            $idText = 'ID: '.$student->student_id;
            $pillWidth = min(48, 5 + mb_strlen($idText) * 1.3);
            $rows = [
                [__('students::pdf.class'), $student->schoolClass?->displayName() ?? '—'],
                [__('students::pdf.roll'), $student->roll ? bn_digits($student->roll) : '—'],
                [__('students::pdf.session'), $student->session?->name ?? '—'],
                [__('students::pdf.blood'), $student->blood_group ?? '—'],
            ];
        @endphp
        <x-core::pdf.at :x="$cx" :y="$cy" :w="54" :h="85.6" style="background-color: #ffffff; border-radius: 3mm;"></x-core::pdf.at>
        <x-core::pdf.at :x="$cx" :y="$cy" :w="54" :h="28.3" style="background-color: #0B6B3A; background-gradient: linear #0B6B3A #064424 0.3 1 0.7 0; border-top-left-radius: 3mm; border-top-right-radius: 3mm;"></x-core::pdf.at>
        {{-- logo as a disc: a ring in the header colour (an SVG square with a round hole) hides the square corners of the artwork --}}
        <x-core::pdf.at :x="$cx + 21.5" :y="$cy + 2.5" :w="11" :h="11" style="background-color: #ffffff;"><img src="{{ $brand['logo'] }}" style="width: 11mm; height: 11mm;"></x-core::pdf.at>
        <x-core::pdf.at :x="$cx + 21.1" :y="$cy + 2.1" :w="11.8" :h="11.8"><img src="{{ $discMask }}" style="width: 11.8mm; height: 11.8mm;"></x-core::pdf.at>
        <x-core::pdf.at :x="$cx + 2" :y="$cy + 14.3" :w="50" :h="3.5" style="text-align: center; color: #ffffff; font-size: 2.7mm; font-weight: bold; line-height: 3.5mm;" class="head"><span class="head">{{ $brand['name_bn'] }}</span></x-core::pdf.at>
        <x-core::pdf.at :x="$cx + 2" :y="$cy + 18.6" :w="50" :h="2.7" style="text-align: center; color: #FFD21F; font-size: 1.8mm; letter-spacing: 0.02em; line-height: 2.7mm;">{{ mb_strtoupper($brand['name_en']) }}</x-core::pdf.at>

        <x-core::pdf.at :x="$cx + 17" :y="$cy + 22.8" :w="18.4" :h="22.4" style="border: 0.8mm solid #FFD21F; border-radius: 2mm; background-color: #F3F4EE; text-align: center;">
            @if ($card['photo'])
                <img src="{{ $card['photo'] }}" style="width: 18.4mm; height: 22.4mm;">
            @else
                <div style="padding-top: 6.5mm;"><img src="{{ $placeholder }}" style="width: 8mm; height: 8mm;"></div>
                <div class="muted" style="font-size: 2.6mm; line-height: 4mm;">{{ __('students::pdf.photo') }}</div>
            @endif
        </x-core::pdf.at>

        <x-core::pdf.at :x="$cx + 2" :y="$cy + 48.2" :w="50" :h="3.9" style="text-align: center; font-size: {{ $nameSize }}mm; font-weight: bold; line-height: 3.9mm;">{{ $name }}</x-core::pdf.at>
        <x-core::pdf.at :x="$cx + (54 - $pillWidth) / 2" :y="$cy + 52.6" :w="$pillWidth" :h="3.7" style="background-color: #D6202A; border-radius: 2mm; color: #ffffff; font-weight: bold; font-size: 2.2mm; line-height: 3.7mm; text-align: center;">{{ $idText }}</x-core::pdf.at>

        @foreach ($rows as $index => [$label, $value])
            <x-core::pdf.at :x="$cx + 3" :y="$cy + 68.6 + $index * 3.45" :w="34" :h="3.2" style="font-size: 2.1mm; line-height: 3.2mm; white-space: nowrap;"><span class="muted">{{ $label }}:</span> <b>{{ $value }}</b></x-core::pdf.at>
        @endforeach

        <x-core::pdf.at :x="$cx + 38" :y="$cy + 69.1" :w="11.8" :h="11.8" style="border: 0.2mm solid #CBD1C7; padding: 0.4mm; background-color: #ffffff;"><img src="{{ $card['qr'] }}" style="width: 11.8mm; height: 11.8mm;"></x-core::pdf.at>

        @foreach ($stripe($cx, $cy + 83.6, false) as $part)
            <x-core::pdf.at :x="$part['x']" :y="$cy + 83.6" :w="$part['w']" :h="2" style="background-color: {{ $part['color'] }};{{ $part['first'] ? ' border-bottom-left-radius: 2.5mm;' : '' }}{{ $part['last'] ? ' border-bottom-right-radius: 2.5mm;' : '' }}"></x-core::pdf.at>
        @endforeach
    @endforeach
    <pagebreak />

    {{-- BACK (columns mirrored) --}}
    @foreach ($marks as $mark)
        <div class="mark" style="left: {{ $mark['x'] }}mm; top: {{ $mark['y'] }}mm; width: {{ $mark['w'] }}mm; height: {{ $mark['h'] }}mm;"></div>
    @endforeach

    @foreach ($sheet['back'] as $card)
        @php
            $student = $card['student'];
            $cx = $card['x'];
            $cy = $card['y'];
        @endphp
        <x-core::pdf.at :x="$cx" :y="$cy" :w="54" :h="85.6" style="background-color: #ffffff; border-radius: 3mm;"></x-core::pdf.at>
        <x-core::pdf.at :x="$cx + 7" :y="$cy + 24" :w="40"><img src="{{ $brand['logo'] }}" style="width: 40mm; height: 37.7mm; opacity: 0.06;"></x-core::pdf.at>
        @foreach ($stripe($cx, $cy, true) as $part)
            <x-core::pdf.at :x="$part['x']" :y="$cy" :w="$part['w']" :h="2" style="background-color: {{ $part['color'] }};{{ $part['first'] ? ' border-top-left-radius: 2.5mm;' : '' }}{{ $part['last'] ? ' border-top-right-radius: 2.5mm;' : '' }}"></x-core::pdf.at>
        @endforeach

        <x-core::pdf.at :x="$cx + 3" :y="$cy + 5" :w="48" style="font-size: 2.2mm;">
            <div class="muted" style="line-height: 3.4mm;">{{ __('students::pdf.guardian_phone') }}</div>
            <div style="font-size: 3mm; font-weight: bold; line-height: 5.2mm;">{{ bn_digits($student->guardian_phone ?? '—', true) }}</div>
            <div style="height: 2mm;"></div>
            <div class="muted" style="line-height: 3.4mm;">{{ __('students::pdf.address') }}</div>
            <div style="font-weight: bold; line-height: 3.45mm;">{{ $student->present_address ?? '—' }}</div>
            <div style="height: 2mm;"></div>
            <div style="height: 0.2mm; background-color: #E3E6DE;"></div>
            <div style="height: 2mm;"></div>
            <div class="muted" style="line-height: 3.4mm;">{{ __('students::pdf.institute_contact') }}</div>
            <div style="font-weight: bold; line-height: 3.45mm;">{{ $brand['address'] }}</div>
            <div style="font-weight: bold; line-height: 3.45mm;">{{ $brand['phones'] }}</div>
            <div style="height: 2mm;"></div>
            <div style="padding: 1.5mm 2mm; border-radius: 1.5mm; background-color: #FFF8D6; text-align: center; font-weight: bold; font-size: 2.3mm; line-height: 3.4mm;">{{ __('students::pdf.return_note') }}</div>
        </x-core::pdf.at>

        <x-core::pdf.at :x="$cx + 29" :y="$cy + 74" :w="22" :h="3.8" style="border-top: 0.2mm solid #17212B; text-align: center; font-size: 2mm; line-height: 3.8mm;">{{ __('students::pdf.principal') }}</x-core::pdf.at>
        <x-core::pdf.at :x="$cx" :y="$cy + 80.8" :w="54" :h="4.8" style="background-color: #0B6B3A; border-bottom-left-radius: 3mm; border-bottom-right-radius: 3mm; color: #ffffff; text-align: center; font-size: 1.9mm; line-height: 4.8mm;">{{ __('students::pdf.valid_until', ['date' => $card['validUntil']]) }}</x-core::pdf.at>
    @endforeach
    @unless ($loop->last)<pagebreak />@endunless
@endforeach
</body>
</html>
