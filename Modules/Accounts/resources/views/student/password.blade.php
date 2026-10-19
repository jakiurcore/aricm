@extends('core::layouts.base')

@section('title', __('accounts::messages.student.change_password'))

@section('body')
    <x-core::auth-shell :title="__('accounts::messages.student.change_password')">
        <form method="POST" action="{{ route('student.password.update') }}" style="display: grid; gap: 12px;" x-data="{ password: '', score() { let s = 0; if (this.password.length >= 6) s++; if (this.password.length >= 10) s++; if (/[A-Za-z]/.test(this.password) && /[0-9]/.test(this.password)) s++; if (/[^A-Za-z0-9]/.test(this.password)) s++; return s; } }">
            @csrf
            @method('PUT')
            @if ($forced)
                <div style="padding: 12px 14px; border-radius: 12px; background: var(--warn-bg); color: var(--warn); font-weight: 600; font-size: 14px;">{{ __('accounts::messages.student.must_change') }}</div>
            @endif

            <label class="pub-field" for="current_password">{{ __('accounts::messages.student.current_password') }}
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="pub-input">
            </label>
            <label class="pub-field" for="password">{{ __('accounts::messages.student.new_password') }}
                <input id="password" name="password" type="password" required autocomplete="new-password" x-model="password" class="pub-input">
            </label>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px;" aria-hidden="true">
                <template x-for="n in 4" :key="n"><span style="height: 6px; border-radius: 999px;" :style="'background:' + (n <= score() ? (score() >= 3 ? 'var(--green-500)' : 'var(--yellow-400)') : 'var(--line)')"></span></template>
            </div>
            <label class="pub-field" for="password_confirmation">{{ __('accounts::messages.student.confirm_password') }}
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="pub-input">
            </label>
            @foreach (['current_password', 'password'] as $field)
                @error($field)<div role="alert" style="padding: 10px 14px; border-radius: 12px; background: var(--danger-bg); color: var(--danger); font-weight: 600; font-size: 14px;">{{ $message }}</div>@enderror
            @endforeach
            <button type="submit" style="height: 52px; border: 0; border-radius: 999px; background: var(--green-700); color: #fff; font-weight: 700; font-size: 17px; cursor: pointer;">{{ __('accounts::messages.student.save_password') }}</button>
        </form>
    </x-core::auth-shell>
@endsection
