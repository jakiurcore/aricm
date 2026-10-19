<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: notosansbengali; font-size: 7.5pt; color: #17212B; }
</style>
</head>
<body>
<div style="padding: 8mm 10mm 0 10mm;">
    <x-core::pdf.letterhead :brand="$brand" :title="__('results::pdf.tabulation')" :subtitle="$exam->displayName().' — '.$class->displayName().($section ? ' / '.$section->displayName() : '')" />

    <table style="width: 100%; border-collapse: collapse; margin-top: 2mm; font-size: 7.5pt;" cellpadding="0" cellspacing="0">
        <thead>
            <tr style="background-color: #16307A; color: #ffffff;">
                <th style="color: #ffffff; padding: 1.2mm;">{{ __('results::pdf.position') }}</th>
                <th style="color: #ffffff; padding: 1.2mm;">{{ __('results::pdf.roll') }}</th>
                <th style="color: #ffffff; padding: 1.2mm; text-align: left;">{{ __('results::pdf.name') }}</th>
                @foreach ($subjects as $name)<th style="color: #ffffff; padding: 1.2mm;">{{ $name }}</th>@endforeach
                <th style="color: #ffffff; padding: 1.2mm;">{{ __('results::pdf.total') }}</th>
                <th style="color: #ffffff; padding: 1.2mm;">GPA</th>
                <th style="color: #ffffff; padding: 1.2mm;">{{ __('results::pdf.grade') }}</th>
                <th style="color: #ffffff; padding: 1.2mm;">{{ __('results::pdf.result') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($results as $result)
                <tr style="{{ $result->is_passed ? ($loop->odd ? '' : 'background-color: #F5F6F1;') : 'background-color: #FDECEC;' }}">
                    <td style="padding: 1mm; text-align: center; border-bottom: 0.2mm solid #CBD1C7;">{{ $result->position_in_class ? bn_digits($result->position_in_class) : '—' }}</td>
                    <td style="padding: 1mm; text-align: center; border-bottom: 0.2mm solid #CBD1C7;">{{ $result->roll ? bn_digits($result->roll) : '—' }}</td>
                    <td style="padding: 1mm; border-bottom: 0.2mm solid #CBD1C7;">{{ $result->student->displayName() }}</td>
                    @foreach ($result->subject_results as $subject)
                        <td style="padding: 1mm; text-align: center; border-bottom: 0.2mm solid #CBD1C7; {{ $subject['passed'] ? '' : 'color: #B0141D;' }}">{{ $subject['absent'] ? __('results::pdf.absent') : bn_number($subject['total']) }}<br><span style="font-size: 6pt; color: #566170;">{{ $subject['grade'] }}</span></td>
                    @endforeach
                    <td style="padding: 1mm; text-align: center; border-bottom: 0.2mm solid #CBD1C7;"><b>{{ bn_number($result->total_marks) }}</b></td>
                    <td style="padding: 1mm; text-align: center; border-bottom: 0.2mm solid #CBD1C7;"><b>{{ bn_number($result->gpa, 2) }}</b></td>
                    <td style="padding: 1mm; text-align: center; border-bottom: 0.2mm solid #CBD1C7;">{{ $result->grade }}</td>
                    <td style="padding: 1mm; text-align: center; border-bottom: 0.2mm solid #CBD1C7;">{{ $result->is_passed ? __('results::messages.passed') : __('results::messages.failed') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="width: 100%; margin-top: 12mm; font-size: 8pt;" cellpadding="0" cellspacing="0">
        <tr>
            <td><span style="border-top: 0.25mm solid #17212B; padding-top: 1mm;">&nbsp;&nbsp;{{ __('results::pdf.class_teacher') }}&nbsp;&nbsp;</span></td>
            <td style="text-align: right;"><span style="border-top: 0.25mm solid #17212B; padding-top: 1mm;">&nbsp;&nbsp;{{ __('results::pdf.principal') }}&nbsp;&nbsp;</span></td>
        </tr>
    </table>
</div>
</body>
</html>
