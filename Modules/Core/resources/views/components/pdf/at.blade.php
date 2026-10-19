@props(['x' => 0, 'y' => 0, 'w' => null, 'h' => null, 'style' => ''])
<div style="position: absolute; left: {{ round($x, 2) }}mm; top: {{ round($y, 2) }}mm;@if ($w !== null) width: {{ round($w, 2) }}mm;@endif @if ($h !== null) height: {{ round($h, 2) }}mm;@endif {{ $style }}">{{ $slot }}</div>
