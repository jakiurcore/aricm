@php
    $identity = app(\Modules\Core\Services\PublicSite::class)->identity();
    $phone = $identity['phones'][0] ?? null;
@endphp
<section class="pub-hero-pattern">
    <div style="max-width: 1240px; margin: 0 auto; padding: 36px 16px 56px; display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 440px), 1fr)); gap: 40px; align-items: center;">
        <div style="display: grid; gap: 18px; justify-items: start;">
            <span style="font-family: var(--f-ar); font-size: 26px; line-height: 1.4; color: var(--green-700);" dir="rtl" lang="ar">{{ __('website::messages.hero.arabic') }}</span>
            <h1 style="margin: 0; font-family: var(--f-head); font-weight: 600; color: var(--heading); font-size: clamp(34px, 6vw, 56px); line-height: 1.25; text-wrap: balance;">{{ $identity['tagline'] }}</h1>
            <p style="margin: 0; font-size: 20px; font-weight: 600; color: var(--green-700);">{{ __('website::messages.hero.tag1') }}</p>
            <p style="margin: 0; font-size: 17px; color: var(--muted); max-width: 40ch; text-wrap: pretty;">{{ __('website::messages.hero.body') }}</p>
            <a href="#abacus" style="display: flex; align-items: center; gap: 12px; padding: 10px 16px 10px 10px; border-radius: 18px; border: 2px solid var(--yellow-400); background: var(--surface); box-shadow: var(--shadow-md); text-align: left; max-width: 460px; color: var(--ink); text-decoration: none;">
                <span style="flex: none; width: 48px; height: 48px; border-radius: 14px; background: var(--yellow-400); display: grid; place-items: center; font-size: 24px;">🧮</span>
                <span style="display: grid; line-height: 1.45; min-width: 0;">
                    <span style="font-size: 12px; font-weight: 700; color: var(--red-600);">{{ __('website::messages.hero.abacus_tag') }}</span>
                    <strong style="font-family: var(--f-head); font-size: 18px; color: var(--heading);">{{ __('website::messages.program.title') }}</strong>
                    <span style="font-size: 14px; color: var(--muted);">{{ __('website::messages.hero.abacus_sub') }}</span>
                </span>
                <span style="flex: none; margin-left: auto; font-size: 20px; color: var(--green-700);">→</span>
            </a>
            <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                @if (Route::has('admission.public'))
                    <a href="{{ route('admission.public') }}" style="height: 52px; padding: 0 26px; border: 0; border-radius: 999px; background: var(--red-600); color: #fff; font-weight: 700; font-size: 17px; display: flex; align-items: center; text-decoration: none; box-shadow: 0 8px 20px rgba(214,32,42,.25);">{{ __('website::messages.hero.apply') }}</a>
                @endif
                @if ($phone)
                    <a href="tel:{{ $phone }}" style="height: 52px; padding: 0 22px; border-radius: 999px; border: 2px solid var(--navy-800); color: var(--navy-800); font-weight: 700; font-size: 17px; display: flex; align-items: center; text-decoration: none; background: var(--surface);">☎{{ __('website::messages.hero.call') }}</a>
                @endif
            </div>
            <span style="font-size: 16px; font-weight: 600; color: var(--ink);">{{ __('website::messages.hero.tag2') }}</span>
        </div>
        <div style="position: relative; padding: 18px 0 22px;">
            <div style="aspect-ratio: 5 / 4; border-radius: 28px; overflow: hidden; box-shadow: var(--shadow-lg); background: var(--surface); border: 6px solid var(--surface);">
                <img src="{{ asset('images/site/hero.jpg') }}" alt="{{ __('website::messages.hero.photo_alt') }}" style="width: 100%; height: 100%; object-fit: cover; display: block; border-radius: 22px;">
            </div>
            <span style="position: absolute; top: 0; right: 12px; padding: 6px 14px; border-radius: 999px; background: var(--yellow-400); color: #1a1a1a; font-weight: 700; font-size: 14px; box-shadow: var(--shadow-md); pointer-events: none;">★ {{ __('website::messages.hero.badge_est', ['year' => $identity['established']]) }}</span>
            <span style="position: absolute; bottom: 0; left: 8px; padding: 8px 14px; border-radius: 16px; background: var(--surface); color: var(--green-700); font-weight: 700; font-size: 14px; box-shadow: var(--shadow-md); border: 1px solid var(--line); pointer-events: none;">📖 {{ __('website::messages.hero.badge_hifz') }}</span>
            <span style="position: absolute; bottom: 44px; right: -4px; padding: 8px 14px; border-radius: 16px; background: var(--navy-800); color: #fff; font-weight: 700; font-size: 14px; box-shadow: var(--shadow-md); pointer-events: none;">{{ __('website::messages.hero.badge_cadet') }}</span>
        </div>
    </div>
</section>
