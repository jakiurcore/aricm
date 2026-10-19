@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.nav.notices'))

@section('content')
    <div style="display: grid; gap: 12px; max-width: 760px; width: 100%; justify-self: center;">
        <a href="{{ route('portal.notices') }}" class="pp-btn" style="min-height: 44px; justify-self: start;">← {{ __('student-portal::messages.nav.notices') }}</a>
        <article class="pp-card" style="padding: 20px; box-shadow: var(--shadow-md); display: grid; gap: 12px;">
            <span style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center; font-size: 13px; color: var(--ds-muted);"><span class="pp-pill" style="background: var(--green-50); color: var(--green-700);">{{ $notice->category->label() }}</span>{{ bn_date($notice->shownAt()) }}</span>
            <h1 class="pp-head" style="margin: 0; color: var(--heading); font-size: 22px; line-height: 1.5; font-weight: 700;">{{ $notice->title() }}</h1>
            <div style="white-space: pre-line; line-height: 1.8;">{{ $notice->body() }}</div>
            @if ($notice->attachment_url)<a href="{{ $notice->attachment_url }}" target="_blank" rel="noopener" class="pp-btn" style="justify-self: start;">📎 {{ $notice->attachment_name }}</a>@endif
        </article>
    </div>
@endsection
