@props(['title', 'tone' => 'green'])
@php
    $identity = app(\Modules\Core\Services\PublicSite::class)->identity();
    $locale = app()->getLocale();
    $home = \Illuminate\Support\Facades\Route::has('home') ? route('home') : url('/');
@endphp
<div class="pub-root pub-hero-pattern" style="min-height: 100vh; display: grid; place-items: center; padding: 24px 16px;">
    <div style="width: 100%; max-width: 420px; display: grid; gap: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <a href="{{ $home }}" style="font-weight: 600; text-decoration: none; color: var(--green-700);">← {{ __('core::nav.back_website') }}</a>
            <div role="group" aria-label="{{ __('core::nav.language') }}" style="display: flex; padding: 3px; border-radius: 999px; background: var(--surface); border: 1px solid var(--line);">
                <a href="{{ route('core.locale', 'bn') }}" style="min-width: 44px; height: 36px; display: grid; place-items: center; border-radius: 999px; text-decoration: none; font-weight: 700; font-size: 14px; background: {{ $locale === 'bn' ? 'var(--green-700)' : 'transparent' }}; color: {{ $locale === 'bn' ? '#fff' : 'var(--muted)' }};">বাং</a>
                <a href="{{ route('core.locale', 'en') }}" style="min-width: 44px; height: 36px; display: grid; place-items: center; border-radius: 999px; text-decoration: none; font-weight: 700; font-size: 14px; font-family: Poppins; background: {{ $locale === 'en' ? 'var(--green-700)' : 'transparent' }}; color: {{ $locale === 'en' ? '#fff' : 'var(--muted)' }};">EN</a>
            </div>
        </div>
        <div style="padding: 28px 24px; border-radius: 24px; background: var(--surface); border: 1px solid var(--line); box-shadow: var(--shadow-lg); display: grid; gap: 16px;">
            <div style="display: grid; justify-items: center; gap: 6px; text-align: center;">
                <img src="{{ $identity['logo'] }}" alt="" style="width: 84px; height: 84px; border-radius: 50%; background: #fff; object-fit: contain;">
                <strong style="font-family: var(--f-head); color: var(--heading); font-size: 18px; line-height: 1.4;">{{ $identity['name'] }}</strong>
                <span style="padding: 2px 12px; border-radius: 999px; background: {{ $tone === 'navy' ? 'var(--navy-50)' : 'var(--green-50)' }}; color: {{ $tone === 'navy' ? 'var(--navy-800)' : 'var(--green-700)' }}; font-weight: 700; font-size: 14px;">{{ $title }}</span>
            </div>
            {{ $slot }}
        </div>
    </div>
</div>
