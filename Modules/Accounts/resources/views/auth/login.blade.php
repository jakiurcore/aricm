@extends('core::layouts.base')

@section('title', __('accounts::messages.login.title'))

@section('body')
    <main class="mx-auto flex min-h-screen max-w-md flex-col justify-center gap-6 px-4 py-10">
        <header class="text-center">
            <h1 class="font-head text-2xl text-heading">{{ setting('institute_name_'.app()->getLocale()) }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('accounts::messages.login.subtitle') }}</p>
        </header>

        <form method="POST" action="{{ route('login.store') }}" class="space-y-4 rounded-2xl border border-line bg-surface p-6 shadow-md">
            @csrf

            <div>
                <label for="email" class="mb-1 block text-sm font-medium">{{ __('accounts::messages.login.email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       class="w-full rounded-md border border-line-strong bg-surface px-3">
                @error('email')
                    <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium">{{ __('accounts::messages.login.password') }}</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="w-full rounded-md border border-line-strong bg-surface px-3">
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" value="1" class="size-4">
                {{ __('accounts::messages.login.remember') }}
            </label>

            <button type="submit" class="min-h-11 w-full rounded-md bg-brand px-5 font-semibold text-white">
                {{ __('accounts::messages.login.submit') }}
            </button>
        </form>

        <p class="text-center text-sm text-ink-muted">
            <a href="{{ route('core.locale', 'bn') }}">বাংলা</a> · <a href="{{ route('core.locale', 'en') }}">English</a>
        </p>
    </main>
@endsection
