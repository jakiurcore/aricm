@php
    $heights = [220, 300, 180, 260, 200, 320, 240, 190];
    $fallback = ['ledge', 'twoRead', 'boys', 'abacus', 'girl', 'robe', 'hijabRead', 'floorRead'];
    $items = $photos->isNotEmpty()
        ? $photos->take(8)->map(fn ($photo) => ['src' => $photo->thumbnailUrl(), 'alt' => (string) $photo->caption(), 'url' => route('gallery.public.show', $photo->album_id)])->values()
        : collect($fallback)->map(fn ($name) => ['src' => asset("images/site/{$name}.jpg"), 'alt' => '', 'url' => route('gallery.public.index')]);
@endphp
<div class="pub-wrap" style="margin-top: 64px;">
    <section style="display: grid; gap: 20px;">
        <div style="display: flex; flex-wrap: wrap; align-items: end; justify-content: space-between; gap: 12px;">
            <div style="display: grid; gap: 4px;">
                <span class="pub-kicker">{{ __('gallery::messages.kicker') }}</span>
                <h2 class="pub-h2">{{ __('gallery::messages.home_title') }}</h2>
            </div>
            <a href="{{ route('gallery.public.index') }}" class="pub-btn-outline">{{ __('gallery::messages.view_all') }} <x-core::icon name="arrow" size="18" /></a>
        </div>
        <div class="pub-masonry">
            @foreach ($items as $index => $item)
                <a href="{{ $item['url'] }}" class="pub-photo" style="display: block; height: {{ $heights[$index % count($heights)] }}px;"><img src="{{ $item['src'] }}" alt="{{ $item['alt'] }}" loading="lazy"></a>
            @endforeach
        </div>
    </section>
</div>
