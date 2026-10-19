@extends('teacher-panel::layouts.panel')

@section('title', __('teacher-panel::messages.marks.title'))
@section('screen_title', __('teacher-panel::messages.marks.title'))

@section('content')
    <div class="grid gap-3.5">
        @if (! $enabled)
            <p class="rounded-[20px] border border-line bg-surface p-5 text-ink-muted">{{ __('teacher-panel::messages.marks.off') }}</p>
        @elseif ($options->isEmpty())
            <p class="rounded-[20px] border border-line bg-surface p-5 text-ink-muted">{{ __('teacher-panel::messages.marks.none') }}</p>
        @endif

        @foreach ($options as $exam => $rows)
            <h2 class="m-0 font-head text-[20px] font-bold text-heading">{{ $exam }}</h2>
            <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,300px),1fr))] gap-3">
                @foreach ($rows as $row)
                    <a href="{{ route('teacher.marks.entry', ['exam' => $row['exam_id'], 'class' => $row['class_id'], 'section' => $row['section_id']]) }}" class="grid gap-2 rounded-[20px] border border-line bg-surface p-4 text-ink no-underline shadow-sm">
                        <strong class="text-[18px] leading-[1.45]">{{ \Modules\TeacherPanel\Support\PanelFormat::section($row['class'], $row['section']) }}</strong>
                        <span class="text-[14px] leading-[1.5] text-ink-muted">{{ collect($row['subjects'])->pluck('name')->implode('، ') }}</span>
                        <span class="flex h-10 items-center justify-self-start whitespace-nowrap rounded-full bg-green-700 px-4 text-[15px] font-bold text-white">✎ {{ __('teacher-panel::messages.marks.enter') }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </div>
@endsection
