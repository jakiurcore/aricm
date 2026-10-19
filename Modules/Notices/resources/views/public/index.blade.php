@extends('core::layouts.public')

@section('title', __('notices::messages.title'))

@section('content')
    <div class="mx-auto max-w-page px-4 py-10">
        <h1 class="mb-6 font-head text-3xl text-heading">{{ __('notices::messages.title') }}</h1>

        <div class="mb-6 flex flex-wrap items-center gap-2">
            <a href="{{ route('notices.public.index') }}" class="rounded-full border px-4 py-1.5 text-sm no-underline {{ ! $category ? 'border-brand bg-brand text-white' : 'border-line-strong text-ink' }}">{{ __('notices::messages.all') }}</a>
            @foreach ($categories as $item)
                <a href="{{ route('notices.public.index', ['category' => $item->value]) }}" class="rounded-full border px-4 py-1.5 text-sm no-underline {{ $category === $item->value ? 'border-brand bg-brand text-white' : 'border-line-strong text-ink' }}">{{ $item->label() }}</a>
            @endforeach
            <form method="GET" class="ml-auto">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('notices::messages.search') }}" class="rounded-md border border-line-strong bg-surface px-3">
                @if ($category)<input type="hidden" name="category" value="{{ $category }}">@endif
            </form>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($notices as $notice)
                <a href="{{ route('notices.public.show', $notice) }}" class="block rounded-xl border border-line bg-surface p-5 no-underline shadow-sm hover:shadow-md">
                    <div class="mb-2 flex items-center gap-2 text-xs">
                        <span class="rounded-full bg-info-bg px-2.5 py-0.5 font-semibold text-info">{{ $notice->category->label() }}</span>
                        @if ($notice->is_pinned)<span class="rounded-full bg-warn-bg px-2.5 py-0.5 font-semibold text-warn">📌 {{ __('notices::messages.pinned') }}</span>@endif
                        <span class="text-ink-muted">{{ bn_date($notice->shownAt()) }}</span>
                    </div>
                    <h2 class="font-head text-lg text-heading">{{ $notice->title() }}</h2>
                    <p class="mt-1 line-clamp-2 text-sm text-ink-muted">{{ \Illuminate\Support\Str::limit(strip_tags($notice->body()), 140) }}</p>
                </a>
            @empty
                <p class="text-ink-muted md:col-span-2">{{ __('notices::messages.none') }}</p>
            @endforelse
        </div>

        <div class="mt-8">{{ $notices->links() }}</div>
    </div>
@endsection
