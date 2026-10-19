@extends('teacher-panel::layouts.panel')

@section('title', __('teacher-panel::messages.panel'))

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm text-ink-muted">{{ __('teacher-panel::messages.welcome') }}</p>
            <h1 class="font-head text-2xl text-heading">{{ $teacher->displayName() }}</h1>
            <p class="text-sm text-ink-muted">{{ \Modules\Core\Support\Bangla::date($today) }} · {{ \Modules\Core\Support\Bangla::weekday($today) }} — {{ $isSchoolDay ? __('teacher-panel::messages.school_day') : __('teacher-panel::messages.holiday') }}</p>
        </div>

        @if ($isSchoolDay && $expected > 0)
            <section class="rounded-xl border border-line bg-surface p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="font-head text-lg text-heading">{{ __('teacher-panel::messages.diary_progress') }}</h2>
                    <span class="text-sm font-semibold {{ $published === $expected ? 'text-success' : 'text-warn' }}">{{ __('teacher-panel::messages.of', ['done' => bn_digits($published), 'total' => bn_digits($expected)]) }}</span>
                </div>
                <div class="my-3 h-2 overflow-hidden rounded-full bg-surface-2"><div class="h-full bg-brand" style="width: {{ round($published * 100 / $expected) }}%"></div></div>
                @if ($pending->isEmpty())
                    <p class="text-success">{{ __('teacher-panel::messages.all_done') }}</p>
                @else
                    <p class="mb-2 text-sm font-semibold">{{ __('teacher-panel::messages.pending') }}</p>
                    <ul class="divide-y divide-line">
                        @foreach ($pending as $row)
                            <li class="flex items-center justify-between gap-3 py-2">
                                <span>{{ $row['class'] }} — {{ $row['section'] }} · {{ $row['subject'] }}</span>
                                <a href="{{ route('teacher.diary.write', ['class_id' => $row['class_id'], 'section_id' => $row['section_id'], 'subject_id' => $row['subject_id']]) }}" class="min-h-10 rounded-md bg-brand px-3 py-2 text-sm font-semibold text-white no-underline">{{ __('teacher-panel::messages.write_now') }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif

        <section>
            <h2 class="mb-2 font-head text-lg text-heading">{{ __('teacher-panel::messages.my_classes') }}</h2>
            @forelse ($sections as $section)
                <div class="mb-3 rounded-xl border border-line bg-surface p-4 shadow-sm">
                    <p class="font-head text-lg text-heading">{{ $section['class_name'] }} — {{ $section['section_name'] }} @if ($section['is_class_teacher'])<span class="ml-1 rounded-full bg-yellow-50 px-2 py-0.5 text-xs font-bold text-warn">{{ __('teacher-panel::messages.class_teacher') }}</span>@endif</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ collect($section['subjects'])->pluck('name')->implode('، ') ?: '—' }}</p>
                </div>
            @empty
                <p class="text-ink-muted">{{ __('teacher-panel::messages.no_assignments') }}</p>
            @endforelse
        </section>
    </div>
@endsection
