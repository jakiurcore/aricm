@props(['settings'])

@if ($settings->bannerEnabled())
    <section class="bg-gradient-to-br from-green-700 to-green-900 text-white">
        <div class="mx-auto flex max-w-page flex-wrap items-center justify-between gap-4 px-4 py-6">
            <div>
                <p class="font-head text-3xl">{{ $settings->text('banner_title') }}</p>
                <p class="mt-1 text-white/90">
                    @if ($settings->get('admission_start')){{ __('admission::messages.admission_start') }}: <strong>{{ bn_date($settings->get('admission_start')) }}</strong>@endif
                    @if ($settings->get('class_start')) · {{ __('admission::messages.class_start') }}: <strong>{{ bn_date($settings->get('class_start')) }}</strong>@endif
                </p>
                @if ($settings->text('free_class_note'))<p class="mt-2 inline-block rounded-md bg-yellow-400 px-3 py-1 font-semibold text-ink">{{ $settings->text('free_class_note') }}</p>@endif
            </div>
            <a href="{{ route('admission.public') }}" class="rounded-md bg-red-600 px-6 py-3 text-lg font-semibold text-white no-underline shadow-md">{{ __('admission::messages.apply') }}</a>
        </div>
    </section>
@endif
