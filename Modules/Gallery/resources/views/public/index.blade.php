@extends('core::layouts.public')

@section('title', __('gallery::messages.title'))

@section('content')
    <div class="mx-auto max-w-page px-4 py-10">
        <h1 class="mb-6 font-head text-3xl text-heading">{{ __('gallery::messages.title') }}</h1>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($albums as $album)
                <a href="{{ route('gallery.public.show', $album) }}" class="group block overflow-hidden rounded-xl border border-line bg-surface no-underline shadow-sm hover:shadow-md">
                    <div class="aspect-[4/3] overflow-hidden bg-surface-2">
                        @if ($album->coverUrl())<img src="{{ $album->coverUrl() }}" alt="" loading="lazy" class="size-full object-cover transition group-hover:scale-105">@endif
                    </div>
                    <div class="p-4">
                        <p class="font-head text-lg text-heading">{{ $album->title() }}</p>
                        <p class="text-sm text-ink-muted">{{ bn_digits($album->photos_count) }} {{ __('gallery::messages.photos') }}@if ($album->album_date) · {{ bn_date($album->album_date) }}@endif</p>
                    </div>
                </a>
            @empty
                <p class="text-ink-muted sm:col-span-2 lg:col-span-3">{{ __('gallery::messages.none') }}</p>
            @endforelse
        </div>
        <div class="mt-8">{{ $albums->links() }}</div>
    </div>
@endsection
