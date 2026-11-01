@extends('core::layouts.public')

@section('title', __('gallery::messages.title'))

@section('content')
    @php($fallback = ['court', 'robe', 'abacus', 'ledge', 'tower', 'campus'])
    <div style="max-width: 1240px; margin: 0 auto; padding: 40px 16px 0; display: grid; gap: 20px;">
        <h1 class="pub-h1-sm">{{ __('gallery::messages.title') }}</h1>
        @if ($albums->isEmpty())
            <div style="padding: 40px 20px; border-radius: 20px; border: 2px dashed var(--line-strong); text-align: center; display: grid; gap: 6px;"><x-core::icon name="camera" size="32" badge /><strong style="font-size: 18px;">{{ __('gallery::messages.none') }}</strong></div>
        @else
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 260px), 1fr)); gap: 16px;">
                @foreach ($albums as $album)
                    <a href="{{ route('gallery.public.show', $album) }}" style="display: block; border-radius: 20px; overflow: hidden; background: var(--surface); border: 1px solid var(--line); box-shadow: var(--shadow-sm); text-decoration: none; color: var(--ink);">
                        <div style="aspect-ratio: 4 / 3; background: var(--surface-2); overflow: hidden;">
                            <img src="{{ $album->coverUrl() ?: asset('images/site/'.$fallback[$loop->index % count($fallback)].'.jpg') }}" alt="" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                        </div>
                        <div style="padding: 14px 16px; display: grid; gap: 2px;">
                            <strong style="font-size: 17px; color: var(--ink);">{{ $album->title() }}</strong>
                            <span style="font-size: 14px; color: var(--muted);">{{ bn_digits($album->photos_count) }} {{ __('gallery::messages.photos') }}@if ($album->album_date) · {{ bn_date($album->album_date) }}@endif <x-core::icon name="arrow" size="14" /></span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
        <div>{{ $albums->links() }}</div>
    </div>
@endsection
