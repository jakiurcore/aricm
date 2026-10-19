<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: notosansbengali; font-size: 8pt; color: #17212B; }
    .abs { position: absolute; }
    .head { font-family: notoserifbengali; }
    .mark { position: absolute; background-color: #8A939E; }
</style>
</head>
<body>
@foreach ($sheets as $sheet)
    {{-- FRONT --}}
    @foreach ($marks as $mark)
        <div class="mark" style="left: {{ $mark['x'] }}mm; top: {{ $mark['y'] }}mm; width: {{ $mark['w'] }}mm; height: {{ $mark['h'] }}mm;"></div>
    @endforeach

    @foreach ($sheet['front'] as $card)
        @php($student = $card['student'])
        <div class="abs" style="left: {{ $card['x'] }}mm; top: {{ $card['y'] }}mm; width: 54mm; height: 85.6mm; border: 0.2mm solid #CBD1C7; background-color: #ffffff;">
            <table style="width: 54mm; height: 85.6mm; border-collapse: collapse;" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="height: 21mm; background-color: #0B6B3A; color: #ffffff; text-align: center; padding: 2mm 1.5mm 0 1.5mm;">
                        <img src="{{ $brand['logo'] }}" style="width: 11mm; height: 11mm;"><br>
                        <span class="head" style="font-size: 7.5pt; color: #ffffff;">{{ $brand['name_bn'] }}</span><br>
                        <span style="font-size: 4.6pt; color: #FFD21F;">{{ strtoupper($brand['name_en']) }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="height: 28mm; text-align: center; padding-top: 2mm;">
                        <table align="center" style="width: 20mm; border: 0.8mm solid #FFD21F; border-collapse: collapse;" cellpadding="0" cellspacing="0">
                            <tr><td style="width: 20mm; height: 24mm; background-color: #F3F4EE; text-align: center;">
                                @if ($card['photo'])
                                    <img src="{{ $card['photo'] }}" style="width: 20mm; height: 24mm;">
                                @else
                                    <span style="font-size: 20pt; color: #8A939E;">{{ mb_substr($student->name_bn, 0, 1) }}</span>
                                @endif
                            </td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="height: 12mm; text-align: center; padding: 0 2mm;">
                        <div style="font-size: 9pt; font-weight: bold;">{{ $student->name_bn }}</div>
                        <div style="margin-top: 0.8mm;"><span style="background-color: #D6202A; color: #ffffff; font-size: 6.5pt; padding: 0.4mm 2.5mm;">&nbsp;ID: {{ $student->student_id }}&nbsp;</span></div>
                    </td>
                </tr>
                <tr>
                    <td style="height: 20mm; padding: 1mm 3mm 0 3mm;">
                        <table style="width: 100%;" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="width: 34mm; font-size: 6pt;">
                                    <table style="font-size: 6pt;" cellpadding="0.2mm" cellspacing="0">
                                        <tr><td style="color: #566170;">{{ __('students::pdf.class') }}:</td><td><b>{{ $student->schoolClass?->displayName() }}{{ $student->section ? ' / '.$student->section->displayName() : '' }}</b></td></tr>
                                        <tr><td style="color: #566170;">{{ __('students::pdf.roll') }}:</td><td><b>{{ $student->roll ? bn_digits($student->roll, true) : '—' }}</b></td></tr>
                                        <tr><td style="color: #566170;">{{ __('students::pdf.blood') }}:</td><td><b>{{ $student->blood_group ?? '—' }}</b></td></tr>
                                        <tr><td style="color: #566170;">{{ __('students::pdf.session') }}:</td><td><b>{{ $student->session?->name }}</b></td></tr>
                                    </table>
                                </td>
                                <td style="text-align: right;"><img src="{{ $card['qr'] }}" style="width: 13mm; height: 13mm;"></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr><td style="height: 2.6mm; background-color: #0B6B3A;">&nbsp;</td></tr>
            </table>
        </div>
    @endforeach
    <pagebreak />

    {{-- BACK (columns mirrored) --}}
    @foreach ($marks as $mark)
        <div class="mark" style="left: {{ $mark['x'] }}mm; top: {{ $mark['y'] }}mm; width: {{ $mark['w'] }}mm; height: {{ $mark['h'] }}mm;"></div>
    @endforeach

    @foreach ($sheet['back'] as $card)
        @php($student = $card['student'])
        <div class="abs" style="left: {{ $card['x'] }}mm; top: {{ $card['y'] }}mm; width: 54mm; height: 85.6mm; border: 0.2mm solid #CBD1C7; background-color: #ffffff;">
            <table style="width: 54mm; height: 85.6mm; border-collapse: collapse;" cellpadding="0" cellspacing="0">
                <tr><td style="height: 2mm; background-color: #0B6B3A;">&nbsp;</td></tr>
                <tr>
                    <td style="height: 62mm; padding: 3mm; font-size: 6pt; vertical-align: top;">
                        <div style="color: #566170;">{{ __('students::pdf.guardian_phone') }}</div>
                        <div style="font-size: 9pt; font-weight: bold;">{{ bn_digits($student->guardian_phone ?? '—', true) }}</div>
                        <div style="padding-top: 3mm; color: #566170;">{{ __('students::pdf.address') }}</div>
                        <div><b>{{ $student->present_address ?? '—' }}</b></div>
                        <div style="padding-top: 3mm; color: #566170;">{{ __('students::pdf.institute_contact') }}</div>
                        <div><b>{{ $brand['address'] }}</b></div>
                        <div><b>{{ $brand['phones'] }}</b></div>
                        <div style="padding-top: 4mm;"><div style="padding: 1.5mm; background-color: #FFF8D6; text-align: center; font-weight: bold;">{{ __('students::pdf.return_note') }}</div></div>
                    </td>
                </tr>
                <tr>
                    <td style="height: 16mm; text-align: right; padding: 0 3mm 3mm 3mm; vertical-align: bottom;">
                        <span style="border-top: 0.2mm solid #17212B; font-size: 5.5pt; padding-top: 0.5mm;">&nbsp;&nbsp;&nbsp;&nbsp;{{ __('students::pdf.principal') }}&nbsp;&nbsp;&nbsp;&nbsp;</span>
                    </td>
                </tr>
                <tr><td style="height: 5.6mm; background-color: #0B6B3A; color: #ffffff; text-align: center; font-size: 5pt;">{{ __('students::pdf.valid_until', ['date' => $card['validUntil']]) }}</td></tr>
            </table>
        </div>
    @endforeach
    @unless ($loop->last)<pagebreak />@endunless
@endforeach
</body>
</html>
