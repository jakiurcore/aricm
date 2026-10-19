@extends('core::layouts.public')

@section('title', $notice->title())
@section('description', \Illuminate\Support\Str::limit(strip_tags($notice->body()), 160))

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-10">
        <a href="{{ route('notices.public.index') }}" class="text-sm">← {{ __('notices::messages.title') }}</a>
        <div class="mb-3 mt-4 flex items-center gap-2 text-xs">
            <span class="rounded-full bg-info-bg px-2.5 py-0.5 font-semibold text-info">{{ $notice->category->label() }}</span>
            <span class="text-ink-muted">{{ bn_date($notice->shownAt()) }}</span>
        </div>
        <h1 class="font-head text-3xl text-heading">{{ $notice->title() }}</h1>
        <div class="mt-6 whitespace-pre-line leading-relaxed">{{ $notice->body() }}</div>

        @if ($notice->attachment_url)
            <a href="{{ $notice->attachment_url }}" target="_blank" rel="noopener" class="mt-8 inline-flex items-center gap-2 rounded-md border border-line-strong px-4 py-2.5 font-semibold text-ink no-underline">
                📎 {{ $notice->attachment_name ?? __('notices::messages.attachment') }}
            </a>
        @endif
    </article>
@endsection
