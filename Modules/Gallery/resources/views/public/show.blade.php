@extends('core::layouts.public')

@section('title', $album->title())

@section('content')
    @php
        $photos = $album->photos->map(fn ($photo) => ['url' => $photo->url(), 'thumb' => $photo->thumbnailUrl(), 'caption' => $photo->caption()])->values();
        $heights = [220, 300, 180, 260, 200, 320, 240, 190, 280, 210, 260, 230];
    @endphp
    <div style="max-width: 1240px; margin: 0 auto; padding: 32px 16px 0; display: grid; gap: 18px;" x-data="{ photos: @js($photos), current: null, label() { return this.current === null ? '' : (this.current + 1).toLocaleString(document.documentElement.lang === 'bn' ? 'bn-BD' : 'en') + ' / ' + this.photos.length.toLocaleString(document.documentElement.lang === 'bn' ? 'bn-BD' : 'en') } }" @keydown.escape.window="current = null" @keydown.arrow-right.window="current !== null && (current = (current + 1) % photos.length)" @keydown.arrow-left.window="current !== null && (current = (current - 1 + photos.length) % photos.length)">
        <a href="{{ route('gallery.public.index') }}" class="pub-btn-back">← {{ __('gallery::messages.back') }}</a>
        <div style="display: grid; gap: 2px;">
            <h1 class="pub-h2">{{ $album->title() }}</h1>
            <span style="color: var(--muted);">{{ bn_digits($photos->count()) }} {{ __('gallery::messages.photos') }}@if ($album->album_date) · {{ bn_date($album->album_date) }}@endif</span>
            @if ($album->description())<span style="color: var(--muted);">{{ $album->description() }}</span>@endif
        </div>

        <div class="pub-masonry">
            @foreach ($photos as $index => $photo)
                <div class="pub-photo" style="height: {{ $heights[$index % count($heights)] }}px;">
                    <img src="{{ $photo['thumb'] }}" alt="{{ $photo['caption'] }}" loading="lazy">
                    <button type="button" @click="current = {{ $index }}" aria-label="{{ __('gallery::messages.open') }}" style="position: absolute; top: 8px; right: 8px; width: 40px; height: 40px; border-radius: 12px; border: 0; background: rgba(0,0,0,.55); color: #fff; font-size: 18px; cursor: pointer;">⤢</button>
                </div>
            @endforeach
        </div>

        <div x-show="current !== null" x-cloak x-transition.opacity class="grid" style="position: fixed; inset: 0; z-index: 70; background: rgba(5,12,8,.92); grid-template-rows: auto 1fr auto; padding: 12px;" role="dialog" aria-modal="true">
            <div style="display: flex; justify-content: space-between; align-items: center; color: #fff; padding: 4px;"><span style="font-weight: 600;" x-text="label()"></span><button type="button" @click="current = null" aria-label="{{ __('gallery::messages.close') }}" style="width: 44px; height: 44px; border-radius: 12px; border: 1px solid rgba(255,255,255,.35); background: transparent; color: #fff; font-size: 20px; cursor: pointer;">✕</button></div>
            <div style="display: grid; place-items: center; min-height: 0; padding: 12px 0;">
                <div style="width: min(100%, 900px); aspect-ratio: 4 / 3; max-height: 100%; border-radius: 16px; overflow: hidden; background: #1b241f;">
                    <template x-if="current !== null"><img :src="photos[current].url" :alt="photos[current].caption || ''" style="width: 100%; height: 100%; object-fit: contain; display: block;"></template>
                </div>
            </div>
            <div style="display: flex; justify-content: center; gap: 12px;">
                <button type="button" @click="current = (current - 1 + photos.length) % photos.length" style="height: 48px; padding: 0 22px; border-radius: 999px; border: 0; background: #fff; color: #0B6B3A; font-weight: 700; cursor: pointer;">← {{ __('gallery::messages.previous') }}</button>
                <button type="button" @click="current = (current + 1) % photos.length" style="height: 48px; padding: 0 22px; border-radius: 999px; border: 0; background: #fff; color: #0B6B3A; font-weight: 700; cursor: pointer;">{{ __('gallery::messages.next') }} →</button>
            </div>
        </div>
    </div>
@endsection
