@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.admit'))

@section('content')
    <div class="space-y-4">
        <h1 class="font-head text-2xl text-heading">{{ __('student-portal::messages.admit') }}</h1>
        @forelse ($rows as $row)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-surface p-4 shadow-sm">
                <div><p class="font-head text-lg text-heading">{{ $row['exam']->displayName() }}</p>
                    @unless ($row['eligibility']->allowed)<p class="text-sm text-warn">{{ $row['eligibility']->reason }}</p>@endunless</div>
                @if ($row['eligibility']->allowed)
                    <a href="{{ route('portal.admit.pdf', $row['exam']) }}" target="_blank" class="rounded-md bg-brand px-5 py-2.5 font-semibold text-white no-underline">⬇ {{ __('student-portal::messages.download') }}</a>
                @elseif ($row['eligibility']->actionRoute && \Illuminate\Support\Facades\Route::has($row['eligibility']->actionRoute))
                    <a href="{{ route($row['eligibility']->actionRoute) }}" class="rounded-md bg-red-600 px-5 py-2.5 font-semibold text-white no-underline">{{ __('student-portal::messages.pay_now') }}</a>
                @endif
            </div>
        @empty
            <p class="text-ink-muted">{{ __('student-portal::messages.no_admit') }}</p>
        @endforelse
    </div>
@endsection
