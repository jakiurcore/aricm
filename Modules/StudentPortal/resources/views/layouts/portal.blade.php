@extends('core::layouts.base')

@php
    $student = auth('student')->user();
    $identity = app(\Modules\Core\Services\PublicSite::class)->identity();
    $badges = collect(app(\Modules\StudentPortal\Services\PortalMenu::class)->items($student))->mapWithKeys(fn ($item): array => [$item->route => $item->badgeFor($student)]);
    $navigator = app(\Modules\StudentPortal\Services\PortalNavigation::class);
    $navigation = $navigator->sidebar($badges->all());
    $bottomNavigation = $navigator->bottom($badges->all());
    $shortName = setting('institute_short_'.app()->getLocale()) ?: $identity['name'];
@endphp

@push('head')
    <style>
        .pp-root { --pp-body: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', sans-serif; --pp-head: 'Noto Serif Bengali', 'Noto Sans Bengali', serif; min-height: 100vh; background: var(--bg); color: var(--ink); font-family: var(--pp-body); font-size: 16px; line-height: 1.7; }
        [data-lang='en'] .pp-root { --pp-body: 'Poppins', 'Noto Sans Bengali', sans-serif; --pp-head: 'Poppins', sans-serif; }
        .pp-root * { box-sizing: border-box; }
        .pp-root button, .pp-root a { cursor: pointer; }
        .pp-root button:disabled { cursor: not-allowed; }
        .pp-root input, .pp-root select { min-height: 44px; }
        .pp-root h3 { font-weight: 700; }
        .pp-head { font-family: var(--pp-head); }
        .pp-en { font-family: 'Poppins', sans-serif; }
        .pp-app { display: grid; grid-template-columns: minmax(0, 1fr); min-height: 100vh; }
        .pp-aside, .pp-narrow-only { display: none; }
        .pp-bottom { display: grid; }
        @media (min-width: 1024px) {
            .pp-app { grid-template-columns: 240px minmax(0, 1fr); }
            .pp-aside { display: flex; }
            .pp-narrow-only, .pp-bottom { display: none; }
        }
        .pp-card { border-radius: 20px; background: var(--surface); border: 1px solid var(--line); }
        .pp-card-sm { box-shadow: var(--shadow-sm); }
        .pp-pill { padding: 2px 10px; border-radius: 999px; font-size: 13px; font-weight: 700; white-space: nowrap; }
        .pp-ok { background: var(--success-bg); color: var(--success); }
        .pp-warn { background: var(--warn-bg); color: var(--warn); }
        .pp-bad { background: var(--danger-bg); color: var(--danger); }
        .pp-info { background: var(--info-bg); color: var(--info); }
        .pp-mute { background: var(--surface-2); color: var(--ds-muted); }
        .pp-btn { min-height: 48px; padding: 0 18px; border-radius: 999px; border: 1px solid var(--line-strong); background: var(--surface); color: var(--ink); font-weight: 600; font-size: 15px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
        .pp-btn-green { border: 0; background: var(--green-700); color: #fff; font-weight: 700; }
        .pp-btn-red { border: 0; background: var(--red-600); color: #fff; font-weight: 700; }
        .pp-section-head { margin: 0; padding: 14px 18px; font-size: 17px; background: var(--surface-2); }
        .pp-label { display: grid; gap: 4px; font-size: 14px; font-weight: 600; }
        .pp-input { height: 52px; padding: 0 14px; border-radius: 12px; border: 1px solid var(--line-strong); background: var(--surface); width: 100%; font-size: 17px; }
        @keyframes pp-shimmer { 0% { background-position: -400px 0; } 100% { background-position: 400px 0; } }
        @keyframes pp-fade { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
    </style>
@endpush

@section('body')
    <div class="pp-root" x-data="{ dark: document.documentElement.dataset.theme === 'dark', toggleTheme() { this.dark = ! this.dark; document.documentElement.dataset.theme = this.dark ? 'dark' : 'light'; try { localStorage.setItem('arm_theme', this.dark ? 'dark' : 'light'); } catch (e) {} } }">
        <div class="pp-app">
            <aside class="pp-aside" style="position: sticky; top: 0; height: 100vh; background: var(--green-900); color: #E6F2EA; flex-direction: column; padding: 16px 12px; gap: 4px; overflow-y: auto;" aria-label="{{ __('student-portal::messages.menu') }}">
                <a href="{{ route('portal.dashboard') }}" style="display: flex; gap: 10px; align-items: center; padding: 4px 8px 16px; text-decoration: none;">
                    <img src="{{ $identity['logo'] }}" alt="" style="width: 44px; height: 44px; border-radius: 50%; background: #fff; object-fit: cover;">
                    <span style="display: grid; line-height: 1.35;"><span class="pp-head" style="font-size: 15px; color: #fff;">{{ $shortName }}</span>@if ($identity['program'])<span style="font-size: 12px; color: #E6F2EA; opacity: .75;">{{ $identity['program'] }}</span>@endif</span>
                </a>
                @foreach ($navigation as $item)
                    <a href="{{ route($item['route']) }}" @if ($item['active']) aria-current="page" @endif style="display: flex; align-items: center; gap: 12px; min-height: 44px; padding: 0 12px; border-radius: 12px; text-decoration: none; font-weight: 600; background: {{ $item['active'] ? 'rgba(255,255,255,.14)' : 'transparent' }}; color: {{ $item['active'] ? '#FFD21F' : '#E6F2EA' }};">
                        <x-core::icon :name="$item['icon']" :size="22" style="width: 22px;" /><span style="flex: 1;">{{ __('student-portal::messages.nav.'.$item['key']) }}</span>
                        @if ($item['dot'])<span class="bg-red-600" style="width: 10px; height: 10px; border-radius: 50%;"></span>@endif
                    </a>
                @endforeach
                <form method="POST" action="{{ route('student.logout') }}" style="margin-top: auto;">@csrf
                    <button type="submit" style="width: 100%; display: flex; align-items: center; gap: 12px; min-height: 44px; padding: 0 12px; border: 1px solid rgba(255,255,255,.2); border-radius: 12px; background: transparent; color: #fff; font-weight: 600;">⎋ {{ __('student-portal::messages.logout') }}</button>
                </form>
            </aside>

            <div style="min-width: 0; display: flex; flex-direction: column;">
                <header style="position: sticky; top: 0; z-index: 20; background: var(--surface); border-bottom: 1px solid var(--line); padding: 10px 16px; display: flex; align-items: center; gap: 12px;">
                    <img class="pp-narrow-only" src="{{ $identity['logo'] }}" alt="" style="width: 40px; height: 40px; border-radius: 50%; background: #fff; object-fit: cover;">
                    <strong class="pp-head" style="flex: 1; min-width: 0; color: var(--heading); font-size: 20px;">@yield('title')</strong>
                    <div style="display: flex; padding: 3px; border-radius: 999px; background: var(--surface-2); border: 1px solid var(--line);">
                        <a href="{{ route('core.locale', 'bn') }}" style="min-width: 40px; height: 34px; border-radius: 999px; font-weight: 700; font-size: 13px; text-decoration: none; display: grid; place-items: center; background: {{ app()->getLocale() === 'bn' ? 'var(--green-700)' : 'transparent' }}; color: {{ app()->getLocale() === 'bn' ? '#fff' : 'var(--ds-muted)' }};">বাং</a>
                        <a href="{{ route('core.locale', 'en') }}" class="pp-en" style="min-width: 40px; height: 34px; border-radius: 999px; font-weight: 700; font-size: 13px; text-decoration: none; display: grid; place-items: center; background: {{ app()->getLocale() === 'en' ? 'var(--green-700)' : 'transparent' }}; color: {{ app()->getLocale() === 'en' ? '#fff' : 'var(--ds-muted)' }};">EN</a>
                    </div>
                    <button type="button" @click="toggleTheme()" aria-label="{{ __('student-portal::messages.theme') }}" class="pub-theme" :data-dark="dark" style="width: 44px; height: 44px; border-radius: 999px; border: 1px solid var(--line); background: var(--surface); color: var(--ink); padding: 0;"><x-core::icon name="moon" :size="22" class="pub-tg-moon" /><x-core::icon name="sun" :size="22" class="pub-tg-sun" /></button>
                </header>

                <main style="flex: 1; padding: 20px 16px 110px; max-width: 1100px; width: 100%; margin: 0 auto; display: grid; gap: 16px; align-content: start;">
                    @yield('content')
                </main>
            </div>
        </div>

        <nav class="pp-bottom" aria-label="{{ __('student-portal::messages.menu') }}" style="position: fixed; left: 0; right: 0; bottom: 0; z-index: 30; background: var(--surface); border-top: 1px solid var(--line); box-shadow: 0 -6px 20px rgba(0,0,0,.06); grid-template-columns: repeat({{ count($bottomNavigation) }}, minmax(0, 1fr)); padding: 6px 2px calc(6px + env(safe-area-inset-bottom));">
            @foreach ($bottomNavigation as $item)
                <a href="{{ route($item['route']) }}" @if ($item['active']) aria-current="page" @endif style="position: relative; min-height: 56px; display: grid; place-items: center; gap: 0; font-size: 11px; font-weight: 700; white-space: nowrap; text-decoration: none; line-height: 1.7; color: {{ $item['active'] ? 'var(--green-700)' : 'var(--ds-muted)' }};">
                    <span style="position: relative; width: 40px; height: 28px; border-radius: 999px; display: grid; place-items: center; font-size: 17px; background: {{ $item['active'] ? 'var(--green-100)' : 'transparent' }};"><x-core::icon :name="$item['icon']" :size="20" />@if ($item['dot'])<span class="bg-red-600" aria-label="{{ __('student-portal::messages.new') }}" style="position: absolute; top: 0; right: 6px; width: 10px; height: 10px; border-radius: 50%; border: 2px solid var(--surface);"></span>@endif</span>{{ __('student-portal::messages.nav.'.$item['key']) }}
                </a>
            @endforeach
        </nav>

        @if (session('status'))
            <div role="status" x-data="{ on: true }" x-init="setTimeout(() => on = false, 2600)" x-show="on" x-transition.opacity style="position: fixed; left: 50%; bottom: 88px; transform: translateX(-50%); z-index: 80; padding: 12px 20px; border-radius: 999px; background: var(--ink); color: var(--bg); font-weight: 600; box-shadow: var(--shadow-lg);">{{ session('status') }}</div>
        @endif
    </div>
@endsection
