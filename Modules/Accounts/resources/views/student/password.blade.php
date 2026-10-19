@extends('core::layouts.base')

@section('title', __('accounts::messages.student.change_password'))

@section('body')
    <main class="mx-auto flex min-h-screen max-w-md flex-col justify-center gap-6 px-4 py-10">
        <header class="text-center">
            <h1 class="font-head text-2xl text-heading">{{ __('accounts::messages.student.change_password') }}</h1>
            @if ($forced)
                <p class="mt-1 text-warn">{{ __('accounts::messages.student.must_change') }}</p>
            @endif
        </header>

        <form method="POST" action="{{ route('student.password.update') }}" class="space-y-4 rounded-2xl border border-line bg-surface p-6 shadow-md">
            @csrf
            @method('PUT')

            @foreach ([['current_password', 'current_password', 'current-password'], ['password', 'new_password', 'new-password'], ['password_confirmation', 'confirm_password', 'new-password']] as [$name, $label, $autocomplete])
                <div>
                    <label for="{{ $name }}" class="mb-1 block text-sm font-medium">{{ __('accounts::messages.student.'.$label) }}</label>
                    <input id="{{ $name }}" name="{{ $name }}" type="password" required autocomplete="{{ $autocomplete }}"
                           class="w-full rounded-md border border-line-strong bg-surface px-3">
                    @if ($name !== 'password_confirmation')
                        @error($name)
                            <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
            @endforeach

            <button type="submit" class="min-h-11 w-full rounded-md bg-brand px-5 font-semibold text-white">
                {{ __('accounts::messages.student.save_password') }}
            </button>
        </form>
    </main>
@endsection
