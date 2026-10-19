<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: notosansbengali; font-size: 9pt; color: #17212B; }
    td, th { vertical-align: middle; }
</style>
</head>
<body>
@foreach ($sheets as $sheet)
    @php($result = $sheet['result'])
    @php($student = $sheet['student'])
    <div style="position: absolute; left: 3.5mm; top: 3.5mm; width: 203mm; height: 290mm; border: 0.8mm solid #0B6B3A;"></div>
    <div style="position: absolute; left: 5mm; top: 5mm; width: 200mm; height: 287mm; border: 0.25mm solid #FFD21F;"></div>
    <div style="position: absolute; left: 42mm; top: 85mm; width: 125mm;"><img src="{{ $brand['logo'] }}" style="width: 125mm; opacity: 0.06;"></div>

    <div style="padding: 9mm 11mm 0 11mm;">
        <table style="width: 100%; border-collapse: collapse;" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width: 26mm;"><img src="{{ $brand['logo'] }}" style="width: 24mm; height: 24mm;"></td>
                <td style="text-align: center;">
                    @if ($brand['name_ar'])<div style="font-family: xbriyaz; font-size: 16pt; color: #0B6B3A;">{{ $brand['name_ar'] }}</div>@endif
                    <div style="font-family: notoserifbengali; font-size: 18pt; color: #16307A;">{{ $brand['name_bn'] }}</div>
                    <div style="font-size: 9pt; font-weight: bold; color: #D6202A;">{{ strtoupper($brand['name_en']) }}</div>
                    <div style="font-size: 8pt; color: #566170;">{{ $brand['address'] }}{{ $brand['phones'] ? ' · '.$brand['phones'] : '' }}</div>
                </td>
                <td style="width: 26mm; text-align: right;">
                    <table align="right" style="border: 0.4mm solid #0B6B3A; border-collapse: collapse;" cellpadding="0" cellspacing="0">
                        <tr><td style="width: 22mm; height: 26mm; background-color: #F3F4EE; text-align: center;">
                            @if ($sheet['photo'])
                                <img src="{{ $sheet['photo'] }}" style="width: 22mm; height: 26mm;">
                            @else
                                <span style="font-size: 18pt; color: #8A939E;">{{ mb_substr($student->name_bn, 0, 1) }}</span>
                            @endif
                        </td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <div style="text-align: center; margin: 3mm 0;">
            <span style="background-color: #0B6B3A; color: #ffffff; font-weight: bold; font-size: 11pt; padding: 1.2mm 8mm;">{{ __('results::pdf.transcript') }} — {{ $result->exam->displayName() }}</span>
        </div>

        <table style="width: 100%; font-size: 9pt; background-color: #EEF8F1;" cellpadding="1.4mm" cellspacing="0">
            <tr>
                <td style="color: #566170; width: 24mm;">{{ __('results::pdf.name') }}</td><td><b>: {{ $student->displayName() }}</b></td>
                <td style="color: #566170; width: 24mm;">{{ __('results::pdf.student_id') }}</td><td><b>: {{ $student->student_id }}</b></td>
            </tr>
            <tr>
                <td style="color: #566170;">{{ __('results::pdf.father') }}</td><td><b>: {{ $student->father_name }}</b></td>
                <td style="color: #566170;">{{ __('results::pdf.mother') }}</td><td><b>: {{ $student->mother_name ?? '—' }}</b></td>
            </tr>
            <tr>
                <td style="color: #566170;">{{ __('results::pdf.class') }}</td><td><b>: {{ $student->schoolClass?->displayName() }}{{ $student->section ? ' / '.$student->section->displayName() : '' }}</b></td>
                <td style="color: #566170;">{{ __('results::pdf.roll') }}</td><td><b>: {{ $result->roll ? bn_digits($result->roll) : '—' }}</b></td>
            </tr>
            <tr>
                <td style="color: #566170;">{{ __('results::pdf.session') }}</td><td><b>: {{ $result->exam->session?->name }}</b></td>
                <td style="color: #566170;">{{ __('results::pdf.dob') }}</td><td><b>: {{ bn_date($student->date_of_birth) }}</b></td>
            </tr>
        </table>

        <table style="width: 100%; border-collapse: collapse; margin-top: 3mm; font-size: 8.5pt;" cellpadding="0" cellspacing="0">
            <thead>
                <tr style="background-color: #16307A; color: #ffffff;">
                    <th style="padding: 1.6mm; text-align: left;">{{ __('results::pdf.subject') }}</th>
                    <th style="padding: 1.6mm;">{{ __('results::pdf.full') }}</th>
                    <th style="padding: 1.6mm; text-align: left;">{{ __('results::pdf.parts') }}</th>
                    <th style="padding: 1.6mm;">{{ __('results::pdf.obtained') }}</th>
                    <th style="padding: 1.6mm;">{{ __('results::pdf.grade') }}</th>
                    <th style="padding: 1.6mm;">GP</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($result->subject_results as $subject)
                    <tr style="{{ $subject['passed'] ? '' : 'background-color: #FDECEC;' }}">
                        <td style="padding: 1.5mm; border-bottom: 0.2mm solid #CBD1C7;"><b>{{ $subject['name'] }}</b>@if ($subject['isOptional']) <span style="font-size: 7pt; color: #566170;">({{ __('results::pdf.optional') }})</span>@endif</td>
                        <td style="padding: 1.5mm; text-align: center; border-bottom: 0.2mm solid #CBD1C7;">{{ bn_number($subject['fullMarks']) }}</td>
                        <td style="padding: 1.5mm; font-size: 7.5pt; color: #566170; border-bottom: 0.2mm solid #CBD1C7;">
                            @if (count($subject['components']) > 1)
                                @foreach ($subject['components'] as $component){{ $component['name'] }}: {{ $component['absent'] ? __('results::pdf.absent') : bn_number($component['obtained'] ?? 0) }}@if (! $loop->last) · @endif @endforeach
                            @endif
                        </td>
                        <td style="padding: 1.5mm; text-align: center; font-weight: bold; border-bottom: 0.2mm solid #CBD1C7;">{{ $subject['absent'] ? __('results::pdf.absent') : bn_number($subject['total']) }}</td>
                        <td style="padding: 1.5mm; text-align: center; font-weight: bold; border-bottom: 0.2mm solid #CBD1C7;">{{ $subject['grade'] }}</td>
                        <td style="padding: 1.5mm; text-align: center; border-bottom: 0.2mm solid #CBD1C7;">{{ bn_number($subject['point'], 2) }}</td>
                    </tr>
                @endforeach
                <tr style="background-color: #EEF8F1; font-weight: bold;">
                    <td style="padding: 1.8mm;">{{ __('results::pdf.total') }}</td>
                    <td style="padding: 1.8mm; text-align: center;">{{ bn_number($result->full_marks) }}</td>
                    <td></td>
                    <td style="padding: 1.8mm; text-align: center;">{{ bn_number($result->total_marks) }}</td>
                    <td></td><td></td>
                </tr>
            </tbody>
        </table>

        <table style="width: 100%; margin-top: 4mm; border-collapse: separate; border-spacing: 2mm 0; text-align: center;" cellpadding="0" cellspacing="0">
            <tr>
                @foreach ([[__('results::pdf.percentage'), bn_number($result->percentage, 2).'%'], ['GPA', bn_number($result->gpa, 2)], [__('results::pdf.grade'), $result->grade], [__('results::pdf.position'), $result->position_in_class ? bn_digits($result->position_in_class) : '—']] as [$label, $value])
                    <td style="border: 0.3mm solid #CBD1C7; background-color: #F3F4EE; padding: 2mm;"><div style="font-size: 7.5pt; color: #566170;">{{ $label }}</div><div style="font-size: 13pt; font-weight: bold;">{{ $value }}</div></td>
                @endforeach
                <td style="border: 0.3mm solid {{ $result->is_passed ? '#15803D' : '#B0141D' }}; background-color: {{ $result->is_passed ? '#E6F6EC' : '#FDECEC' }}; padding: 2mm;">
                    <div style="font-size: 7.5pt; color: #566170;">{{ __('results::pdf.result') }}</div>
                    <div style="font-size: 13pt; font-weight: bold; color: {{ $result->is_passed ? '#15803D' : '#B0141D' }};">{{ $result->is_passed ? __('results::messages.passed') : __('results::messages.failed') }}</div>
                </td>
            </tr>
        </table>

        <table style="width: 100%; margin-top: 4mm;" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <div style="font-size: 8pt; font-weight: bold; color: #0B6B3A;">{{ __('results::pdf.scale') }}</div>
                    <table style="border-collapse: collapse; font-size: 7.5pt; text-align: center;" cellpadding="0" cellspacing="0">
                        <tr>@foreach ($sheet['scale'] as $band)<td style="border: 0.2mm solid #CBD1C7; background-color: #F3F4EE; padding: 0.8mm 2mm; font-weight: bold;">{{ $band['grade'] }}</td>@endforeach</tr>
                        <tr>@foreach ($sheet['scale'] as $band)<td style="border: 0.2mm solid #CBD1C7; padding: 0.8mm 2mm;">{{ $band['range'] }}</td>@endforeach</tr>
                        <tr>@foreach ($sheet['scale'] as $band)<td style="border: 0.2mm solid #CBD1C7; padding: 0.8mm 2mm; color: #566170;">{{ $band['point'] }}</td>@endforeach</tr>
                    </table>
                </td>
                <td style="width: 26mm; text-align: center;">
                    <img src="{{ $sheet['qr'] }}" style="width: 22mm; height: 22mm;">
                    <div style="font-size: 6.5pt; color: #566170;">{{ __('results::pdf.verify') }}</div>
                </td>
            </tr>
        </table>

        <table style="width: 100%; margin-top: 14mm; font-size: 8.5pt;" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width: 35%;"><span style="border-top: 0.25mm solid #17212B; padding-top: 1mm;">&nbsp;&nbsp;{{ __('results::pdf.class_teacher') }}&nbsp;&nbsp;</span></td>
                <td style="text-align: center; font-size: 7.5pt; color: #566170;">{{ __('results::pdf.issued') }}: {{ $issued }}</td>
                <td style="width: 35%; text-align: right;">
                    @if ($brand['signature'])<img src="{{ $brand['signature'] }}" style="height: 10mm;"><br>@endif
                    <span style="border-top: 0.25mm solid #17212B; padding-top: 1mm;">&nbsp;&nbsp;{{ __('results::pdf.principal') }}&nbsp;&nbsp;</span>
                </td>
            </tr>
        </table>
    </div>
    @unless ($loop->last)<pagebreak />@endunless
@endforeach
</body>
</html>
