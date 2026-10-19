<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: notosansbengali; font-size: 8.5pt; color: #17212B; }
</style>
</head>
<body>
<div style="padding: 9mm 11mm 0 11mm;">
    <x-core::pdf.letterhead :brand="$brand" :title="$table->title" :subtitle="bn_date(now())" />

    <table style="width: 100%; border-collapse: collapse; margin-top: 3mm;" cellpadding="0" cellspacing="0">
        <thead>
            <tr style="background-color: #0B6B3A; color: #ffffff;">
                @foreach ($table->columns as $column)
                    <th style="color: #ffffff; padding: 1.5mm; text-align: {{ in_array($column['type'], ['money', 'int']) ? 'right' : 'left' }};">{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($table->rows as $row)
                <tr>
                    @foreach ($table->columns as $column)
                        <td style="padding: 1.3mm; border-bottom: 0.2mm solid #CBD1C7; text-align: {{ in_array($column['type'], ['money', 'int']) ? 'right' : 'left' }};">{{ $table->display($column['type'], $row[$column['key']] ?? null) }}</td>
                    @endforeach
                </tr>
            @endforeach
            @if ($table->totals)
                <tr style="background-color: #EEF8F1; font-weight: bold;">
                    @foreach ($table->columns as $index => $column)
                        <td style="padding: 1.5mm; text-align: {{ in_array($column['type'], ['money', 'int']) ? 'right' : 'left' }};">
                            {{ isset($table->totals[$column['key']]) ? $table->display($column['type'], $table->totals[$column['key']]) : ($index === 0 ? __('fees::reports.total') : '') }}
                        </td>
                    @endforeach
                </tr>
            @endif
        </tbody>
    </table>
</div>
</body>
</html>
