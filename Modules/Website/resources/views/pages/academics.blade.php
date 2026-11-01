@extends('core::layouts.public')

@section('title', site_content()->text('academics.title'))

@section('content')
    <div style="max-width: 1040px; margin: 0 auto; padding: 40px 16px 0; display: grid; gap: 32px;">
        <h1 class="pub-h1">{{ site_content()->text('academics.title') }}</h1>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap: 16px;">
            @foreach (site_content()->items('academics_programs') as $program)
                <div class="pub-card" style="display: grid; gap: 8px; align-content: start;">
                    <x-core::icon :name="$program->icon ?: ['book', 'school', 'abacus', 'phonetics', 'medal'][$loop->index] ?? 'star'" size="24" badge ring style="margin: 4px;" />
                    <h3 style="margin: 0; font-family: var(--f-head); color: var(--heading); font-size: 22px;">{{ $program->title() }}</h3>
                    <p style="margin: 0; color: var(--muted);">{{ $program->body() }}</p>
                </div>
            @endforeach
        </div>
        @php($schedule = site_content()->items('schedule'))
        @if ($schedule->isNotEmpty())
        <div style="border-radius: 20px; background: var(--surface); border: 1px solid var(--line); overflow: hidden;">
            <h2 style="margin: 0; padding: 16px 20px; font-family: var(--f-head); color: var(--heading); font-size: 22px; border-bottom: 1px solid var(--line);">{{ site_content()->text('academics.schedule_title') }}</h2>
            @foreach ($schedule as $entry)
                <div style="display: grid; grid-template-columns: minmax(110px, 160px) 1fr; gap: 12px; padding: 12px 20px; {{ $loop->last ? '' : 'border-bottom: 1px solid var(--line);' }}">
                    <span style="font-weight: 700; color: var(--green-700);">{{ $entry->metaText('time') }}</span><span>{{ $entry->title() }}</span>
                </div>
            @endforeach
        </div>
        @endif
        @if ($classes->isNotEmpty())
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap: 16px;">
                @foreach (site_content()->items('divisions') as $division)
                    @php($group = $classes->filter(fn ($class) => $class->division?->value === ($division->meta['key'] ?? null)))
                    <div class="pub-card" style="display: grid; gap: 12px; align-content: start;">
                        <h2 style="margin: 0; font-family: var(--f-head); color: var(--heading); font-size: 22px;">{{ $division->title() }} <span style="font-size: 15px; color: var(--muted); font-family: var(--f-body);">({{ $division->metaText('age') }} {{ site_content()->text('divisions.years') }})</span></h2>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            @forelse ($group as $class)<span style="padding: 2px 12px; border-radius: 999px; background: var(--green-50); color: var(--green-700); font-weight: 600; font-size: 14px;">{{ $class->displayName() }}</span>@empty<span style="color: var(--muted);">—</span>@endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
