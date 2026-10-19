@extends('teacher-panel::layouts.panel')

@section('title', __('teacher-panel::messages.titles.history'))
@section('screen_title', __('teacher-panel::messages.titles.history'))
@section('fab', true)

@php
    use Modules\TeacherPanel\Support\PanelFormat;

    $look = [
        'published' => ['icon' => '✓', 'chip' => 'bg-success-bg text-success'],
        'draft' => ['icon' => '◐', 'chip' => 'bg-warn-bg text-warn'],
        'missing' => ['icon' => '○', 'chip' => 'bg-danger-bg text-danger'],
    ];
    $count = $total + $missing->count();
    $field = 'h-12 w-full rounded-xl border border-line-strong bg-surface px-3';
@endphp

@section('content')
    <div class="grid gap-3.5">
        <form method="GET" action="{{ route('teacher.diary') }}" x-data class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,180px),1fr))] items-end gap-2.5 rounded-[20px] border border-line bg-surface p-3.5">
            <label class="grid gap-1 text-[14px] font-bold">{{ __('teacher-panel::messages.diary.date') }}<input type="date" name="date" value="{{ $filters['date'] }}" @change="$el.form.submit()" class="{{ $field }}"></label>
            <label class="grid gap-1 text-[14px] font-bold">{{ __('teacher-panel::messages.history.class') }}
                <select name="section_id" @change="$el.form.submit()" class="{{ $field }}">
                    <option value="">{{ __('teacher-panel::messages.history.all_classes') }}</option>
                    @foreach ($sections as $row)<option value="{{ $row['section_id'] }}" @selected($filters['section_id'] === $row['section_id'])>{{ PanelFormat::section($row['class_name'], $row['section_name']) }}</option>@endforeach
                </select>
            </label>
            <label class="grid gap-1 text-[14px] font-bold">{{ __('teacher-panel::messages.diary.subject') }}
                <select name="subject" @change="$el.form.submit()" class="{{ $field }}">
                    <option value="">{{ __('teacher-panel::messages.history.all_subjects') }}</option>
                    @foreach ($subjects as $row)<option value="{{ $row['id'] }}" @selected($filters['subject'] === $row['id'])>{{ $row['name'] }}</option>@endforeach
                    @if ($hasGeneral)<option value="general" @selected($filters['subject'] === 'general')>{{ __('teacher-panel::messages.diary.general') }}</option>@endif
                </select>
            </label>
            <a href="{{ route('teacher.diary') }}" class="grid h-12 place-items-center rounded-xl border border-line-strong bg-surface-2 font-semibold text-ink no-underline">{{ __('teacher-panel::messages.history.clear') }}</a>
        </form>

        <span class="text-[14px] font-semibold text-ink-muted">{{ __('teacher-panel::messages.history.count', ['count' => bn_digits($count)]) }}</span>

        @if ($isHoliday)
            <p class="rounded-[14px] bg-warn-bg px-3.5 py-3 font-semibold text-warn">{{ __('teacher-panel::messages.diary.holiday') }}</p>
        @endif

        @if ($entries->isEmpty() && $missing->isEmpty())
            <div class="grid gap-1.5 rounded-[20px] border-2 border-dashed border-line-strong px-5 py-9 text-center">
                <span class="text-[32px]">🗂</span>
                <strong class="text-[18px]">{{ __('teacher-panel::messages.history.empty_title') }}</strong>
                <span class="text-ink-muted">{{ __('teacher-panel::messages.history.empty_body') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,340px),1fr))] gap-2.5">
            @foreach ($entries as $entry)
                @php($state = $entry->isPublished() ? 'published' : 'draft')
                @php($dm = PanelFormat::dayMonth($entry->entry_date))
                <div class="grid grid-cols-[56px_minmax(0,1fr)] gap-3 rounded-[20px] border border-line bg-surface p-3.5">
                    <span class="grid content-start self-start rounded-[14px] bg-green-50 py-2 text-center leading-[1.35] text-green-700"><strong class="font-head text-[20px] font-bold">{{ $dm['day'] }}</strong><span class="text-[12px] font-bold">{{ $dm['month'] }}</span></span>
                    <div class="grid min-w-0 gap-1.5">
                        <span class="flex flex-wrap items-center gap-1.5">
                            <strong class="text-[16px] leading-[1.4]">{{ PanelFormat::section($entry->section->schoolClass->displayName(), $entry->section->displayName()) }} – {{ $entry->subjectName() }}</strong>
                            <span class="rounded-full px-2.5 py-px text-[12px] font-bold {{ $look[$state]['chip'] }}">{{ $look[$state]['icon'] }} {{ __('teacher-panel::messages.status.'.$state) }}</span>
                        </span>
                        <span class="overflow-hidden text-ellipsis whitespace-nowrap text-[14px] leading-[1.5] text-ink-muted">{{ $entry->lesson ?: ($entry->note ?: ($entry->next_lesson ?: $entry->homework)) }}</span>
                        <span class="text-[13px] text-ink-muted">{{ \Modules\Core\Support\Bangla::weekday($entry->entry_date) }} · {{ $state === 'published' ? __('teacher-panel::messages.history.published_at', ['time' => PanelFormat::time($entry->published_at)]) : __('teacher-panel::messages.history.saved_at', ['time' => PanelFormat::time($entry->updated_at)]) }}</span>
                        @if ($state === 'published')
                            @php($size = max(1, $sizes[$entry->id]))
                            <div class="grid gap-1">
                                <span class="h-2 overflow-hidden rounded-full bg-surface-2"><span class="block h-full rounded-full bg-green-500" style="width: {{ min(100, round($seen[$entry->id] * 100 / $size)) }}%"></span></span>
                                <span class="text-[13px] font-bold text-green-700">👁 {{ __('teacher-panel::messages.history.seen', ['seen' => bn_digits($seen[$entry->id]), 'total' => bn_digits($sizes[$entry->id])]) }}</span>
                            </div>
                        @endif
                        <span class="flex flex-wrap gap-2">
                            @if ($editable[$entry->id])
                                <a href="{{ route('teacher.diary.write', array_filter(['class_id' => $entry->class_id, 'section_id' => $entry->section_id, 'subject_id' => $entry->subject_id, 'date' => $entry->entry_date->toDateString()])) }}" class="flex h-10 items-center rounded-full border border-green-700 bg-transparent px-3.5 font-bold text-green-700 no-underline">✎ {{ __('teacher-panel::messages.history.edit') }}</a>
                            @else
                                <span class="text-[13px] font-semibold text-faint">🔒 {{ __('teacher-panel::messages.history.locked') }}</span>
                            @endif
                        </span>
                    </div>
                </div>
            @endforeach

            @foreach ($missing as $row)
                @php($dm = PanelFormat::dayMonth($date))
                <div class="grid grid-cols-[56px_minmax(0,1fr)] gap-3 rounded-[20px] border border-line bg-surface p-3.5">
                    <span class="grid content-start self-start rounded-[14px] bg-green-50 py-2 text-center leading-[1.35] text-green-700"><strong class="font-head text-[20px] font-bold">{{ $dm['day'] }}</strong><span class="text-[12px] font-bold">{{ $dm['month'] }}</span></span>
                    <div class="grid min-w-0 gap-1.5">
                        <span class="flex flex-wrap items-center gap-1.5">
                            <strong class="text-[16px] leading-[1.4]">{{ PanelFormat::section($row['class'], $row['section']) }} – {{ $row['subject'] }}</strong>
                            <span class="rounded-full px-2.5 py-px text-[12px] font-bold {{ $look['missing']['chip'] }}">{{ $look['missing']['icon'] }} {{ __('teacher-panel::messages.status.missing') }}</span>
                        </span>
                        <span class="text-[13px] text-ink-muted">{{ \Modules\Core\Support\Bangla::weekday($date) }} · {{ __('teacher-panel::messages.home.not_written') }}</span>
                        <span class="flex flex-wrap gap-2"><a href="{{ route('teacher.diary.write', ['class_id' => $row['class_id'], 'section_id' => $row['section_id'], 'subject_id' => $row['subject_id'], 'date' => $date->toDateString()]) }}" class="flex h-10 items-center rounded-full bg-green-700 px-3.5 font-bold text-white no-underline">+ {{ __('teacher-panel::messages.home.write') }}</a></span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
