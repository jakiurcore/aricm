@extends('teacher-panel::layouts.panel')

@section('title', __('teacher-panel::messages.titles.class_day'))
@section('screen_title', __('teacher-panel::messages.titles.class_day'))

@php
    use Modules\TeacherPanel\Support\PanelFormat;

    $stateChip = ['draft' => 'bg-warn-bg text-warn', 'missing' => 'bg-danger-bg text-danger'];
    $config = ['csrf' => csrf_token(), 'query' => ['section_id' => $section['section_id'], 'date' => $date->toDateString()], 'remind' => route('teacher.class-day.remind'), 'sms' => route('teacher.class-day.sms')];
@endphp

@section('content')
    <div class="grid gap-3.5" x-data="classDay(@js($config))">
        @if ($mySections->count() > 1)
            <div class="tp-scroll flex gap-2 overflow-x-auto">
                @foreach ($mySections as $mine)
                    <a href="{{ route('teacher.class-day', ['section_id' => $mine['section_id'], 'date' => $date->toDateString()]) }}" class="min-h-9 flex-none whitespace-nowrap rounded-full border px-3 py-1 text-[14px] no-underline {{ $mine['section_id'] === $section['section_id'] ? 'border-green-700 bg-green-100 font-bold text-green-700' : 'border-line-strong text-ink' }}">{{ PanelFormat::section($mine['class_name'], $mine['section_name']) }}</a>
                @endforeach
            </div>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-2.5">
            <span class="grid leading-[1.45]"><span class="text-[13px] font-bold text-navy-800">★ {{ __('teacher-panel::messages.class_teacher') }}</span><strong class="font-head text-[20px] font-bold text-heading">{{ $label }}</strong></span>
            <form method="GET" action="{{ route('teacher.class-day') }}" x-data>
                <input type="hidden" name="section_id" value="{{ $section['section_id'] }}">
                <label class="grid gap-1 text-[14px] font-bold">{{ __('teacher-panel::messages.diary.date') }}<input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ now()->toDateString() }}" @change="$el.form.submit()" class="h-12 rounded-xl border border-line-strong bg-surface px-3"></label>
            </form>
        </div>

        <div class="grid grid-cols-[repeat(auto-fit,minmax(150px,1fr))] gap-2.5">
            <div class="grid rounded-2xl bg-green-700 p-3.5 text-white"><span class="text-[13px] opacity-90">{{ __('teacher-panel::messages.class_day.written') }}</span><strong class="font-head text-[26px] font-bold leading-[1.35]">{{ bn_digits($report['written']) }}/{{ bn_digits($report['expected']) }}</strong></div>
            <div class="grid rounded-2xl bg-navy-50 p-3.5"><span class="text-[13px] text-ink-muted">{{ __('teacher-panel::messages.class_day.seen') }}</span><strong class="font-head text-[26px] font-bold leading-[1.35] text-navy-800">{{ bn_digits($report['seen']) }}/{{ bn_digits($report['total']) }}</strong></div>
            <div class="grid rounded-2xl bg-danger-bg p-3.5"><span class="text-[13px] text-danger">{{ __('teacher-panel::messages.class_day.missing_count') }}</span><strong class="font-head text-[26px] font-bold leading-[1.35] text-danger">{{ bn_digits(count($report['missing'])) }}</strong></div>
        </div>

        @unless ($report['hasEntries'])
            <div class="grid gap-1.5 rounded-[20px] border-2 border-dashed border-line-strong px-5 py-7 text-center">
                <span class="text-[32px]">📓</span>
                <strong class="text-[18px]">{{ __('teacher-panel::messages.class_day.none_title') }}</strong>
                <span class="text-ink-muted">{{ __('teacher-panel::messages.class_day.none_body') }}</span>
            </div>
        @endunless

        <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,320px),1fr))] items-start gap-3">
            <div class="overflow-hidden rounded-[20px] border border-line bg-surface">
                <div class="flex flex-wrap items-center justify-between gap-2 bg-danger-bg px-4 py-3 font-bold text-danger"><span>{{ __('teacher-panel::messages.class_day.missing_title') }}</span>
                    @if (count($report['missing']))<button type="button" @click="notify('remind')" :disabled="busy" class="h-9 min-h-0 cursor-pointer rounded-full bg-danger px-3 text-[13px] font-bold text-white">{{ __('teacher-panel::messages.class_day.remind_all') }}</button>@endif
                </div>
                @foreach ($report['missing'] as $row)
                    <div class="flex items-center gap-2.5 border-t border-line px-4 py-2.5"><span class="grid flex-1 leading-[1.4]"><strong>{{ $row['subject'] }}</strong><span class="text-[13px] text-ink-muted">{{ $row['teacher'] }}</span></span><span class="rounded-full px-2.5 py-px text-[12px] font-bold {{ $stateChip[$row['status']] }}">{{ __('teacher-panel::messages.class_day.state.'.$row['status']) }}</span></div>
                @endforeach
                @if (! count($report['missing']))<div class="px-4 py-3.5 font-semibold text-success">✓ {{ __('teacher-panel::messages.class_day.all_done') }}</div>@endif
            </div>
            <div class="overflow-hidden rounded-[20px] border border-line bg-surface">
                <div class="flex flex-wrap items-center justify-between gap-2 bg-warn-bg px-4 py-3 font-bold text-warn"><span>{{ __('teacher-panel::messages.class_day.not_seen_title', ['count' => bn_digits($report['notSeen']->count())]) }}</span>
                    @if ($report['notSeen']->isNotEmpty())<button type="button" @click="notify('sms')" :disabled="busy" class="h-9 min-h-0 cursor-pointer rounded-full bg-navy-800 px-3 text-[13px] font-bold text-white">{{ __('teacher-panel::messages.class_day.send_sms') }}</button>@endif
                </div>
                @foreach ($report['notSeen'] as $student)
                    <div class="flex items-center gap-2.5 border-t border-line px-4 py-2"><span class="w-9 text-center text-[14px] font-bold text-ink-muted">{{ bn_digits($student->roll) }}</span><span class="flex-1">{{ $student->displayName() }}</span></div>
                @endforeach
            </div>
        </div>

        <h3 class="mx-0 mb-0 mt-1 font-head text-[18px] font-bold text-heading">{{ __('teacher-panel::messages.class_day.full_day') }}</h3>
        <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,340px),1fr))] items-start gap-3">
            @foreach ($report['entries'] as $entry)
                @php($rows = array_filter([
                    ['k' => __('teacher-panel::messages.diary.fields.lesson'), 'v' => $entry->lesson], ['k' => __('teacher-panel::messages.diary.fields.next_lesson'), 'v' => $entry->next_lesson],
                    ['k' => __('teacher-panel::messages.diary.fields.homework'), 'v' => $entry->homework, 'due' => $entry->homework_due_date], ['k' => __('teacher-panel::messages.diary.fields.note'), 'v' => $entry->note],
                ], fn (array $row): bool => filled($row['v'])))
                <div class="grid gap-2.5 rounded-[20px] border border-line bg-surface p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2"><strong class="text-[17px] text-green-700">{{ $entry->subjectName() }}</strong>@unless ($entry->isPublished())<span class="rounded-full bg-warn-bg px-2.5 py-px text-[12px] font-bold text-warn">◐ {{ __('teacher-panel::messages.class_day.draft_note') }}</span>@endunless</div>
                    @foreach ($rows as $row)
                        <div class="grid gap-0.5"><span class="text-[13px] font-bold text-ink-muted">{{ $row['k'] }}</span><span class="whitespace-pre-line leading-[1.6]">{{ $row['v'] }}</span>
                            @if (! empty($row['due']))<span class="justify-self-start rounded-full bg-yellow-50 px-2.5 py-px text-[12px] font-bold text-warn">{{ __('teacher-panel::messages.class_day.due', ['date' => PanelFormat::shortDate($row['due'])]) }}</span>@endif
                        </div>
                    @endforeach
                    @if ($entry->attachments->isNotEmpty())
                        <div class="grid gap-0.5"><span class="text-[13px] font-bold text-ink-muted">{{ __('teacher-panel::messages.diary.attachments') }}</span><span class="leading-[1.6]">{{ $entry->attachments->map(fn ($file) => ($file->isImage() ? '🖼 ' : '📄 ').$file->original_name)->implode(' · ') }}</span></div>
                    @endif
                    <span class="border-t border-line pt-2 text-[13px] text-ink-muted">{{ $entry->teacher?->displayName() }} · {{ PanelFormat::time($entry->isPublished() ? $entry->published_at : $entry->updated_at) }}@if ($entry->isPublished()) · 👁 {{ bn_digits($report['seenCounts'][$entry->id] ?? 0) }}/{{ bn_digits($report['total']) }}@endif</span>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('classDay', (cfg) => ({
                busy: false,
                async notify(kind) {
                    this.busy = true;
                    try {
                        const response = await fetch(cfg[kind] + '?' + new URLSearchParams(cfg.query), { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': cfg.csrf } });
                        const data = await response.json().catch(() => ({}));
                        window.dispatchEvent(new CustomEvent('tp-toast', { detail: data.message || 'Error' }));
                    } finally { this.busy = false; }
                },
            }));
        });
    </script>
@endsection
