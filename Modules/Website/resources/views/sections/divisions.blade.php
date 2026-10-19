<div class="pub-wrap" style="margin-top: 64px;">
    <section style="display: grid; gap: 24px;">
        <div style="display: grid; gap: 4px;">
            <span class="pub-kicker">{{ __('website::messages.divisions.kicker') }}</span>
            <h2 class="pub-h2">{{ __('website::messages.divisions.title') }}</h2>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap: 16px;">
            @foreach (['kids' => 'var(--green-500)', 'junior' => 'var(--navy-800)'] as $division => $background)
                <div style="border-radius: 20px; overflow: hidden; background: var(--surface); border: 1px solid var(--line); box-shadow: var(--shadow-sm); display: grid; grid-template-columns: 116px 1fr;">
                    <div style="background: {{ $background }}; color: #fff; display: grid; place-items: center; text-align: center; padding: 12px; line-height: 1.5;">
                        <span style="display: grid;"><span style="font-family: var(--f-head); font-size: 28px;">{{ __("website::messages.divisions.{$division}_age") }}</span><span style="font-size: 14px; font-weight: 600;">{{ __('website::messages.divisions.years') }}</span></span>
                    </div>
                    <div style="padding: 20px; display: grid; gap: 4px; align-content: center;">
                        <h3 style="margin: 0; font-family: var(--f-head); color: var(--heading); font-size: 24px;">{{ __("website::messages.divisions.{$division}") }}</h3>
                        <p style="margin: 0; color: var(--muted);">{{ __("website::messages.divisions.{$division}_body") }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>
