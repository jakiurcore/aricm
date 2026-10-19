@extends('core::layouts.public')

@section('title', __('website::messages.academics.title'))

@section('content')
    <div style="max-width: 1040px; margin: 0 auto; padding: 40px 16px 0; display: grid; gap: 32px;">
        <h1 class="pub-h1">{{ __('website::messages.academics.title') }}</h1>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap: 16px;">
            @foreach (__('website::messages.academics.programs') as [$icon, $title, $body])
                <div class="pub-card" style="display: grid; gap: 8px; align-content: start;">
                    <span style="width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; font-size: 22px; background: var(--green-50);">{{ $icon }}</span>
                    <h3 style="margin: 0; font-family: var(--f-head); color: var(--heading); font-size: 22px;">{{ $title }}</h3>
                    <p style="margin: 0; color: var(--muted);">{{ $body }}</p>
                </div>
            @endforeach
        </div>
        <div style="border-radius: 20px; background: var(--surface); border: 1px solid var(--line); overflow: hidden;">
            <h2 style="margin: 0; padding: 16px 20px; font-family: var(--f-head); color: var(--heading); font-size: 22px; border-bottom: 1px solid var(--line);">{{ __('website::messages.academics.schedule_title') }}</h2>
            @foreach (__('website::messages.academics.schedule') as [$time, $what])
                <div style="display: grid; grid-template-columns: minmax(110px, 160px) 1fr; gap: 12px; padding: 12px 20px; {{ $loop->last ? '' : 'border-bottom: 1px solid var(--line);' }}">
                    <span style="font-weight: 700; color: var(--green-700);">{{ $time }}</span><span>{{ $what }}</span>
                </div>
            @endforeach
        </div>
        @if ($classes->isNotEmpty())
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap: 16px;">
                @foreach (['kids', 'junior'] as $division)
                    @php($group = $classes->filter(fn ($class) => $class->division?->value === $division))
                    <div class="pub-card" style="display: grid; gap: 12px; align-content: start;">
                        <h2 style="margin: 0; font-family: var(--f-head); color: var(--heading); font-size: 22px;">{{ __("website::messages.divisions.{$division}") }} <span style="font-size: 15px; color: var(--muted); font-family: var(--f-body);">({{ __("website::messages.divisions.{$division}_age") }} {{ __('website::messages.divisions.years') }})</span></h2>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            @forelse ($group as $class)<span style="padding: 2px 12px; border-radius: 999px; background: var(--green-50); color: var(--green-700); font-weight: 600; font-size: 14px;">{{ $class->displayName() }}</span>@empty<span style="color: var(--muted);">—</span>@endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
