@extends('core::layouts.public')

@section('title', $notice->title())
@section('description', \Illuminate\Support\Str::limit(strip_tags($notice->bodyHtml()), 160))

@section('content')
    <article style="max-width: 760px; margin: 0 auto; padding: 32px 16px 0; display: grid; gap: 18px;">
        <a href="{{ route('notices.public.index') }}" class="pub-btn-back">← {{ __('notices::messages.back') }}</a>
        <div style="display: flex; flex-wrap: wrap; gap: 8px; font-size: 13px; font-weight: 700;">
            <span style="padding: 2px 10px; border-radius: 999px; background: var(--navy-50); color: var(--navy-800);">{{ $notice->category->label() }}</span>
            <span style="padding: 2px 10px; border-radius: 999px; background: var(--surface-2); color: var(--muted);">{{ __('notices::messages.published') }}: {{ bn_date($notice->shownAt()) }}</span>
        </div>
        <h1 style="margin: 0; font-family: var(--f-head); color: var(--heading); font-size: clamp(26px, 4vw, 36px); line-height: 1.35; text-wrap: balance;">{{ $notice->title() }}</h1>
        <div style="padding: 22px; border-radius: 20px; background: var(--surface); border: 1px solid var(--line); font-size: 17px;">
            <div class="pub-notice-body" style="margin: 0;">{!! $notice->bodyHtml() !!}</div>
            <p style="margin: 18px 0 0; font-weight: 600;">— {{ __('notices::messages.office', ['name' => app(\Modules\Core\Services\PublicSite::class)->identity()['name']]) }}</p>
        </div>
        @if ($notice->attachment_url)
            <div style="display: flex; align-items: center; gap: 14px; padding: 14px; border-radius: 16px; border: 1px solid var(--line); background: var(--surface);">
                <span style="width: 48px; height: 48px; border-radius: 12px; background: var(--red-50); color: var(--danger); display: grid; place-items: center; font-size: 12px; font-weight: 800; flex: none;">PDF</span>
                <span style="flex: 1; min-width: 0; display: grid; line-height: 1.4;"><strong>{{ __('notices::messages.attachment') }}</strong><span style="font-size: 14px; color: var(--muted); overflow-wrap: anywhere;">{{ $notice->attachment_name ?? basename($notice->attachment) }}</span></span>
                <a href="{{ $notice->attachment_url }}" target="_blank" rel="noopener" class="pub-btn-green" style="height: 44px; padding: 0 16px;">{{ __('notices::messages.download') }}</a>
            </div>
        @endif
    </article>
@endsection
