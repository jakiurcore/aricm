@extends('core::layouts.public')

@section('title', __('website::messages.routine.title'))

@section('content')
    @php
        $weekday = fn ($date) => \Modules\Core\Support\Bangla::weekday($date);
        $monthLabel = fn ($date) => app()->getLocale() === 'bn' ? \Modules\Core\Support\Bangla::monthName($date) : $date->format('M');
        $timeLabel = fn ($row) => bn_digits(substr($row->start_time, 0, 5)).' – '.bn_digits(substr($row->end_time, 0, 5));
    @endphp
    <div style="max-width: 960px; margin: 0 auto; padding: 40px 16px 0; display: grid; gap: 20px;">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: end; justify-content: space-between;">
            <div style="display: grid; gap: 4px;"><h1 class="pub-h1-sm">{{ __('website::messages.routine.title') }}</h1><span style="color: var(--muted);">{{ __('website::messages.routine.sub') }}</span></div>
            @if ($exams->isNotEmpty() && $routine->isNotEmpty())
                <button type="button" onclick="window.print()" style="height: 44px; padding: 0 18px; border-radius: 999px; border: 0; background: var(--red-600); color: #fff; font-weight: 700; cursor: pointer;">⬇ {{ __('website::messages.routine.pdf') }}</button>
            @endif
        </div>

        @if ($exams->isEmpty())
            <div style="padding: 40px 20px; border-radius: 20px; border: 2px dashed var(--line-strong); text-align: center; display: grid; gap: 6px;"><x-core::icon name="calendar" size="32" badge /><strong style="font-size: 18px;">{{ __('website::messages.routine.none') }}</strong><span style="color: var(--muted);">{{ __('website::messages.routine.none_body') }}</span></div>
        @else
            <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr)); gap: 12px; padding: 16px; border-radius: 20px; background: var(--surface); border: 1px solid var(--line);">
                <label class="pub-field"><span>① {{ __('website::messages.routine.exam') }}</span>
                    <select name="exam" onchange="this.form.submit()" class="pub-input">
                        @foreach ($exams as $item)<option value="{{ $item->id }}" @selected($exam?->id === $item->id)>{{ $item->displayName() }}</option>@endforeach
                    </select>
                </label>
                <label class="pub-field"><span>② {{ __('website::messages.routine.class') }}</span>
                    <select name="class" onchange="this.form.submit()" class="pub-input">
                        @foreach ($exam->classes as $item)<option value="{{ $item->id }}" @selected($class?->id === $item->id)>{{ $item->displayName() }}</option>@endforeach
                    </select>
                </label>
                <noscript><button class="pub-btn-green">{{ __('website::messages.routine.show') }}</button></noscript>
            </form>

            @if ($routine->isEmpty())
                <div style="padding: 40px 20px; border-radius: 20px; border: 2px dashed var(--line-strong); text-align: center; display: grid; gap: 6px;"><x-core::icon name="calendar" size="32" badge /><strong style="font-size: 18px;">{{ __('website::messages.routine.empty') }}</strong><span style="color: var(--muted);">{{ __('website::messages.routine.empty_body') }}</span></div>
            @else
                <div class="hidden md:block" style="border-radius: 20px; overflow: hidden; border: 1px solid var(--line); background: var(--surface);">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead><tr style="background: var(--green-700); color: #fff; text-align: left;"><th style="padding: 12px 16px;">{{ __('website::messages.routine.date') }}</th><th style="padding: 12px 16px;">{{ __('website::messages.routine.day') }}</th><th style="padding: 12px 16px;">{{ __('website::messages.routine.subject') }}</th><th style="padding: 12px 16px;">{{ __('website::messages.routine.time') }}</th></tr></thead>
                        <tbody>
                            @foreach ($routine as $row)
                                <tr style="border-top: 1px solid var(--line); background: {{ $loop->odd ? 'var(--surface)' : 'var(--surface-2)' }};"><td style="padding: 12px 16px; font-weight: 600;">{{ bn_date($row->exam_date) }}</td><td style="padding: 12px 16px; color: var(--muted);">{{ $weekday($row->exam_date) }}</td><td style="padding: 12px 16px; font-weight: 600;">{{ $row->subject->displayName() }}</td><td style="padding: 12px 16px;">{{ $timeLabel($row) }}@if ($row->room) <span style="color: var(--muted);">· {{ __('website::messages.routine.room') }} {{ bn_digits($row->room) }}</span>@endif</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="md:hidden" style="display: grid; gap: 10px;">
                    @foreach ($routine as $row)
                        <div style="display: grid; grid-template-columns: 64px 1fr; gap: 14px; align-items: center; padding: 12px; border-radius: 16px; background: var(--surface); border: 1px solid var(--line);">
                            <span style="display: grid; text-align: center; line-height: 1.45; padding: 8px 0; border-radius: 12px; background: var(--green-700); color: #fff;"><span style="font-family: var(--f-head); font-size: 22px;">{{ bn_digits($row->exam_date->format('j')) }}</span><span style="font-size: 12px; font-weight: 700;">{{ $monthLabel($row->exam_date) }}</span></span>
                            <span style="display: grid; line-height: 1.45;"><strong style="font-size: 17px;">{{ $row->subject->displayName() }}</strong><span style="color: var(--muted); font-size: 14px;">{{ $weekday($row->exam_date) }} · {{ $timeLabel($row) }}@if ($row->room) · {{ __('website::messages.routine.room') }} {{ bn_digits($row->room) }}@endif</span></span>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
@endsection
