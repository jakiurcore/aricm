@extends('core::layouts.base')

@section('title', __('accounts::messages.student.title'))

@section('body')
    <x-core::auth-shell :title="__('accounts::messages.student.title')">
        <form method="POST" action="{{ route('student.login.store') }}" style="display: grid; gap: 12px;">
            @csrf
            <label class="pub-field" for="student_id">{{ __('accounts::messages.student.student_id') }}
                <input id="student_id" name="student_id" value="{{ old('student_id') }}" required autofocus autocomplete="username" placeholder="ARICM-2026-0001" class="pub-input" style="text-transform: uppercase;">
            </label>
            <label class="pub-field" for="password">{{ __('accounts::messages.login.password') }}
                <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="••••••" class="pub-input">
            </label>
            @error('student_id')<div role="alert" style="padding: 10px 14px; border-radius: 12px; background: var(--danger-bg); color: var(--danger); font-weight: 600; font-size: 14px;">{{ $message }}</div>@enderror
            <button type="submit" style="height: 52px; border: 0; border-radius: 999px; background: var(--green-700); color: #fff; font-weight: 700; font-size: 17px; cursor: pointer;">{{ __('accounts::messages.login.submit') }}</button>
            <span style="font-size: 13px; color: var(--muted); text-align: center;">{{ __('accounts::messages.student.password_hint') }}</span>
        </form>
    </x-core::auth-shell>
@endsection
