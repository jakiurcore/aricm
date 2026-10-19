@extends('teacher-panel::layouts.panel')

@section('title', __('teacher-panel::messages.marks.title'))

@section('content')
    <div class="space-y-5">
        <h1 class="font-head text-2xl text-heading">{{ __('teacher-panel::messages.marks.title') }}</h1>

        @if (! $enabled)
            <p class="rounded-xl border border-line bg-surface p-4 text-ink-muted">{{ __('teacher-panel::messages.marks.off') }}</p>
        @elseif ($options->isEmpty())
            <p class="rounded-xl border border-line bg-surface p-4 text-ink-muted">{{ __('teacher-panel::messages.marks.none') }}</p>
        @endif

        @foreach ($options as $exam => $rows)
            <section class="space-y-2">
                <h2 class="font-head text-lg text-green-700">{{ $exam }}</h2>
                @foreach ($rows as $row)
                    <a href="{{ route('teacher.marks.entry', ['exam' => $row['exam_id'], 'class' => $row['class_id'], 'section' => $row['section_id']]) }}" class="flex items-center justify-between gap-3 rounded-xl border border-line bg-surface p-4 no-underline shadow-sm">
                        <span><span class="block font-head text-lg text-heading">{{ $row['class'] }} — {{ $row['section'] }}</span><span class="text-sm text-ink-muted">{{ collect($row['subjects'])->pluck('name')->implode('، ') }}</span></span>
                        <span class="rounded-md bg-brand px-3 py-2 text-sm font-semibold text-white">{{ __('teacher-panel::messages.marks.enter') }}</span>
                    </a>
                @endforeach
            </section>
        @endforeach
    </div>
@endsection
