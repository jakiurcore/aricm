@extends('core::layouts.public')

@section('title', __('website::messages.academics.title'))

@section('content')
    <div class="mx-auto max-w-page px-4 py-10">
        <h1 class="font-head text-3xl text-heading">{{ __('website::messages.academics.title') }}</h1>
        <p class="mt-2 max-w-3xl text-ink-muted">{{ __('website::messages.academics.lead') }}</p>

        <div class="mt-8 grid gap-5 md:grid-cols-2">
            @foreach (['kids', 'junior'] as $division)
                @php($group = $classes->filter(fn ($class) => $class->division?->value === $division))
                <div class="rounded-2xl border border-line bg-surface p-6 shadow-sm">
                    <p class="font-head text-2xl text-green-700">{{ __("website::messages.divisions.{$division}") }} <span class="text-base text-red-600">({{ __("website::messages.divisions.{$division}_age") }})</span></p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @forelse ($group as $class)<span class="rounded-full bg-green-50 px-3 py-1 text-sm font-medium text-green-700">{{ $class->displayName() }}</span>@empty<span class="text-sm text-ink-muted">—</span>@endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @include('website::sections.program')
@endsection
