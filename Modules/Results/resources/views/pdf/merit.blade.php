<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: notosansbengali; font-size: 9pt; color: #17212B; }
</style>
</head>
<body>
<div style="padding: 9mm 12mm 0 12mm;">
    <x-core::pdf.letterhead :brand="$brand" :title="__('results::pdf.merit')" :subtitle="$exam->displayName().' — '.$class->displayName().($section ? ' / '.$section->displayName() : '')" />

    <table style="width: 100%; border-collapse: collapse; margin-top: 3mm;" cellpadding="0" cellspacing="0">
        <thead>
            <tr style="background-color: #16307A; color: #ffffff;">
                <th style="color: #ffffff; padding: 1.8mm;">{{ __('results::pdf.position') }}</th>
                <th style="color: #ffffff; padding: 1.8mm; text-align: left;">{{ __('results::pdf.name') }}</th>
                <th style="color: #ffffff; padding: 1.8mm;">{{ __('results::pdf.roll') }}</th>
                <th style="color: #ffffff; padding: 1.8mm;">{{ __('results::pdf.section') }}</th>
                <th style="color: #ffffff; padding: 1.8mm;">{{ __('results::pdf.total') }}</th>
                <th style="color: #ffffff; padding: 1.8mm;">GPA</th>
                <th style="color: #ffffff; padding: 1.8mm;">{{ __('results::pdf.grade') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($results as $result)
                <tr style="{{ $loop->odd ? '' : 'background-color: #F5F6F1;' }}">
                    <td style="padding: 1.6mm; text-align: center; font-weight: bold; border-bottom: 0.2mm solid #CBD1C7;">{{ bn_digits($result->position_in_class) }}</td>
                    <td style="padding: 1.6mm; border-bottom: 0.2mm solid #CBD1C7;">{{ $result->student->displayName() }}</td>
                    <td style="padding: 1.6mm; text-align: center; border-bottom: 0.2mm solid #CBD1C7;">{{ $result->roll ? bn_digits($result->roll) : '—' }}</td>
                    <td style="padding: 1.6mm; text-align: center; border-bottom: 0.2mm solid #CBD1C7;">{{ $result->section?->displayName() ?? '—' }}</td>
                    <td style="padding: 1.6mm; text-align: center; border-bottom: 0.2mm solid #CBD1C7;">{{ bn_number($result->total_marks) }}</td>
                    <td style="padding: 1.6mm; text-align: center; font-weight: bold; border-bottom: 0.2mm solid #CBD1C7;">{{ bn_number($result->gpa, 2) }}</td>
                    <td style="padding: 1.6mm; text-align: center; border-bottom: 0.2mm solid #CBD1C7;">{{ $result->grade }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
</body>
</html>
