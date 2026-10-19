@extends('teacher-panel::layouts.panel')

@section('title', __('teacher-panel::messages.class_day.title'))

@php
    $badge = ['published' => 'bg-success-bg text-success', 'draft' => 'bg-warn-bg text-warn', 'missing' => 'bg-danger-bg text-danger'];
    $route = fn (array $extra = []) => route('teacher.class-day', ['section_id' => $section['section_id'], ...$extra]);
@endphp

@section('content')
    <div class="space-y-5">
        <div>
            <h1 class="font-head text-2xl text-heading">{{ __('teacher-panel::messages.class_day.title') }}</h1>
            <p class="text-sm text-ink-muted">{{ $section['class_name'] }} — {{ $section['section_name'] }}</p>
        </div>

        @if ($mySections->count() > 1)
            <div class="flex gap-2 overflow-x-auto">
                @foreach ($mySections as $mine)
                    <a href="{{ route('teacher.class-day', ['section_id' => $mine['section_id'], 'date' => $date->toDateString()]) }}" class="flex-none rounded-full border px-3 py-1.5 text-sm no-underline {{ $mine['section_id'] === $section['section_id'] ? 'border-green-700 bg-green-100 font-semibold text-green-700' : 'border-line-strong' }}">{{ $mine['class_name'] }} — {{ $mine['section_name'] }}</a>
                @endforeach
            </div>
        @endif

        <div class="flex items-center justify-between gap-2 rounded-xl border border-line bg-surface p-2">
            <a href="{{ $route(['date' => $previous->toDateString()]) }}" class="min-h-10 rounded-md px-3 py-2 text-sm no-underline hover:bg-surface-2">← {{ __('teacher-panel::messages.diary.previous') }}</a>
            <div class="text-center leading-tight"><p class="font-semibold">{{ \Modules\Core\Support\Bangla::date($date) }}</p><p class="text-xs text-ink-muted">{{ \Modules\Core\Support\Bangla::weekday($date) }}</p></div>
            @if ($next)<a href="{{ $route(['date' => $next->toDateString()]) }}" class="min-h-10 rounded-md px-3 py-2 text-sm no-underline hover:bg-surface-2">{{ __('teacher-panel::messages.diary.next') }} →</a>@else<span class="w-24"></span>@endif
        </div>

        <section class="space-y-3">
            <h2 class="font-head text-lg text-heading">{{ __('teacher-panel::messages.class_day.subjects') }}</h2>
            @forelse ($rows as $row)
                @php($entry = $bySubject->get($row['subject_id']))
                <div class="rounded-xl border border-line bg-surface p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-head text-lg text-heading">{{ $row['subject'] }}</p>
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $badge[$row['status']] }}">{{ __('teacher-panel::messages.status.'.$row['status']) }}</span>
                    </div>
                    <p class="text-sm text-ink-muted">{{ __('teacher-panel::messages.class_day.by') }}: {{ $row['teacher'] }}</p>
                    @if ($entry)
                        @if ($entry->lesson)<p class="mt-2 text-sm"><strong>{{ __('teacher-panel::messages.diary.lesson') }}:</strong> {{ $entry->lesson }}</p>@endif
                        @if ($entry->homework)<p class="mt-1 text-sm"><strong>{{ __('teacher-panel::messages.diary.homework') }}:</strong> {{ $entry->homework }}</p>@endif
                    @else
                        <p class="mt-2 text-sm text-ink-muted">{{ __('teacher-panel::messages.class_day.nothing') }}</p>
                    @endif
                </div>
            @empty
                <p class="text-ink-muted">{{ __('teacher-panel::messages.diary.holiday') }}</p>
            @endforelse
        </section>

        <section class="space-y-2">
            <div class="flex items-center justify-between"><h2 class="font-head text-lg text-heading">{{ __('teacher-panel::messages.class_day.general') }}</h2>
                <a href="{{ route('teacher.diary.write', ['class_id' => $section['class_id'], 'section_id' => $section['section_id'], 'date' => $date->toDateString()]) }}" class="min-h-10 rounded-md border border-line-strong px-3 py-2 text-sm font-semibold no-underline">{{ __('teacher-panel::messages.class_day.write_general') }}</a></div>
            @foreach ($general as $entry)<p class="rounded-lg bg-surface-2 p-3 text-sm">{{ $entry->note }}</p>@endforeach
        </section>

        @php($remarks = $entries->flatMap->remarks)
        @if ($remarks->isNotEmpty())
            <section class="space-y-2">
                <h2 class="font-head text-lg text-heading">{{ __('teacher-panel::messages.class_day.remarks') }}</h2>
                <ul class="divide-y divide-line rounded-xl border border-line bg-surface text-sm">
                    @foreach ($remarks as $remark)<li class="p-3"><strong>{{ $remark->student->displayName() }}</strong> — {{ $remark->text }} <span class="text-ink-muted">({{ $remark->kind->label() }})</span></li>@endforeach
                </ul>
            </section>
        @endif
    </div>
@endsection
