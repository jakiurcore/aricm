@php($divisions = site_content()->items('divisions'))
@if ($divisions->isNotEmpty())
<div class="pub-wrap" style="margin-top: 64px;">
    <section style="display: grid; gap: 24px;">
        <div style="display: grid; gap: 4px;">
            <span class="pub-kicker">{{ site_content()->text('divisions.kicker') }}</span>
            <h2 class="pub-h2">{{ site_content()->text('divisions.title') }}</h2>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap: 16px;">
            @foreach ($divisions as $index => $division)
                @php($background = ['var(--green-500)', 'var(--navy-800)'][$index] ?? 'var(--navy-800)')
                <div style="border-radius: 20px; overflow: hidden; background: var(--surface); border: 1px solid var(--line); box-shadow: var(--shadow-sm); display: grid; grid-template-columns: 116px 1fr;">
                    <div style="background: {{ $background }}; color: #fff; display: grid; place-items: center; text-align: center; padding: 12px; line-height: 1.5;">
                        <span style="display: grid;"><span style="font-family: var(--f-head); font-size: 28px;">{{ $division->metaText('age') }}</span><span style="font-size: 14px; font-weight: 600;">{{ site_content()->text('divisions.years') }}</span></span>
                    </div>
                    <div style="padding: 20px; display: grid; gap: 4px; align-content: center;">
                        <h3 style="margin: 0; font-family: var(--f-head); color: var(--heading); font-size: 24px; display: flex; align-items: center; gap: 10px;"><x-core::icon :name="$division->icon ?: ['users', 'school'][$index] ?? 'school'" size="24" />{{ $division->title() }}</h3>
                        <p style="margin: 0; color: var(--muted);">{{ $division->body() }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endif
