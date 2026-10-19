@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.routine'))

@section('content')
    <div class="space-y-6">
        <h1 class="font-head text-2xl text-heading">{{ __('student-portal::messages.routine') }}</h1>
        @forelse ($rows as $row)
            <section>
                <h2 class="mb-2 font-head text-lg text-green-700">{{ $row['exam']->displayName() }}</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($row['routine'] as $paper)
                        <div class="rounded-xl border border-line bg-surface p-4 shadow-sm">
                            <p class="font-head text-lg text-heading">{{ $paper->subject->displayName() }}</p>
                            <p class="text-sm text-ink-muted">{{ bn_date($paper->exam_date) }} · {{ \Modules\Core\Support\Bangla::weekday($paper->exam_date) }}</p>
                            <p class="text-sm">{{ bn_digits(substr($paper->start_time, 0, 5)) }} – {{ bn_digits(substr($paper->end_time, 0, 5)) }}@if ($paper->room) · {{ __('student-portal::messages.room') }} {{ bn_digits($paper->room) }}@endif</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <p class="text-ink-muted">{{ __('student-portal::messages.no_routine') }}</p>
        @endforelse
    </div>
@endsection
