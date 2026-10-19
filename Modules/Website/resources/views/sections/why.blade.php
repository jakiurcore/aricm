@php($icons = ['📖', '🏫', '🎖', '🔤', '🧮', '🛡'])
<div class="pub-wrap" style="margin-top: 64px;">
    <section style="display: grid; gap: 24px;">
        <div style="display: grid; gap: 4px;">
            <span class="pub-kicker">{{ __('website::messages.why.kicker') }}</span>
            <h2 class="pub-h2">{{ __('website::messages.why.title') }}</h2>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap: 12px;">
            @foreach (__('website::messages.why.items') as $index => [$title, $body])
                <div style="padding: 18px; border-radius: 16px; background: var(--surface); border: 1px solid var(--line); display: grid; grid-template-columns: 44px 1fr; gap: 14px; align-items: start;">
                    <span style="width: 44px; height: 44px; border-radius: 12px; background: var(--green-50); color: var(--green-700); display: grid; place-items: center; font-size: 20px;">{{ $icons[$index] ?? '' }}</span>
                    <span style="display: grid; gap: 2px;"><span style="font-size: 17px; font-weight: 700;">{{ $title }}</span><span style="color: var(--muted); font-size: 15px;">{{ $body }}</span></span>
                </div>
            @endforeach
        </div>
    </section>
</div>
