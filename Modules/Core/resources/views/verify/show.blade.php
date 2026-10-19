@extends('core::layouts.public')

@section('title', __('core::messages.verify.title'))

@section('content')
    <div style="max-width: 560px; margin: 0 auto; padding: 40px 16px 0; display: grid; gap: 16px;">
        <div class="pub-card" style="padding: 24px; box-shadow: var(--shadow-md); border-radius: 24px; display: grid; gap: 14px;">
            @if ($result)
                <span style="justify-self: start; padding: 4px 14px; border-radius: 999px; background: var(--success-bg); color: var(--success); font-weight: 700; font-size: 14px;">✔ {{ __('core::messages.verify.valid') }}</span>
                <h1 style="margin: 0; font-family: var(--f-head); color: var(--heading); font-size: 26px; line-height: 1.35;">{{ $result->title }}</h1>
                <dl style="margin: 0; display: grid;">
                    @foreach ($result->details as $label => $value)
                        <div style="display: flex; justify-content: space-between; gap: 16px; padding: 10px 0; border-top: 1px solid var(--line);">
                            <dt style="color: var(--muted);">{{ $label }}</dt>
                            <dd style="margin: 0; text-align: right; font-weight: 600;">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <span style="justify-self: start; padding: 4px 14px; border-radius: 999px; background: var(--danger-bg); color: var(--danger); font-weight: 700; font-size: 14px;">✖ {{ __('core::messages.verify.invalid') }}</span>
                <h1 style="margin: 0; font-family: var(--f-head); color: var(--heading); font-size: 26px; line-height: 1.35;">{{ __('core::messages.verify.not_found') }}</h1>
            @endif
        </div>
    </div>
@endsection
