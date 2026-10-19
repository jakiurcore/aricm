<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: notosansbengali; font-size: 8.5pt; color: #17212B; }
    td, th { vertical-align: top; }
</style>
</head>
<body>
@foreach ($pages as $page)
    @foreach ($page as $card)
        @php($student = $card['student'])
        <div style="height: 139mm; overflow: hidden; padding: 7mm 9mm 0 9mm; border-bottom: 0.3mm dashed #8A939E;">
            <table style="width: 100%; border-collapse: collapse; border-bottom: 0.6mm solid #0B6B3A;" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width: 20mm; padding-bottom: 2mm;"><img src="{{ $brand['logo'] }}" style="width: 17mm; height: 17mm;"></td>
                    <td style="text-align: center; padding-bottom: 2mm;">
                        @if ($brand['name_ar'])<div style="font-family: xbriyaz; font-size: 12pt; color: #0B6B3A;">{{ $brand['name_ar'] }}</div>@endif
                        <div style="font-family: notoserifbengali; font-size: 14pt; color: #16307A;">{{ $brand['name_bn'] }}</div>
                        <div style="font-size: 7.5pt; color: #566170;">{{ $brand['address'] }}</div>
                    </td>
                    <td style="width: 20mm; text-align: right; padding-bottom: 2mm;"><img src="{{ $card['qr'] }}" style="width: 17mm; height: 17mm;"></td>
                </tr>
            </table>

            <div style="text-align: center; margin: 2mm 0;">
                <span style="background-color: #D6202A; color: #ffffff; font-weight: bold; font-size: 10pt; padding: 1mm 6mm;">{{ __('exams::pdf.admit_card') }} — {{ $exam->displayName() }}</span>
            </div>

            <table style="width: 100%; border-collapse: collapse;" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width: 46%; padding-right: 4mm;">
                        <table style="width: 100%; font-size: 8.5pt;" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>
                                    <table cellpadding="0.6mm" cellspacing="0" style="font-size: 8.5pt;">
                                        <tr><td style="color: #566170; width: 17mm;">{{ __('exams::pdf.name') }}</td><td><b>: {{ $student->displayName() }}</b></td></tr>
                                        <tr><td style="color: #566170;">{{ __('exams::pdf.student_id') }}</td><td><b>: {{ $student->student_id }}</b></td></tr>
                                        <tr><td style="color: #566170;">{{ __('exams::pdf.class') }}</td><td><b>: {{ $student->schoolClass?->displayName() }}{{ $student->section ? ' / '.$student->section->displayName() : '' }}</b></td></tr>
                                        <tr><td style="color: #566170;">{{ __('exams::pdf.roll') }}</td><td><b>: {{ $student->roll ? bn_digits($student->roll) : '—' }}</b></td></tr>
                                        <tr><td style="color: #566170;">{{ __('exams::pdf.father') }}</td><td><b>: {{ $student->father_name }}</b></td></tr>
                                        <tr><td style="color: #566170;">{{ __('exams::pdf.session') }}</td><td><b>: {{ $exam->session?->name }}</b></td></tr>
                                    </table>
                                </td>
                                <td style="width: 25mm; text-align: right;">
                                    <table align="right" style="border: 0.4mm solid #0B6B3A; border-collapse: collapse;" cellpadding="0" cellspacing="0">
                                        <tr><td style="width: 24mm; height: 29mm; background-color: #F3F4EE; text-align: center;">
                                            @if ($card['photo'])
                                                <img src="{{ $card['photo'] }}" style="width: 24mm; height: 29mm;">
                                            @else
                                                <span style="font-size: 20pt; color: #8A939E;">{{ mb_substr($student->name_bn, 0, 1) }}</span>
                                            @endif
                                        </td></tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        <div style="margin-top: 2mm; padding: 2mm 2.5mm; background-color: #EEF8F1; font-size: 7pt;">
                            <b style="color: #0B6B3A;">{{ __('exams::pdf.rules') }}</b>
                            @foreach ($rules as $rule)<div>• {{ $rule }}</div>@endforeach
                        </div>
                    </td>
                    <td style="width: 54%;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 8pt;" cellpadding="0" cellspacing="0">
                            <thead>
                                <tr style="background-color: #0B6B3A; color: #ffffff;">
                                    <th style="padding: 1.2mm 1.5mm; text-align: left;">{{ __('exams::pdf.date') }}</th>
                                    <th style="padding: 1.2mm 1.5mm; text-align: left;">{{ __('exams::pdf.day') }}</th>
                                    <th style="padding: 1.2mm 1.5mm; text-align: left;">{{ __('exams::pdf.subject') }}</th>
                                    <th style="padding: 1.2mm 1.5mm; text-align: left;">{{ __('exams::pdf.time') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($card['routine'] as $row)
                                    <tr style="border-bottom: 0.2mm solid #E3E6DE;">
                                        <td style="padding: 1mm 1.5mm; border-bottom: 0.2mm solid #E3E6DE;">{{ $row['date'] }}</td>
                                        <td style="padding: 1mm 1.5mm; border-bottom: 0.2mm solid #E3E6DE;">{{ $row['day'] }}</td>
                                        <td style="padding: 1mm 1.5mm; border-bottom: 0.2mm solid #E3E6DE;"><b>{{ $row['subject'] }}</b></td>
                                        <td style="padding: 1mm 1.5mm; border-bottom: 0.2mm solid #E3E6DE;">{{ $row['time'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>

            <table style="width: 100%; margin-top: 9mm; font-size: 8pt;" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width: 33%; text-align: left;"><span style="border-top: 0.25mm solid #17212B; padding-top: 1mm;">&nbsp;&nbsp;{{ __('exams::pdf.class_teacher') }}&nbsp;&nbsp;</span></td>
                    <td style="width: 34%; text-align: center;"><span style="border-top: 0.25mm solid #17212B; padding-top: 1mm;">&nbsp;&nbsp;{{ __('exams::pdf.controller') }}&nbsp;&nbsp;</span></td>
                    <td style="width: 33%; text-align: right;"><span style="border-top: 0.25mm solid #17212B; padding-top: 1mm;">&nbsp;&nbsp;{{ __('exams::pdf.principal') }}&nbsp;&nbsp;</span></td>
                </tr>
            </table>
        </div>
    @endforeach
    @unless ($loop->last)<pagebreak />@endunless
@endforeach
</body>
</html>
