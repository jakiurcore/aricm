@extends('core::layouts.base')

@section('title', __('accounts::messages.login.title'))

@section('body')
    <x-core::auth-shell :title="__('accounts::messages.login.subtitle')" tone="navy">
        <form method="POST" action="{{ route('login.store') }}" style="display: grid; gap: 12px;">
            @csrf
            <label class="pub-field" for="email">{{ __('accounts::messages.login.email') }}
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="pub-input">
            </label>
            <label class="pub-field" for="password">{{ __('accounts::messages.login.password') }}
                <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="••••••" class="pub-input">
            </label>
            @error('email')<div role="alert" style="padding: 10px 14px; border-radius: 12px; background: var(--danger-bg); color: var(--danger); font-weight: 600; font-size: 14px;">{{ $message }}</div>@enderror
            <label style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                <input type="checkbox" name="remember" value="1" style="width: 18px; height: 18px; min-height: 0; accent-color: var(--green-700);">
                {{ __('accounts::messages.login.remember') }}
            </label>
            <button type="submit" style="height: 52px; border: 0; border-radius: 999px; background: var(--green-700); color: #fff; font-weight: 700; font-size: 17px; cursor: pointer;">{{ __('accounts::messages.login.submit') }}</button>
        </form>
    </x-core::auth-shell>
@endsection
