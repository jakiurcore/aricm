@if ($notices->isNotEmpty())
    <section class="mx-auto max-w-page px-4 py-10">
        <div class="mb-4 flex items-end justify-between">
            <h2 class="font-head text-2xl text-heading">{{ __('notices::messages.latest') }}</h2>
            <a href="{{ route('notices.public.index') }}" class="text-sm font-semibold">{{ __('notices::messages.view_all') }} →</a>
        </div>
        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($notices as $notice)
                <a href="{{ route('notices.public.show', $notice) }}" class="block rounded-xl border border-line bg-surface p-4 no-underline shadow-sm hover:shadow-md">
                    <div class="mb-1 flex items-center gap-2 text-xs"><span class="rounded-full bg-info-bg px-2 py-0.5 font-semibold text-info">{{ $notice->category->label() }}</span><span class="text-ink-muted">{{ bn_date($notice->shownAt()) }}</span></div>
                    <p class="font-head text-heading">{{ $notice->title() }}</p>
                </a>
            @endforeach
        </div>
    </section>
@endif
