@extends('student-portal::layouts.portal')

@section('title', __('diary::messages.portal.title'))

@section('content')
    <div class="space-y-5">
        <h1 class="font-head text-2xl text-heading">{{ __('diary::messages.portal.title') }}</h1>

        {{-- Week strip, Saturday to Friday. --}}
        <div class="rounded-xl border border-line bg-surface p-2 shadow-sm">
            <div class="mb-2 flex items-center justify-between px-1 text-sm">
                <a href="{{ route('portal.diary', ['date' => $previousWeek->toDateString()]) }}" class="rounded-md px-2 py-1 no-underline hover:bg-surface-2">← {{ __('diary::messages.portal.week_prev') }}</a>
                <span class="font-semibold">{{ \Modules\Core\Support\Bangla::monthYear($date) }}</span>
                @if ($weekHasFuture)<a href="{{ route('portal.diary', ['date' => $nextWeek->toDateString()]) }}" class="rounded-md px-2 py-1 no-underline hover:bg-surface-2">{{ __('diary::messages.portal.week_next') }} →</a>@else<span class="w-20"></span>@endif
            </div>
            <div class="grid grid-cols-7 gap-1 text-center">
                @foreach ($week as $day)
                    @php($selected = $day['date']->isSameDay($date))
                    @php($future = $day['date']->greaterThan(now()->endOfDay()))
                    <a @if (! $future) href="{{ route('portal.diary', ['date' => $day['date']->toDateString()]) }}" @endif class="relative flex flex-col items-center rounded-lg px-1 py-2 text-xs no-underline {{ $selected ? 'bg-brand font-bold text-white' : ($future || ! $day['is_school_day'] ? 'bg-surface-2 text-faint' : 'bg-surface-2 text-ink') }}">
                        <span>{{ mb_substr(\Modules\Core\Support\Bangla::weekday($day['date']), 0, 3) }}</span>
                        <span class="text-base font-semibold">{{ bn_digits($day['date']->day) }}</span>
                        @if ($day['entries'] > 0)<span class="mt-0.5 rounded-full px-1.5 text-[10px] font-bold {{ $selected ? 'bg-white text-green-700' : 'bg-green-100 text-green-700' }}">{{ bn_digits($day['entries']) }}</span>@endif
                        @if ($day['unseen'] > 0 && ! $selected)<span class="absolute right-1 top-1 size-2 rounded-full bg-red-600"></span>@endif
                    </a>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-between gap-3">
            <h2 class="font-head text-lg text-heading">{{ \Modules\Core\Support\Bangla::date($date) }} · {{ \Modules\Core\Support\Bangla::weekday($date) }}@if ($date->isToday()) <span class="ml-1 text-sm text-green-700">({{ __('diary::messages.portal.today') }})</span>@endif</h2>
            @if ($entries->isNotEmpty() && ! $allAcknowledged)
                <form method="POST" action="{{ route('portal.diary.acknowledge-day') }}">@csrf<input type="hidden" name="date" value="{{ $date->toDateString() }}"><button class="min-h-10 rounded-md border border-line-strong px-3 text-sm font-semibold">{{ __('diary::messages.portal.acknowledge_all') }}</button></form>
            @endif
        </div>

        @forelse ($entries as $entry)
            @php($ack = $entry->acknowledgements->first())
            <article class="space-y-3 rounded-xl border border-line bg-surface p-4 shadow-sm">
                <header class="flex items-start justify-between gap-2">
                    <div>
                        <h3 class="font-head text-lg text-heading">{{ $entry->subjectName() }}@if (in_array($entry->id, $new, true)) <span class="ml-1 rounded-full bg-red-600 px-2 py-0.5 align-middle text-xs font-bold text-white">{{ __('diary::messages.portal.new') }}</span>@endif</h3>
                        <p class="text-xs text-ink-muted">{{ __('diary::messages.portal.by', ['name' => $entry->teacher->displayName()]) }}@if ($entry->last_edited_at) · {{ __('diary::messages.portal.edited') }}@endif</p>
                    </div>
                    @if ($ack?->acknowledged_at)
                        <span class="rounded-full bg-success-bg px-2.5 py-1 text-xs font-bold text-success">{{ __('diary::messages.portal.acknowledged') }}</span>
                    @else
                        <form method="POST" action="{{ route('portal.diary.acknowledge', $entry) }}">@csrf<button class="min-h-10 rounded-md bg-brand px-3 text-sm font-semibold text-white">{{ __('diary::messages.portal.acknowledge') }}</button></form>
                    @endif
                </header>

                @if ($entry->lesson)<div><p class="text-xs font-bold uppercase text-ink-muted">{{ __('diary::messages.portal.lesson') }}</p><p class="whitespace-pre-line">{{ $entry->lesson }}</p></div>@endif
                @if ($entry->homework)
                    <div class="rounded-lg bg-yellow-50 p-3">
                        <p class="text-xs font-bold uppercase text-warn">{{ __('diary::messages.portal.homework') }}@if ($entry->homework_due_date) · {{ __('diary::messages.portal.due', ['date' => bn_date($entry->homework_due_date)]) }}@endif</p>
                        <p class="whitespace-pre-line">{{ $entry->homework }}</p>
                    </div>
                @endif
                @if ($entry->note)<div><p class="text-xs font-bold uppercase text-ink-muted">{{ __('diary::messages.portal.note') }}</p><p class="whitespace-pre-line">{{ $entry->note }}</p></div>@endif

                @if ($entry->attachments->isNotEmpty())
                    <div><p class="text-xs font-bold uppercase text-ink-muted">{{ __('diary::messages.portal.attachments') }}</p>
                        <ul class="mt-1 flex flex-wrap gap-2">@foreach ($entry->attachments as $file)<li><a href="{{ $file->url() }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center rounded-md border border-line-strong px-3 text-sm">{{ $file->isImage() ? '🖼️' : '📎' }} {{ $file->original_name }}</a></li>@endforeach</ul></div>
                @endif

                @if ($entry->remarks->isNotEmpty())
                    <div class="rounded-lg border border-line bg-surface-2 p-3"><p class="mb-1 text-xs font-bold uppercase text-ink-muted">{{ __('diary::messages.portal.remarks') }}</p>
                        @foreach ($entry->remarks as $remark)<p class="text-sm"><span class="font-semibold {{ $remark->kind->value === 'praise' ? 'text-success' : ($remark->kind->value === 'concern' ? 'text-danger' : '') }}">{{ $remark->kind->label() }}:</span> {{ $remark->text }}</p>@endforeach</div>
                @endif
            </article>
        @empty
            <p class="rounded-xl border border-line bg-surface p-4 text-ink-muted">{{ $week && collect($week)->firstWhere(fn ($day) => $day['date']->isSameDay($date))['is_school_day'] ? __('diary::messages.portal.no_entries') : __('diary::messages.portal.holiday') }}</p>
        @endforelse
    </div>
@endsection
