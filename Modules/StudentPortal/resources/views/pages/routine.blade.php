@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.nav.routine'))

@section('content')
    <div data-screen-label="Portal routine" style="display: grid; gap: 10px;">
        @forelse ($rows as $row)
            <div style="padding: 14px 16px; border-radius: 16px; background: var(--navy-50); color: var(--navy-800); font-weight: 700; {{ $loop->first ? '' : 'margin-top: 6px;' }}">{{ $row['exam']->displayName() }}@if ($student->schoolClass) · {{ $student->schoolClass->displayName() }}@endif</div>
            @foreach ($row['routine'] as $paper)
                <div style="display: grid; grid-template-columns: 64px 1fr; gap: 14px; align-items: center; padding: 12px; border-radius: 16px; background: var(--surface); border: 1px solid var(--line);">
                    <span style="display: grid; text-align: center; line-height: 1.45; padding: 8px 0; border-radius: 12px; background: var(--green-700); color: #fff;"><span class="pp-head" style="font-size: 22px;">{{ bn_digits($paper->exam_date->day) }}</span><span style="font-size: 12px; font-weight: 700;">{{ \Modules\Core\Support\Bangla::monthName($paper->exam_date) }}</span></span>
                    <span style="display: grid; line-height: 1.45;"><strong style="font-size: 17px;">{{ $paper->subject->displayName() }}</strong><span style="color: var(--ds-muted); font-size: 14px;">{{ \Modules\Core\Support\Bangla::weekday($paper->exam_date) }} · {{ bn_digits(substr($paper->start_time, 0, 5)) }} – {{ bn_digits(substr($paper->end_time, 0, 5)) }}@if ($paper->room) · {{ __('student-portal::messages.room') }} {{ bn_digits($paper->room) }}@endif</span></span>
                </div>
            @endforeach
        @empty
            @include('student-portal::partials.empty', ['title' => __('student-portal::messages.no_routine')])
        @endforelse
    </div>
@endsection
