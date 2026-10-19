@extends('core::layouts.base')

@section('title', __('accounts::messages.student.title'))

@section('body')
    <main class="mx-auto flex min-h-screen max-w-md flex-col justify-center gap-6 px-4 py-10">
        <header class="text-center">
            <h1 class="font-head text-2xl text-heading">{{ setting('institute_name_'.app()->getLocale()) }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('accounts::messages.student.subtitle') }}</p>
        </header>

        <form method="POST" action="{{ route('student.login.store') }}" class="space-y-4 rounded-2xl border border-line bg-surface p-6 shadow-md">
            @csrf

            <div>
                <label for="student_id" class="mb-1 block text-sm font-medium">{{ __('accounts::messages.student.student_id') }}</label>
                <input id="student_id" name="student_id" value="{{ old('student_id') }}" required autofocus autocomplete="username"
                       placeholder="ARICM-2026-0001" class="w-full rounded-md border border-line-strong bg-surface px-3 uppercase">
                @error('student_id')
                    <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium">{{ __('accounts::messages.login.password') }}</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="w-full rounded-md border border-line-strong bg-surface px-3">
                <p class="mt-1 text-xs text-ink-muted">{{ __('accounts::messages.student.password_hint') }}</p>
            </div>

            <button type="submit" class="min-h-11 w-full rounded-md bg-brand px-5 font-semibold text-white">
                {{ __('accounts::messages.login.submit') }}
            </button>
        </form>

        <p class="text-center text-sm text-ink-muted">
            <a href="{{ route('core.locale', 'bn') }}">বাংলা</a> · <a href="{{ route('core.locale', 'en') }}">English</a>
        </p>
    </main>
@endsection
