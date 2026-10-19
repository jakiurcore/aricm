@props(['brand', 'title' => null, 'subtitle' => null])

{{-- Same letterhead as the printed marksheet/admit card (Print Documents design): Arabic in green, Bangla name in blue, English in red. --}}
<table style="width: 100%; border-collapse: collapse; border-bottom: 0.6mm solid #0B6B3A;" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width: 24mm; padding-bottom: 2.5mm;"><img src="{{ $brand['logo'] }}" style="width: 20mm; height: 20mm;"></td>
        <td style="text-align: center; padding-bottom: 2.5mm;">
            @if ($brand['name_ar'])<div style="font-family: xbriyaz; font-size: 4.4mm; color: #0B6B3A; line-height: 5.6mm;">{{ $brand['name_ar'] }}</div>@endif
            <div style="font-family: notoserifbengali; font-size: 5.6mm; font-weight: bold; color: #16307A; line-height: 7mm;">{{ $brand['name_bn'] }}</div>
            @if ($brand['name_en'])<div style="font-size: 3mm; font-weight: bold; color: #D6202A; letter-spacing: 0.02em; line-height: 4.4mm;">{{ mb_strtoupper($brand['name_en']) }}</div>@endif
            <div style="font-size: 2.8mm; color: #566170; line-height: 4mm;">{{ $brand['address'] }}{{ $brand['phones'] ? ' · '.$brand['phones'] : '' }}</div>
        </td>
        <td style="width: 24mm;">&nbsp;</td>
    </tr>
</table>
@if ($title)
    <div style="margin: 2.5mm 0 0 0;">
        <x-core::pdf.pill color="#0B6B3A" :height="7.4" :padding="6" style="color: #ffffff; font-weight: bold; font-size: 3.6mm; line-height: 5.4mm;">{{ $title }}</x-core::pdf.pill>
        @if ($subtitle)<div style="text-align: center; font-size: 3mm; line-height: 4.5mm; margin-top: 1.5mm;">{{ $subtitle }}</div>@endif
    </div>
@endif
