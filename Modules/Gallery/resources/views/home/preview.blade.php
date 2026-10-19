@if ($photos->isNotEmpty())
    <section class="mx-auto max-w-page px-4 py-10">
        <div class="mb-4 flex items-end justify-between">
            <h2 class="font-head text-2xl text-heading">{{ __('gallery::messages.title') }}</h2>
            <a href="{{ route('gallery.public.index') }}" class="text-sm font-semibold">{{ __('gallery::messages.view_all') }} →</a>
        </div>
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            @foreach ($photos as $photo)
                <a href="{{ route('gallery.public.show', $photo->album_id) }}" class="block aspect-square overflow-hidden rounded-lg border border-line bg-surface-2">
                    <img src="{{ $photo->thumbnailUrl() }}" alt="{{ $photo->caption() }}" loading="lazy" class="size-full object-cover transition hover:scale-105">
                </a>
            @endforeach
        </div>
    </section>
@endif
