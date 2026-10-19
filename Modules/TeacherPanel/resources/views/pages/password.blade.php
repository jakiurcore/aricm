@extends('teacher-panel::layouts.panel')

@section('title', __('teacher-panel::messages.titles.password'))
@section('screen_title', __('teacher-panel::messages.titles.password'))

@php
    $input = 'h-12 w-full rounded-xl border border-line-strong bg-surface px-3 font-normal';
@endphp

@section('content')
    <form method="POST" action="{{ route('teacher.password.update') }}" x-data="{ password: '', get score() { return (this.password.length >= 6) + /[0-9]/.test(this.password) + (this.password.length >= 10) + /[^A-Za-z0-9]/.test(this.password); } }" class="mx-auto grid w-full max-w-[420px] gap-3 rounded-3xl border border-line bg-surface px-6 py-7 shadow-[var(--shadow-lg)]">
        @csrf @method('PUT')
        @if ($errors->any())<div role="alert" class="rounded-xl bg-danger-bg px-3.5 py-2.5 text-[14px] font-semibold text-danger">{{ $errors->first() }}</div>@endif
        <label class="grid gap-1 text-[14px] font-semibold">{{ __('teacher-panel::messages.password.current') }}<input type="password" name="current_password" required autocomplete="current-password" class="{{ $input }}"></label>
        <label class="grid gap-1 text-[14px] font-semibold">{{ __('teacher-panel::messages.password.new') }}<input type="password" name="password" required minlength="6" autocomplete="new-password" x-model="password" class="{{ $input }}"></label>
        <div class="grid grid-cols-4 gap-1" aria-hidden="true">
            @foreach ([0, 1, 2, 3] as $index)
                <span class="h-1.5 rounded-full" :style="`background: ${ {{ $index }} < score ? ['var(--red-600)', 'var(--yellow-400)', 'var(--green-500)', 'var(--green-700)'][score - 1] : 'var(--line)' }`"></span>
            @endforeach
        </div>
        <label class="grid gap-1 text-[14px] font-semibold">{{ __('teacher-panel::messages.password.confirm') }}<input type="password" name="password_confirmation" required autocomplete="new-password" class="{{ $input }}"></label>
        <button class="h-[52px] cursor-pointer rounded-full bg-green-700 text-[17px] font-bold text-white">{{ __('teacher-panel::messages.password.save') }}</button>
    </form>
@endsection
