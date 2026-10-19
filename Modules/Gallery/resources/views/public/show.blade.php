@extends('core::layouts.public')

@section('title', $album->title())

@section('content')
    @php($photos = $album->photos->map(fn ($photo) => ['url' => $photo->url(), 'thumb' => $photo->thumbnailUrl(), 'caption' => $photo->caption()])->values())
    <div class="mx-auto max-w-page px-4 py-10" x-data="{ photos: @js($photos), current: null }" @keydown.escape.window="current = null" @keydown.arrow-right.window="current !== null && (current = (current + 1) % photos.length)" @keydown.arrow-left.window="current !== null && (current = (current - 1 + photos.length) % photos.length)">
        <a href="{{ route('gallery.public.index') }}" class="text-sm">← {{ __('gallery::messages.title') }}</a>
        <h1 class="mb-1 mt-3 font-head text-3xl text-heading">{{ $album->title() }}</h1>
        @if ($album->description())<p class="mb-6 text-ink-muted">{{ $album->description() }}</p>@endif

        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">
            <template x-for="(photo, index) in photos" :key="index">
                <button type="button" class="aspect-square overflow-hidden rounded-lg border border-line bg-surface-2" @click="current = index">
                    <img :src="photo.thumb" :alt="photo.caption || ''" loading="lazy" class="size-full object-cover transition hover:scale-105">
                </button>
            </template>
        </div>

        <div x-show="current !== null" x-cloak style="display: none;" x-transition.opacity class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-black/90 p-4" @click.self="current = null" role="dialog" aria-modal="true">
            <button type="button" class="absolute right-4 top-4 size-11 rounded-full bg-white/15 text-2xl text-white" @click="current = null" aria-label="{{ __('gallery::messages.close') }}">✕</button>
            <button type="button" class="absolute left-3 top-1/2 size-11 -translate-y-1/2 rounded-full bg-white/15 text-2xl text-white" @click="current = (current - 1 + photos.length) % photos.length" aria-label="{{ __('gallery::messages.previous') }}">‹</button>
            <template x-if="current !== null"><img :src="photos[current].url" :alt="photos[current].caption || ''" class="max-h-[80vh] max-w-full rounded-lg object-contain"></template>
            <p x-show="current !== null && photos[current].caption" x-text="current !== null ? photos[current].caption : ''" class="mt-3 text-center text-white"></p>
            <button type="button" class="absolute right-3 top-1/2 size-11 -translate-y-1/2 rounded-full bg-white/15 text-2xl text-white" @click="current = (current + 1) % photos.length" aria-label="{{ __('gallery::messages.next') }}">›</button>
        </div>
    </div>
@endsection
