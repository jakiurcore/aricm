@extends('teacher-panel::layouts.panel')

@section('title', __('teacher-panel::messages.titles.home'))
@section('screen_title', __('teacher-panel::messages.titles.home'))
@section('fab', true)

@php
    use Modules\Core\Support\Bangla;
    use Modules\TeacherPanel\Support\PanelFormat;

    $status = [
        'missing' => ['icon' => '○', 'chip' => 'bg-danger-bg text-danger', 'edge' => 'border-t-danger'],
        'draft' => ['icon' => '◐', 'chip' => 'bg-warn-bg text-warn', 'edge' => 'border-t-warn'],
        'published' => ['icon' => '✓', 'chip' => 'bg-success-bg text-success', 'edge' => 'border-t-success'],
    ];
@endphp

@section('content')
    <div class="grid gap-4">
        <div class="flex flex-wrap items-center gap-4 rounded-[24px] bg-[linear-gradient(135deg,var(--green-700),var(--green-900))] p-5 text-white">
            <div class="size-[72px] flex-none overflow-hidden rounded-full border-[3px] border-white bg-[rgba(255,255,255,.15)]">@include('teacher-panel::partials.photo-slot', ['glyph' => 20])</div>
            <div class="grid min-w-[200px] flex-1 leading-[1.45]">
                <span class="text-[15px] opacity-90">{{ __('teacher-panel::messages.greeting') }}</span>
                <strong class="font-head text-[22px] font-bold">{{ $teacher->displayName() }}</strong>
                <span class="text-[14px] opacity-90">{{ $teacher->designation }}</span>
            </div>
            <div class="grid flex-[1_1_220px] gap-1.5">
                <span class="rounded-[14px] bg-[rgba(255,255,255,.12)] px-3 py-2 text-[14px] leading-[1.45]">{{ __('teacher-panel::messages.today') }}<br><strong class="text-[16px]">{{ PanelFormat::fullDate($today) }}</strong></span>
                <span class="rounded-[14px] bg-[#FFD21F] px-3 py-2 text-[14px] leading-[1.45] text-[#17212B]">{{ __('teacher-panel::messages.next_day') }}<br><strong class="text-[16px]">{{ PanelFormat::fullDate($next) }}</strong></span>
            </div>
        </div>

        @if ($cards->isEmpty() && ! $classTeacher)
            <div class="rounded-[20px] border border-line bg-surface p-5 text-ink-muted">{{ __('teacher-panel::messages.no_assignments') }}</div>
        @elseif (! $isSchoolDay)
            <div class="rounded-[20px] border border-line bg-surface p-5 text-ink-muted">{{ __('teacher-panel::messages.holiday') }}</div>
        @else
            @if ($cards->isNotEmpty() && ! $hasProgress)
                <div class="grid justify-items-center gap-2 rounded-[20px] border-2 border-dashed border-line-strong bg-surface px-5 py-7 text-center">
                    <span class="text-[34px]">📓</span>
                    <strong class="text-[18px]">{{ __('teacher-panel::messages.home.empty_title') }}</strong>
                    <span class="max-w-[420px] text-ink-muted">{{ __('teacher-panel::messages.home.empty_body') }}</span>
                    <a href="{{ route('teacher.diary.write') }}" class="flex h-12 items-center rounded-full bg-green-700 px-[22px] font-bold text-white no-underline">+ {{ __('teacher-panel::messages.home.empty_cta') }}</a>
                </div>
            @endif

            @if ($cards->isNotEmpty())
                <div class="flex flex-wrap items-baseline justify-between gap-2.5">
                    <h2 class="m-0 font-head text-[20px] font-bold text-heading">{{ __('teacher-panel::messages.titles.home') }}</h2>
                    <span class="text-[14px] font-semibold text-ink-muted">{{ __('teacher-panel::messages.home.summary', ['total' => bn_digits($cards->count()), 'done' => bn_digits($published)]) }}</span>
                </div>
                <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,300px),1fr))] gap-3">
                    @foreach ($cards as $card)
                        @php($look = $status[$card['status']])
                        <a href="{{ route('teacher.diary.write', $card['params']) }}" class="grid gap-2 rounded-[20px] border border-t-4 border-line {{ $look['edge'] }} bg-surface p-4 text-left text-ink no-underline shadow-sm">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="whitespace-nowrap rounded-full px-2.5 py-0.5 text-[13px] font-bold {{ $look['chip'] }}">{{ $look['icon'] }} {{ __('teacher-panel::messages.status.'.$card['status']) }}</span>
                                @if ($card['class_teacher'])<span class="whitespace-nowrap rounded-full bg-navy-50 px-2.5 py-0.5 text-[13px] font-bold text-navy-800">★ {{ __('teacher-panel::messages.class_teacher') }}</span>@endif
                            </span>
                            <strong class="text-[18px] leading-[1.45]">{{ $card['label'] }}</strong>
                            <span class="text-[14px] leading-[1.5] text-ink-muted">
                                @if ($card['status'] === 'missing') {{ __('teacher-panel::messages.home.not_written') }}
                                @elseif ($card['status'] === 'draft') {{ __('teacher-panel::messages.home.draft_saved', ['time' => PanelFormat::time($card['entry']->updated_at)]) }}
                                @else {{ __('teacher-panel::messages.home.published_meta', ['time' => PanelFormat::time($card['entry']->published_at), 'seen' => bn_digits($card['seen']), 'total' => bn_digits($card['total'])]) }}
                                @endif
                            </span>
                            <span class="flex h-10 items-center justify-self-start whitespace-nowrap rounded-full px-4 text-[15px] font-bold {{ $card['entry'] ? 'bg-surface-2 text-ink' : 'bg-green-700 text-white' }}">{{ $card['entry'] ? '✎ '.__('teacher-panel::messages.home.edit') : '+ '.__('teacher-panel::messages.home.write') }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        @endif

        @if ($classTeacher)
            <a href="{{ route('teacher.class-day', ['section_id' => $classTeacher['section_id']]) }}" class="flex flex-wrap items-center gap-3.5 rounded-[20px] border border-navy-800 bg-navy-50 p-[18px] text-left text-ink no-underline">
                <span class="grid size-12 flex-none place-items-center rounded-[14px] bg-navy-800 text-[20px] text-white">★</span>
                <span class="grid min-w-[200px] flex-1 leading-[1.5]">
                    <strong class="text-[17px] text-navy-800">{{ __('teacher-panel::messages.home.class_teacher_banner', ['label' => PanelFormat::section($classTeacher['class_name'], $classTeacher['section_name'])]) }}</strong>
                    <span class="text-[14px]">{{ __('teacher-panel::messages.home.class_teacher_short', ['total' => bn_digits($classDay['expected']), 'done' => bn_digits($classDay['written']), 'unseen' => bn_digits($classDay['notSeen']->count())]) }}</span>
                </span>
                <span class="font-bold text-navy-800">{{ __('teacher-panel::messages.home.full_day') }} →</span>
            </a>
        @endif

        @foreach ($extraItems as $item)
            <a href="{{ route($item->route) }}" class="flex flex-wrap items-center gap-3.5 rounded-[20px] border border-line bg-surface p-[18px] text-left text-ink no-underline shadow-sm">
                <span class="grid size-12 flex-none place-items-center rounded-[14px] bg-green-50 text-[20px] text-green-700">📝</span>
                <strong class="min-w-[200px] flex-1 text-[17px]">{{ $item->label }}</strong>
                <span class="font-bold text-green-700">→</span>
            </a>
        @endforeach
    </div>
@endsection
