@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.results'))

@section('content')
    <div class="space-y-4">
        <h1 class="font-head text-2xl text-heading">{{ __('student-portal::messages.results') }}</h1>
        @forelse ($rows as $row)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-surface p-4 shadow-sm">
                <div>
                    <p class="font-head text-lg text-heading">{{ $row['exam']->displayName() }}</p>
                    @if ($row['eligibility']->allowed)
                        <p class="text-sm text-ink-muted">GPA {{ bn_number($row['result']->gpa, 2) }} · {{ $row['result']->grade }} · {{ $row['result']->is_passed ? __('results::messages.passed') : __('results::messages.failed') }}</p>
                    @else
                        <p class="text-sm text-warn">{{ $row['eligibility']->reason }}</p>
                    @endif
                </div>
                @if ($row['eligibility']->allowed)
                    <a href="{{ route('portal.results.show', $row['exam']) }}" class="rounded-md bg-brand px-5 py-2.5 font-semibold text-white no-underline">{{ __('student-portal::messages.view') }}</a>
                @elseif ($row['eligibility']->actionRoute && \Illuminate\Support\Facades\Route::has($row['eligibility']->actionRoute))
                    <a href="{{ route($row['eligibility']->actionRoute) }}" class="rounded-md bg-red-600 px-5 py-2.5 font-semibold text-white no-underline">{{ __('student-portal::messages.pay_now') }}</a>
                @endif
            </div>
        @empty
            <p class="text-ink-muted">{{ __('student-portal::messages.no_results') }}</p>
        @endforelse
    </div>
@endsection
