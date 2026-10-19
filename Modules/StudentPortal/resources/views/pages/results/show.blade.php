@extends('student-portal::layouts.portal')

@section('title', $exam->displayName())

@section('content')
    <div class="space-y-4">
        <a href="{{ route('portal.results') }}" class="text-sm">← {{ __('student-portal::messages.results') }}</a>
        @if ($eligibility->allowed)
            <div class="flex justify-end"><a href="{{ route('portal.results.pdf', $exam) }}" target="_blank" class="rounded-md bg-brand px-5 py-2.5 font-semibold text-white no-underline">⬇ {{ __('student-portal::messages.download_marksheet') }}</a></div>
            @include('results::partials.marksheet', ['result' => $result])
        @else
            <div class="rounded-xl bg-warn-bg p-6 text-warn"><p class="font-semibold">{{ $eligibility->reason }}</p>@if ($eligibility->actionRoute && \Illuminate\Support\Facades\Route::has($eligibility->actionRoute))<a href="{{ route($eligibility->actionRoute) }}" class="mt-3 inline-block rounded-md bg-red-600 px-5 py-2.5 font-semibold text-white no-underline">{{ __('student-portal::messages.pay_now') }}</a>@endif</div>
        @endif
    </div>
@endsection
