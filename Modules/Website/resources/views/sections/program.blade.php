@php
    $benefits = site_content()->items('programs');
    $icons = ['🧠', '🎯', '⚡', '🏆'];
    $backgrounds = ['var(--green-50)', 'var(--navy-50)', 'var(--yellow-50)', 'var(--red-50)'];
@endphp
@if ($benefits->isNotEmpty())
<div class="pub-wrap" style="margin-top: 64px;">
    <section id="abacus" style="display: grid; gap: 24px; padding-top: 16px; scroll-margin-top: 140px;">
        <div style="display: grid; gap: 4px; max-width: 760px;">
            <span class="pub-kicker">{{ site_content()->text('program.kicker') }}</span>
            <h2 class="pub-h2" style="text-wrap: balance;">{{ site_content()->text('program.title') }}</h2>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 240px), 1fr)); gap: 16px;">
            @foreach ($benefits as $index => $benefit)
                <div class="pub-card" style="display: grid; gap: 14px; align-content: start;">
                    <span style="width: 52px; height: 52px; border-radius: 16px; display: grid; place-items: center; font-size: 24px; background: {{ $backgrounds[$index] ?? $backgrounds[0] }};">{{ $icons[$index] ?? '' }}</span>
                    <span style="font-size: 18px; font-weight: 700; line-height: 1.5; color: var(--ink);">{{ $benefit->title() }}</span>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endif
