@extends('core::layouts.base')

@php
    $site = app(\Modules\Core\Services\PublicSite::class);
    $identity = $site->identity();
    $nav = $site->nav();
    $ticker = $site->ticker();
    $locale = app()->getLocale();
    $year = bn_digits(now()->format('Y'));
    $isActive = function (string $route): bool {
        $pattern = match (true) {
            str_ends_with($route, '.index') => substr($route, 0, -5).'*',
            $route === 'admission.public' => 'admission.*',
            $route === 'results.public' => 'results.*',
            default => $route,
        };

        return request()->routeIs($pattern);
    };
    $teacherUrl = Route::has('teacher.home') ? route('teacher.home') : (Route::has('login') ? route('login') : url('/'));
    $loginUrl = Route::has('student.login') ? route('student.login') : url('/');
    $heroPhone = $identity['phones'][0] ?? null;
@endphp

@push('meta')
    <meta name="description" content="@yield('description', $identity['tagline'])">
    <meta property="og:title" content="@yield('title', $identity['name'])">
    <meta property="og:description" content="@yield('description', $identity['tagline'])">
    <meta property="og:image" content="@yield('og_image', $identity['logo'])">
    <meta property="og:type" content="website">
    <link rel="icon" href="{{ $identity['logo'] }}">
@endpush

@section('body')
    <div class="pub-root" x-data="{ drawer: false, dark: document.documentElement.getAttribute('data-theme') === 'dark' }" @keydown.escape.window="drawer = false" x-effect="document.body.style.overflow = drawer ? 'hidden' : ''">
        @if ($ticker)
            <div style="display: flex; align-items: center; height: 40px; background: var(--green-900); color: #fff; overflow: hidden;">
                <span style="flex: none; height: 100%; display: flex; align-items: center; padding: 0 14px; background: var(--red-600); color: #fff; font-weight: 700; font-size: 14px;">{{ __('core::nav.new') }}</span>
                <div style="flex: 1; min-width: 0; overflow: hidden; font-size: 15px;">
                    <div class="pub-tick">
                        @foreach ([...$ticker, ...$ticker] as $item)
                            @if ($item->url)
                                <a href="{{ $item->url }}" style="padding: 0 28px; white-space: nowrap; color: #fff; text-decoration: none;">◆&nbsp; {{ $item->text }}</a>
                            @else
                                <span style="padding: 0 28px; white-space: nowrap;">◆&nbsp; {{ $item->text }}</span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <header style="position: sticky; top: 0; z-index: 40; background: var(--surface); border-bottom: 1px solid var(--line); box-shadow: var(--shadow-sm);">
            <div style="max-width: 1240px; margin: 0 auto; padding: 10px 16px; display: flex; align-items: center; gap: 12px;">
                <a href="{{ $nav[0]['url'] ?? url('/') }}" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--ink); min-width: 0; flex: 1;">
                    <img src="{{ $identity['logo'] }}" alt="" style="flex: none; width: 52px; height: 52px; border-radius: 50%; background: #fff; object-fit: contain; border: 1px solid var(--line);">
                    <span style="min-width: 0; display: grid; line-height: 1.35;">
                        <span style="font-family: var(--f-head); font-size: 17px; color: var(--heading); font-weight: 600; text-wrap: balance;">{{ $identity['name'] }}</span>
                        <span style="font-size: 13px; color: var(--muted);">{{ __('core::nav.established_line', ['year' => $identity['established']]) }}</span>
                    </span>
                </a>
                <div style="display: flex; align-items: center; gap: 8px; flex: none;">
                    <div role="group" aria-label="{{ __('core::nav.language') }}" style="display: flex; padding: 3px; border-radius: 999px; background: var(--surface-2); border: 1px solid var(--line);">
                        <a href="{{ route('core.locale', 'bn') }}" @if ($locale === 'bn') aria-current="true" @endif style="min-width: 44px; height: 36px; display: grid; place-items: center; border-radius: 999px; text-decoration: none; font-weight: 700; font-size: 14px; background: {{ $locale === 'bn' ? 'var(--green-700)' : 'transparent' }}; color: {{ $locale === 'bn' ? '#fff' : 'var(--muted)' }};">বাং</a>
                        <a href="{{ route('core.locale', 'en') }}" @if ($locale === 'en') aria-current="true" @endif style="min-width: 44px; height: 36px; display: grid; place-items: center; border-radius: 999px; text-decoration: none; font-weight: 700; font-size: 14px; font-family: Poppins, sans-serif; background: {{ $locale === 'en' ? 'var(--green-700)' : 'transparent' }}; color: {{ $locale === 'en' ? '#fff' : 'var(--muted)' }};">EN</a>
                    </div>
                    <button type="button" aria-label="{{ __('core::nav.theme') }}" @click="dark = !dark; document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light'); try { localStorage.setItem('arm_theme', dark ? 'dark' : 'light') } catch (e) {}" style="width: 44px; height: 44px; border-radius: 999px; border: 1px solid var(--line); background: var(--surface); cursor: pointer; font-size: 18px;" x-text="dark ? '☀' : '☾'">☾</button>
                    <div class="hidden lg:flex" style="gap: 8px;">
                        @if (Route::has('admission.public'))
                            <a href="{{ route('admission.public') }}" style="height: 44px; padding: 0 18px; border: 0; border-radius: 999px; background: var(--red-600); color: #fff; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(214,32,42,.25); text-decoration: none;"><span style="width: 8px; height: 8px; border-radius: 50%; background: var(--yellow-400);"></span>{{ __('core::nav.admission_open') }}</a>
                        @endif
                        <a href="{{ $loginUrl }}" style="height: 44px; padding: 0 18px; border-radius: 999px; border: 2px solid var(--navy-800); color: var(--navy-800); font-weight: 700; display: flex; align-items: center; text-decoration: none;">{{ __('core::nav.login') }}</a>
                    </div>
                    <button type="button" class="lg:hidden" @click="drawer = true" aria-label="{{ __('core::nav.menu') }}" style="width: 44px; height: 44px; border-radius: 12px; border: 1px solid var(--line); background: var(--green-700); color: #fff; font-size: 20px; cursor: pointer;">☰</button>
                </div>
            </div>
            <nav class="hidden lg:block" style="border-top: 1px solid var(--line);">
                <div style="max-width: 1240px; margin: 0 auto; padding: 0 8px; display: flex; flex-wrap: wrap; gap: 2px;">
                    @foreach ($nav as $item)
                        @php($active = $isActive($item['route']))
                        <a href="{{ $item['url'] }}" @if ($active) aria-current="page" @endif style="padding: 10px 14px; text-decoration: none; font-weight: 600; font-size: 15px; color: {{ $active ? 'var(--green-700)' : 'var(--ink)' }}; box-shadow: inset 0 -3px 0 {{ $active ? 'var(--green-700)' : 'transparent' }};">{{ $item['label'] }}</a>
                    @endforeach
                </div>
            </nav>
        </header>

        <div x-show="drawer" x-cloak x-transition.opacity.duration.200ms class="flex flex-col lg:hidden" style="display: none; position: fixed; inset: 0; z-index: 60; background: var(--green-700); color: #fff; overflow-y: auto;" role="dialog" aria-modal="true">
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,.2);">
                <img src="{{ $identity['logo'] }}" alt="" style="width: 44px; height: 44px; border-radius: 50%; background: #fff; object-fit: contain;">
                <span style="flex: 1; font-family: var(--f-head); font-size: 17px;">{{ __('core::nav.short_name') }}</span>
                <button type="button" @click="drawer = false" aria-label="{{ __('core::nav.close') }}" style="width: 44px; height: 44px; border-radius: 12px; border: 1px solid rgba(255,255,255,.4); background: transparent; color: #fff; font-size: 20px; cursor: pointer;">✕</button>
            </div>
            <nav style="display: grid; padding: 8px;">
                @foreach ($nav as $item)
                    <a href="{{ $item['url'] }}" style="color: #fff; text-decoration: none; padding: 12px 16px; border-radius: 12px; font-size: 20px; font-weight: 600; background: {{ $isActive($item['route']) ? 'rgba(255,255,255,.14)' : 'transparent' }};">{{ $item['label'] }}</a>
                @endforeach
            </nav>
            <div style="display: grid; gap: 10px; padding: 16px 16px 32px; margin-top: auto;">
                @if (Route::has('admission.public'))
                    <a href="{{ route('admission.public') }}" style="height: 52px; border: 0; border-radius: 999px; background: var(--red-600); color: #fff; font-weight: 700; font-size: 17px; display: grid; place-items: center; text-decoration: none;">{{ __('core::nav.admission_open') }}</a>
                @endif
                <a href="{{ $loginUrl }}" style="height: 52px; border-radius: 999px; background: #fff; color: var(--green-700); font-weight: 700; font-size: 17px; display: grid; place-items: center; text-decoration: none;">{{ __('core::nav.login') }}</a>
                <div style="display: flex; flex-wrap: wrap; gap: 16px; justify-content: center; padding-top: 8px;">
                    @foreach ($identity['phones'] as $phone)
                        <a href="tel:{{ $phone }}" style="color: var(--yellow-400); font-weight: 600;">☎ {{ bn_digits($phone) }}</a>
                    @endforeach
                </div>
            </div>
        </div>

        <main>@yield('content')</main>

        <footer style="margin-top: 64px; background: var(--green-900); color: #E6F2EA;">
            <div style="max-width: 1240px; margin: 0 auto; padding: 40px 16px 24px; display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr)); gap: 28px;">
                <div style="display: grid; gap: 10px; align-content: start;">
                    <img src="{{ $identity['logo'] }}" alt="" style="width: 72px; height: 72px; border-radius: 50%; background: #fff; object-fit: contain;">
                    <strong style="font-family: var(--f-head); font-size: 18px; color: #fff;">{{ $identity['name'] }}</strong>
                    <span style="font-size: 15px;">{{ $identity['address'] }}</span>
                </div>
                <div style="display: grid; gap: 6px; align-content: start;">
                    <strong style="color: var(--yellow-400);">{{ __('core::nav.links') }}</strong>
                    @foreach (array_slice($nav, 1, 6) as $item)
                        <a href="{{ $item['url'] }}" class="pub-footlink">{{ $item['label'] }}</a>
                    @endforeach
                </div>
                <div style="display: grid; gap: 6px; align-content: start;">
                    <strong style="color: var(--yellow-400);">{{ __('core::nav.portals') }}</strong>
                    <a href="{{ $loginUrl }}" class="pub-footlink">{{ __('core::nav.student_portal') }}</a>
                    <a href="{{ $teacherUrl }}" class="pub-footlink">{{ __('core::nav.teacher_panel') }}</a>
                    <a href="{{ url('/admin') }}" class="pub-footlink">{{ __('core::nav.admin_panel') }}</a>
                </div>
                <div style="display: grid; gap: 8px; align-content: start;">
                    <strong style="color: var(--yellow-400);">{{ __('core::nav.phone') }}</strong>
                    @foreach ($identity['phones'] as $phone)
                        <a href="tel:{{ $phone }}" style="color: #fff; font-weight: 600; text-decoration: underline; text-underline-offset: 3px;">☎ {{ bn_digits($phone) }}</a>
                    @endforeach
                    <div style="display: flex; gap: 8px; margin-top: 6px;">
                        <a href="{{ $identity['facebook'] ?: '#' }}" aria-label="Facebook" rel="noopener" style="width: 44px; height: 44px; border-radius: 50%; background: rgba(255,255,255,.1); color: #fff; display: grid; place-items: center; text-decoration: none; font-weight: 700; font-family: Poppins;">f</a>
                        <a href="{{ $identity['youtube'] ?: '#' }}" aria-label="YouTube" rel="noopener" style="width: 44px; height: 44px; border-radius: 50%; background: rgba(255,255,255,.1); color: #fff; display: grid; place-items: center; text-decoration: none;">▶</a>
                        <a href="{{ $heroPhone ? 'https://wa.me/88'.$heroPhone : '#' }}" aria-label="WhatsApp" rel="noopener" style="width: 44px; height: 44px; border-radius: 50%; background: rgba(255,255,255,.1); color: #fff; display: grid; place-items: center; text-decoration: none;">✆</a>
                    </div>
                </div>
            </div>
            <div style="border-top: 1px solid rgba(255,255,255,.12); padding: 16px; text-align: center; font-size: 14px;">{{ __('core::nav.copyright', ['year' => $year, 'name' => $identity['name']]) }}</div>
        </footer>
    </div>
@endsection
