@extends('core::layouts.public')

@section('title', __('website::messages.about.title'))

@section('content')
    @php($identity = app(\Modules\Core\Services\PublicSite::class)->identity())
    <div class="mx-auto max-w-4xl px-4 py-10">
        <h1 class="font-head text-3xl text-heading">{{ __('website::messages.about.title') }}</h1>
        <p class="mt-4 text-lg leading-relaxed">{{ __('website::messages.about.intro', ['name' => $identity['name'], 'year' => $identity['established'], 'address' => $identity['address']]) }}</p>

        <div class="mt-8 grid gap-4 md:grid-cols-3">
            @foreach (['mission', 'vision', 'values'] as $key)
                <div class="rounded-xl border border-line bg-surface p-5 shadow-sm"><h2 class="font-head text-xl text-green-700">{{ __("website::messages.about.{$key}_title") }}</h2><p class="mt-2 text-ink-muted">{{ __("website::messages.about.{$key}_body") }}</p></div>
            @endforeach
        </div>
    </div>
    @include('website::sections.principal', ['content' => $content])
@endsection
