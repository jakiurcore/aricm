@php($identity = app(\Modules\Core\Services\PublicSite::class)->identity())
<section class="border-b border-line bg-green-50">
    <div class="mx-auto grid max-w-page items-center gap-8 px-4 py-12 md:grid-cols-[1.2fr_1fr]">
        <div>
            <p class="mb-2 inline-block rounded-full bg-yellow-50 px-3 py-1 text-sm font-semibold text-warn">{{ __('website::messages.hero.badge') }}</p>
            <h1 class="font-head text-4xl leading-tight text-heading md:text-5xl">{{ $identity['tagline'] }}</h1>
            <p class="mt-4 max-w-xl text-lg text-ink-muted">{{ __('website::messages.hero.lead', ['name' => $identity['name']]) }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
                @if (Route::has('admission.public'))<a href="{{ route('admission.public') }}" class="rounded-md bg-red-600 px-6 py-3 font-semibold text-white no-underline shadow-md">{{ __('website::messages.hero.apply') }}</a>@endif
                @if (Route::has('results.public'))<a href="{{ route('results.public') }}" class="rounded-md border border-line-strong bg-surface px-6 py-3 font-semibold text-ink no-underline">{{ __('website::messages.hero.results') }}</a>@endif
            </div>
        </div>
        <div class="flex justify-center"><img src="{{ $identity['logo'] }}" alt="{{ $identity['name'] }}" class="size-56 rounded-full border-4 border-yellow-400 bg-white object-cover shadow-lg md:size-72"></div>
    </div>
</section>
