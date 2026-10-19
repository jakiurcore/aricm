@extends('core::layouts.base')

@section('title', __('results::messages.public.title'))

@section('body')
    <main class="mx-auto flex min-h-screen max-w-3xl flex-col justify-center gap-6 px-4 py-10">
        <header class="text-center">
            <h1 class="font-head text-3xl text-heading">{{ __('results::messages.public.title') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('results::messages.public.subtitle') }}</p>
        </header>

        <div class="rounded-2xl border border-line bg-surface p-6 shadow-md">
            @if ($notFound)
                <p class="mb-4 rounded-md bg-danger-bg p-3 text-danger">{{ __('results::messages.public.not_found') }}</p>
            @endif
            @if ($blocked)
                <p class="mb-4 rounded-md bg-warn-bg p-3 text-warn">{{ $blocked }}</p>
            @endif

            @if ($exams->isEmpty())
                <p class="text-ink-muted">{{ __('results::messages.public.none_published') }}</p>
            @else
                @include('results::partials.search-form', ['exams' => $exams, 'classMap' => $classMap])
            @endif
        </div>
    </main>
@endsection
