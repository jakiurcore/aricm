@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.nav.results'))

@section('content')
    <div data-screen-label="Portal results" style="display: grid; gap: 12px;">
        @forelse ($rows as $row)
            @php($allowed = $row['eligibility']->allowed)
            <div class="pp-card" style="padding: 16px; display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
                <span style="width: 52px; height: 52px; border-radius: 16px; display: grid; place-items: center; font-size: 22px; background: {{ $allowed ? 'var(--green-50)' : 'var(--warn-bg)' }};">{{ $allowed ? '🏅' : '⏳' }}</span>
                <span style="flex: 1; min-width: 180px; display: grid; line-height: 1.45;">
                    <strong style="font-size: 17px;">{{ $row['exam']->displayName() }}</strong>
                    <span style="color: var(--ds-muted); font-size: 14px;">@if ($allowed)GPA {{ bn_number($row['result']->gpa, 2) }} · {{ $row['result']->grade }}@if ($row['exam']->end_date) · {{ bn_date($row['exam']->end_date) }}@endif @else{{ $row['eligibility']->reason }}@endif</span>
                </span>
                @if ($allowed)
                    <a href="{{ route('portal.results.show', $row['exam']) }}" class="pp-btn-green" style="height: 44px; padding: 0 18px; border-radius: 999px; display: inline-flex; align-items: center; text-decoration: none; font-weight: 700; color: #fff;">{{ __('student-portal::messages.view_marksheet') }}</a>
                @elseif ($row['eligibility']->actionRoute && \Illuminate\Support\Facades\Route::has($row['eligibility']->actionRoute))
                    <a href="{{ route($row['eligibility']->actionRoute) }}" class="pp-btn-red" style="height: 44px; padding: 0 18px; border-radius: 999px; display: inline-flex; align-items: center; text-decoration: none; font-weight: 700; color: #fff;">{{ __('student-portal::messages.pay_now') }}</a>
                @else
                    <span class="pp-pill pp-warn" style="padding: 4px 12px; font-size: 14px;">⏳ {{ __('student-portal::messages.not_published') }}</span>
                @endif
            </div>
        @empty
            @include('student-portal::partials.empty', ['title' => __('student-portal::messages.no_results')])
        @endforelse
    </div>
@endsection
