@extends('core::layouts.base')

@section('title', __('results::messages.public.title'))

@section('body')
    <main class="mx-auto max-w-3xl space-y-4 px-4 py-10">
        <x-results::marksheet-wrapper :result="$result" />
        <p class="text-center"><a class="text-brand underline" href="{{ route('results.public') }}">{{ __('results::messages.public.back') }}</a></p>
    </main>
@endsection
