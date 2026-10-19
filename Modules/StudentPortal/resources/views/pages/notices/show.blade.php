@extends('student-portal::layouts.portal')

@section('title', $notice->title())

@section('content')
    <article class="space-y-3">
        <a href="{{ route('portal.notices') }}" class="text-sm">← {{ __('student-portal::messages.notices') }}</a>
        <p class="text-xs text-ink-muted">{{ bn_date($notice->shownAt()) }} · {{ $notice->category->label() }}</p>
        <h1 class="font-head text-2xl text-heading">{{ $notice->title() }}</h1>
        <div class="whitespace-pre-line leading-relaxed">{{ $notice->body() }}</div>
        @if ($notice->attachment_url)<a href="{{ $notice->attachment_url }}" target="_blank" class="inline-flex rounded-md border border-line-strong px-4 py-2 font-semibold no-underline">📎 {{ $notice->attachment_name }}</a>@endif
    </article>
@endsection
