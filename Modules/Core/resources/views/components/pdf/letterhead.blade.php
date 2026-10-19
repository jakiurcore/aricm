@props(['brand', 'title' => null, 'subtitle' => null])

<table style="width: 100%; border-collapse: collapse; border-bottom: 0.6mm solid #0B6B3A;" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width: 20mm; padding-bottom: 2mm;"><img src="{{ $brand['logo'] }}" style="width: 17mm; height: 17mm;"></td>
        <td style="text-align: center; padding-bottom: 2mm;">
            @if ($brand['name_ar'])<div style="font-family: xbriyaz; font-size: 13pt; color: #0B6B3A;">{{ $brand['name_ar'] }}</div>@endif
            <div style="font-family: notoserifbengali; font-size: 15pt; color: #16307A;">{{ $brand['name_bn'] }}</div>
            <div style="font-size: 7.5pt; color: #566170;">{{ $brand['address'] }}{{ $brand['phones'] ? ' · '.$brand['phones'] : '' }}</div>
        </td>
        <td style="width: 20mm;">&nbsp;</td>
    </tr>
</table>
@if ($title)
    <div style="text-align: center; margin: 2.5mm 0;">
        <span style="background-color: #0B6B3A; color: #ffffff; font-weight: bold; font-size: 10pt; padding: 1mm 6mm;">{{ $title }}</span>
        @if ($subtitle)<div style="font-size: 8.5pt; margin-top: 1.5mm;">{{ $subtitle }}</div>@endif
    </div>
@endif
