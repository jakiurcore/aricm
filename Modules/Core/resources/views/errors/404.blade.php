@extends('core::layouts.public')

@section('title', __('core::messages.errors.404_title'))

@section('content')
    <div style="max-width: 560px; margin: 0 auto; padding: 72px 16px 24px; display: grid; gap: 14px; justify-items: center; text-align: center;">
        <p style="margin: 0; font-family: var(--f-head); font-size: clamp(64px, 14vw, 104px); line-height: 1.1; font-weight: 700; color: var(--red-600);">{{ bn_digits(404) }}</p>
        <h1 class="pub-h2">{{ __('core::messages.errors.404_title') }}</h1>
        <p style="margin: 0; color: var(--muted); font-size: 17px; text-wrap: pretty;">{{ __('core::messages.errors.404_body') }}</p>
        <a href="{{ url('/') }}" class="pub-btn-green" style="height: 52px; padding: 0 26px; font-size: 17px; margin-top: 6px;">{{ __('core::messages.go_home') }}</a>
    </div>
@endsection
