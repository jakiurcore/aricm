@props(['color', 'height' => 7.4, 'padding' => 6, 'style' => '', 'align' => 'center'])
<table align="{{ $align }}" style="border-collapse: collapse;" cellpadding="0" cellspacing="0"><tr>
    <td style="width: {{ $height / 2 }}mm; height: {{ $height }}mm; padding: 0;"><img src="{{ \Modules\Core\Support\Branding::pillCap($color, true) }}" style="width: {{ $height * 0.525 }}mm; height: {{ $height }}mm; margin-right: -{{ $height * 0.025 }}mm;"></td>
    <td style="height: {{ $height }}mm; background-color: {{ $color }}; padding: 0 {{ max(0, $padding - $height / 2) }}mm; text-align: center; white-space: nowrap; {{ $style }}">{{ $slot }}</td>
    <td style="width: {{ $height / 2 }}mm; height: {{ $height }}mm; padding: 0;"><img src="{{ \Modules\Core\Support\Branding::pillCap($color, false) }}" style="margin-left: -{{ $height * 0.025 }}mm; width: {{ $height * 0.525 }}mm; height: {{ $height }}mm;"></td>
</tr></table>
