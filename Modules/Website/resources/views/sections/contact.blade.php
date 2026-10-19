<section class="mx-auto max-w-page px-4 py-12">
    <h2 class="mb-6 font-head text-3xl text-heading">{{ __('website::messages.contact.title') }}</h2>
    <div class="grid gap-6 md:grid-cols-2">
        <div class="space-y-3">
            <p class="text-lg">{{ $address }}</p>
            @foreach ($phones as $phone)<p><a href="tel:{{ $phone }}" class="inline-flex min-h-11 items-center rounded-md bg-brand px-5 font-semibold text-white no-underline">📞 {{ bn_digits($phone) }}</a></p>@endforeach
            @if ($email)<p><a href="mailto:{{ $email }}">{{ $email }}</a></p>@endif
        </div>
        <div class="overflow-hidden rounded-xl border border-line"><iframe title="{{ __('website::messages.contact.map') }}" src="{{ $map['embed'] }}" class="h-64 w-full" loading="lazy"></iframe></div>
    </div>
</section>
