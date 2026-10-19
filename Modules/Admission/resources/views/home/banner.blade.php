@if ($settings->bannerEnabled())
    @php
        $start = $settings->get('admission_start');
        $classStart = $settings->get('class_start');
        $phone = ((array) setting('phones', []))[0] ?? null;
        $sessionYear = (int) \Illuminate\Support\Carbon::parse($start ?: now())->format('Y');
        $session = bn_digits($sessionYear).'–'.bn_digits(str_pad((string) (($sessionYear + 1) % 100), 2, '0', STR_PAD_LEFT));
    @endphp
    <div class="pub-wrap" style="margin-top: -24px;">
        <section style="position: relative; border-radius: 24px; overflow: hidden; background: var(--red-600); color: #fff; box-shadow: var(--shadow-lg); display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr));">
            <div style="padding: 24px; display: grid; gap: 8px; align-content: center;">
                <span style="justify-self: start; padding: 2px 12px; border-radius: 999px; background: var(--yellow-400); color: #1a1a1a; font-weight: 700; font-size: 13px;">{{ __('admission::messages.session', ['session' => $session]) }}</span>
                <h2 style="margin: 0; font-family: var(--f-head); font-size: clamp(32px, 5vw, 44px); line-height: 1.2;">{{ $settings->text('banner_title') }}</h2>
                @if ($settings->text('free_class_note'))
                    <p style="margin: 0; justify-self: start; padding: 4px 14px; border-radius: 12px; background: var(--yellow-400); color: #1a1a1a; font-family: var(--f-head); font-size: 20px; font-weight: 700; line-height: 1.5;">{{ $settings->text('free_class_note') }}</p>
                @endif
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1px; background: rgba(255,255,255,.25);">
                <div style="padding: 20px; background: var(--red-700); display: grid; align-content: center;">
                    <span style="font-size: 14px; opacity: .9;">{{ __('admission::messages.admission_start') }}</span>
                    <span style="font-family: var(--f-head); font-size: 24px; line-height: 1.35;">{{ $start ? bn_date($start) : '—' }}</span>
                </div>
                <div style="padding: 20px; background: var(--red-700); display: grid; align-content: center;">
                    <span style="font-size: 14px; opacity: .9;">{{ __('admission::messages.class_start') }}</span>
                    <span style="font-family: var(--f-head); font-size: 24px; line-height: 1.35;">{{ $classStart ? bn_date($classStart) : '—' }}</span>
                </div>
            </div>
            <div style="padding: 24px; display: grid; gap: 10px; align-content: center; background: #fff;">
                <a href="{{ route('admission.public') }}" style="height: 52px; border: 0; border-radius: 999px; background: var(--green-700); color: #fff; font-weight: 700; font-size: 17px; display: grid; place-items: center; text-decoration: none;">{{ __('admission::messages.apply_online') }}</a>
                @if ($phone)
                    <a href="tel:{{ $phone }}" style="text-align: center; font-weight: 600; color: #0B6B3A; text-decoration: underline; text-underline-offset: 3px;">☎ {{ bn_digits($phone) }}</a>
                @endif
            </div>
        </section>
    </div>
@endif
