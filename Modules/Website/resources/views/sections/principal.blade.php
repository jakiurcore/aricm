<section class="mx-auto max-w-page px-4 py-12">
    <div class="grid items-start gap-6 rounded-2xl border border-line bg-surface p-6 shadow-sm md:grid-cols-[auto_1fr]">
        <div class="grid size-24 place-items-center rounded-full bg-green-100 font-head text-4xl text-green-700">{{ mb_substr($content->principalName(), 0, 1) }}</div>
        <div>
            <h2 class="font-head text-2xl text-heading">{{ __('website::messages.principal.title') }}</h2>
            <blockquote class="mt-3 whitespace-pre-line leading-relaxed text-ink">{{ $content->principalMessage() }}</blockquote>
            <p class="mt-3 font-semibold">— {{ $content->principalName() }}</p>
        </div>
    </div>
</section>
