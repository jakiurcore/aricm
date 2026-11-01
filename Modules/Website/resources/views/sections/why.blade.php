@php
    $points = site_content()->items('why');
    $fallbackIcons = ['book', 'school', 'medal', 'phonetics', 'abacus', 'shield'];
@endphp
@if ($points->isNotEmpty())
<div class="pub-wrap" style="margin-top: 64px;">
    <section style="display: grid; gap: 24px;">
        <div style="display: grid; gap: 4px;">
            <span class="pub-kicker">{{ site_content()->text('why.kicker') }}</span>
            <h2 class="pub-h2">{{ site_content()->text('why.title') }}</h2>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap: 12px;">
            @foreach ($points as $index => $point)
                <div style="padding: 18px; border-radius: 16px; background: var(--surface); border: 1px solid var(--line); display: grid; grid-template-columns: 48px 1fr; gap: 14px; align-items: start;">
                    <x-core::icon :name="$point->icon ?: ($fallbackIcons[$index] ?? 'star')" size="22" badge ring style="margin: 3px;" />
                    <span style="display: grid; gap: 2px;"><span style="font-size: 17px; font-weight: 700;">{{ $point->title() }}</span><span style="color: var(--muted); font-size: 15px;">{{ $point->body() }}</span></span>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endif
