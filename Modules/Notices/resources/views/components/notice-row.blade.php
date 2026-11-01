@props(['notice', 'list' => false])
@php
    $shownAt = $notice->shownAt();
    $day = bn_digits((int) $shownAt->format('j'));
    $month = app()->getLocale() === 'bn' ? \Modules\Core\Support\Bangla::monthName($shownAt) : $shownAt->format('M');
    $pinnedList = $list && $notice->is_pinned;
@endphp
<a href="{{ route('notices.public.show', $notice) }}" @if ($list) style="text-decoration: none; color: var(--ink); display: grid; grid-template-columns: 64px 1fr auto; gap: 14px; align-items: center; padding: 14px; border-radius: 16px; background: {{ $pinnedList ? 'var(--yellow-50)' : 'var(--surface)' }}; border: 1px solid {{ $pinnedList ? 'var(--yellow-400)' : 'var(--line)' }};" onmouseover="this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.boxShadow='none'" @else class="pub-notice" @endif>
    <span class="pub-date-tile">
        <span style="font-family: var(--f-head); font-size: 24px; font-weight: 600;">{{ $day }}</span>
        <span style="font-size: 12px; font-weight: 700;">{{ $month }}</span>
    </span>
    <span style="display: grid; gap: 4px; min-width: 0;">
        <span style="{{ $list ? 'font-weight: 600; line-height: 1.5;' : 'font-size: 16px; font-weight: 600; line-height: 1.5; text-wrap: pretty;' }}">{{ $notice->title() }}</span>
        <span style="display: flex; flex-wrap: wrap; gap: 6px; font-size: 12px; font-weight: 700;">
            <span class="pub-pill" style="background: var(--navy-50); color: var(--navy-800);">{{ $notice->category->label() }}</span>
            @if ($notice->is_pinned)<span class="pub-pill" style="background: var(--yellow-50); color: var(--warn);"><x-core::icon name="pin" size="14" /> {{ __('notices::messages.pinned') }}</span>@endif
            @if (! $list && $notice->attachment)<span class="pub-pill" style="background: var(--red-50); color: var(--danger);">PDF</span>@endif
        </span>
    </span>
    @if ($list && $notice->attachment)
        <span title="PDF" style="width: 40px; height: 40px; border-radius: 12px; background: var(--red-50); color: var(--danger); display: grid; place-items: center; font-size: 11px; font-weight: 800;">PDF</span>
    @endif
</a>
