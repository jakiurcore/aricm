<section class="mx-auto max-w-page px-4 py-12">
    <div class="mb-8 text-center">
        <h2 class="font-head text-3xl text-heading">{{ __('website::messages.program.title') }}</h2>
        <p class="mx-auto mt-2 max-w-2xl text-ink-muted">{{ __('website::messages.program.lead') }}</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach (range(1, 4) as $number)
            <div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <div class="mb-3 grid size-11 place-items-center rounded-full bg-green-100 text-xl font-bold text-green-700">{{ bn_digits($number) }}</div>
                <h3 class="font-head text-lg text-heading">{{ __("website::messages.program.benefit_{$number}_title") }}</h3>
                <p class="mt-1 text-sm text-ink-muted">{{ __("website::messages.program.benefit_{$number}_body") }}</p>
            </div>
        @endforeach
    </div>
</section>
