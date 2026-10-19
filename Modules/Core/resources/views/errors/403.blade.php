@extends('core::layouts.base')

@section('title', __('core::messages.errors.403_title'))

@section('body')
    <main class="mx-auto flex min-h-screen max-w-md flex-col items-center justify-center gap-4 px-4 text-center">
        <p class="text-6xl font-bold text-red-600">{{ bn_digits(403) }}</p>
        <h1 class="font-head text-2xl text-heading">{{ __('core::messages.errors.403_title') }}</h1>
        <p class="text-ink-muted">{{ __('core::messages.errors.403_body') }}</p>
        <a href="{{ url('/') }}" class="rounded-md bg-brand px-5 py-3 font-semibold text-white">{{ __('core::messages.go_home') }}</a>
    </main>
@endsection
