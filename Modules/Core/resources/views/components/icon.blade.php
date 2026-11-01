@props(['name' => '', 'size' => 24, 'badge' => false, 'ring' => false])
@php
    $key = core_icon_key((string) $name);
    $svg = $key ? app(\Modules\Core\Services\IconSet::class)->svg($key, $size) : null;
    $style = '--ico: '.((int) $size).'px;'.$attributes->get('style', '');
    $bag = $attributes->except('style')->merge(['style' => $style, 'aria-hidden' => 'true', 'role' => 'presentation']);
@endphp
@if ($svg)
<span {{ $bag->class(['pub-icon', 'pub-ico' => $badge, 'pub-ico-ring' => $badge && $ring])->merge(['data-icon' => $key]) }}>{!! $svg !!}</span>
@elseif (filled($name))
<span {{ $bag->class(['pub-icon', 'pub-icon-text', 'pub-ico' => $badge]) }}>{{ $name }}</span>
@endif
