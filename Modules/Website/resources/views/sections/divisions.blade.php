<section class="bg-surface-2 py-12">
    <div class="mx-auto max-w-page px-4">
        <h2 class="mb-8 text-center font-head text-3xl text-heading">{{ __('website::messages.divisions.title') }}</h2>
        <div class="grid gap-5 md:grid-cols-2">
            @foreach (['kids', 'junior'] as $division)
                <div class="rounded-2xl border border-line bg-surface p-6 shadow-sm">
                    <p class="font-head text-2xl text-green-700">{{ __("website::messages.divisions.{$division}") }}</p>
                    <p class="mb-3 text-lg font-semibold text-red-600">{{ __("website::messages.divisions.{$division}_age") }}</p>
                    <p class="text-ink-muted">{{ __("website::messages.divisions.{$division}_body") }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
