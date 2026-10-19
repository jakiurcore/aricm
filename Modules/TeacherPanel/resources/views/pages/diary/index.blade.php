@extends('teacher-panel::layouts.panel')

@section('title', __('teacher-panel::messages.diary.title'))

@php
    $badge = ['published' => 'bg-success-bg text-success', 'draft' => 'bg-warn-bg text-warn', 'missing' => 'bg-danger-bg text-danger'];
@endphp

@section('content')
    <div class="space-y-5">
        <h1 class="font-head text-2xl text-heading">{{ __('teacher-panel::messages.diary.title') }}</h1>

        <div class="flex items-center justify-between gap-2 rounded-xl border border-line bg-surface p-2">
            <a href="{{ route('teacher.diary', ['date' => $previous->toDateString()]) }}" class="min-h-10 rounded-md px-3 py-2 text-sm no-underline hover:bg-surface-2">← {{ __('teacher-panel::messages.diary.previous') }}</a>
            <div class="text-center leading-tight"><p class="font-semibold">{{ \Modules\Core\Support\Bangla::date($date) }}</p><p class="text-xs text-ink-muted">{{ \Modules\Core\Support\Bangla::weekday($date) }}</p></div>
            @if ($next)<a href="{{ route('teacher.diary', ['date' => $next->toDateString()]) }}" class="min-h-10 rounded-md px-3 py-2 text-sm no-underline hover:bg-surface-2">{{ __('teacher-panel::messages.diary.next') }} →</a>@else<span class="w-24"></span>@endif
        </div>

        @unless ($isSchoolDay)
            <p class="rounded-xl border border-line bg-surface p-4 text-ink-muted">{{ __('teacher-panel::messages.diary.holiday') }}</p>
        @endunless

        @if ($isSchoolDay)
            @if (! $writable)<p class="rounded-md bg-warn-bg p-3 text-sm text-warn">{{ __('teacher-panel::messages.diary.locked') }}</p>@endif

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($rows as $row)
                    <div class="rounded-xl border border-line bg-surface p-4 shadow-sm">
                        <p class="font-head text-lg text-heading">{{ $row['subject'] }}</p>
                        <p class="text-sm text-ink-muted">{{ $row['class'] }} — {{ $row['section'] }}</p>
                        <div class="mt-3 flex items-center justify-between">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $badge[$row['status']] }}">{{ __('teacher-panel::messages.status.'.$row['status']) }}</span>
                            <a href="{{ route('teacher.diary.write', ['class_id' => $row['class_id'], 'section_id' => $row['section_id'], 'subject_id' => $row['subject_id'], 'date' => $date->toDateString()]) }}" class="min-h-10 rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white no-underline">{{ $row['status'] === 'missing' ? __('teacher-panel::messages.diary.open') : __('teacher-panel::messages.diary.edit') }}</a>
                        </div>
                    </div>
                @endforeach

                @foreach ($general as $section)
                    <div class="rounded-xl border border-dashed border-line-strong bg-surface p-4">
                        <p class="font-head text-lg text-heading">{{ __('teacher-panel::messages.diary.general') }}</p>
                        <p class="text-sm text-ink-muted">{{ $section['class_name'] }} — {{ $section['section_name'] }}</p>
                        <div class="mt-3 flex items-center justify-between">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $badge[$section['status']] }}">{{ __('teacher-panel::messages.status.'.$section['status']) }}</span>
                            <a href="{{ route('teacher.diary.write', ['class_id' => $section['class_id'], 'section_id' => $section['section_id'], 'date' => $date->toDateString()]) }}" class="min-h-10 rounded-md border border-line-strong px-4 py-2 text-sm font-semibold no-underline">{{ __('teacher-panel::messages.diary.open') }}</a>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($rows->isEmpty() && $general->isEmpty())<p class="text-ink-muted">{{ __('teacher-panel::messages.no_assignments') }}</p>@endif
        @endif
    </div>
@endsection
