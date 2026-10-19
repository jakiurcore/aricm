@php
    $icons = ['Aa', 'ab', 'abc', '💬'];
    $backgrounds = ['var(--navy-50)', 'var(--green-50)', 'var(--yellow-50)', 'var(--red-50)'];
@endphp
<div class="pub-wrap" style="margin-top: 64px;">
    <section style="display: grid; gap: 24px; padding: clamp(20px, 3vw, 32px); border-radius: 28px; background: var(--navy-50);">
        <div style="display: grid; gap: 4px; max-width: 760px;">
            <span style="color: var(--navy-800); font-weight: 700; font-size: 14px;">{{ __('website::messages.program.kicker') }}</span>
            <h2 class="pub-h2" style="text-wrap: balance;">{{ __('website::messages.phonetics.title') }}</h2>
            <p style="margin: 0; color: var(--muted); font-size: 17px; text-wrap: pretty;">{{ __('website::messages.phonetics.sub') }}</p>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 240px), 1fr)); gap: 16px;">
            @foreach (__('website::messages.phonetics.benefits') as $index => $benefit)
                <div class="pub-card" style="display: grid; gap: 14px; align-content: start;">
                    <span style="width: 52px; height: 52px; border-radius: 16px; display: grid; place-items: center; font-size: 22px; font-family: Poppins, sans-serif; font-weight: 700; color: var(--navy-800); background: {{ $backgrounds[$index] ?? $backgrounds[0] }};">{{ $icons[$index] ?? '' }}</span>
                    <span style="font-size: 18px; font-weight: 700; line-height: 1.5; color: var(--ink);">{{ $benefit }}</span>
                </div>
            @endforeach
        </div>
    </section>
</div>
