@extends('core::layouts.base')

@php
    $user = auth()->user();
    $menu = app(\Modules\TeacherPanel\Services\TeacherMenu::class);
    $identity = app(\Modules\Core\Services\PublicSite::class)->identity();
    $scope = app(\Modules\Teachers\Services\TeacherScope::class);
    $isClassTeacher = collect($scope->sections($user))->contains('is_class_teacher', true);
    $t = fn (string $key) => __('teacher-panel::messages.nav.'.$key);

    // Fixed navigation, in the order of the design: key => [icon, label, route, active patterns].
    $nav = [
        'home' => ['home', $t('home'), 'teacher.home', ['teacher.home', 'teacher.class-day*']],
        'write' => ['pencil', $t('write'), 'teacher.diary.write', ['teacher.diary.write']],
        'remarks' => ['bell', $t('remarks'), 'teacher.remarks', ['teacher.remarks']],
        'history' => ['book', $t('history'), 'teacher.diary', ['teacher.diary']],
        'ct' => ['star', $t('class_day'), 'teacher.class-day', ['teacher.class-day*']],
        'profile' => ['users', $t('profile'), 'teacher.profile', ['teacher.profile', 'teacher.password*']],
    ];
    $sidebar = collect($nav)->reject(fn ($item, $key) => $key === 'ct' && ! $isClassTeacher)->all();
    $extras = $menu->extras($menu->items($user));
    $bottom = collect($nav)->only(['home', 'write', 'remarks', 'history', 'profile'])->all();
    $isActive = fn (string $key) => $key === 'home' ? request()->routeIs('teacher.home', 'teacher.class-day*') : (in_array($key, ['ct']) ? request()->routeIs('teacher.class-day*') : request()->routeIs(...$nav[$key][3]));
    $backView = request()->routeIs('teacher.diary.write', 'teacher.class-day*', 'teacher.password*', 'teacher.marks.entry');
    $backUrl = request()->routeIs('teacher.marks.entry') ? route('teacher.marks') : (request()->routeIs('teacher.password*') ? route('teacher.profile') : route('teacher.home'));
@endphp

@push('head')
    <script>try { if (localStorage.getItem('arm_theme') === 'dark') { document.documentElement.dataset.theme = 'dark'; } } catch (e) {}</script>
    <style>
        .tp { --muted: var(--ds-muted); }
        html[data-lang="en"] .tp { font-family: 'Poppins', 'Noto Sans Bengali', sans-serif; }
        html[data-lang="en"] .tp .font-head { font-family: 'Poppins', sans-serif; }
        .tp input, .tp select, .tp textarea { min-height: 44px; color: inherit; font: inherit; }
        .tp :focus-visible { outline: 3px solid var(--yellow-400); outline-offset: 2px; }
        @keyframes tp-shimmer { 0% { background-position: -400px 0; } 100% { background-position: 400px 0; } }
        .tp-skeleton { background: linear-gradient(90deg, var(--surface-2) 0, var(--line) 50%, var(--surface-2) 100%); background-size: 800px 100%; animation: tp-shimmer 1.2s linear infinite; }
        .tp-scroll::-webkit-scrollbar { display: none; }
    </style>
@endpush

@section('body')
    <div class="tp flex min-h-screen bg-bg font-body text-[16px] leading-[1.7] text-ink" x-data="tpShell()" @tp-toast.window="flash($event.detail)">
        <aside class="sticky top-0 hidden h-screen w-[248px] flex-none flex-col gap-1 overflow-y-auto bg-green-900 px-3 py-4 text-[#E6F2EA] lg:flex" aria-label="{{ __('teacher-panel::messages.menu') }}">
            <a href="{{ route('teacher.home') }}" class="flex items-center gap-2.5 px-2 py-1 no-underline">
                <img src="{{ $identity['logo'] }}" alt="" class="size-11 rounded-full bg-white object-cover">
                <span class="grid leading-[1.35]"><span class="font-head text-[15px] text-white">{{ $identity["name"] }}</span>@if ($identity['program'])<span class="text-[12px] text-[#E6F2EA] opacity-75">{{ $identity['program'] }}</span>@endif</span>
            </a>
            <span class="mx-2 mb-3.5 self-start justify-self-start rounded-full bg-[rgba(255,210,31,.16)] px-2.5 py-0.5 text-[13px] font-bold text-[#FFD21F]">{{ __('teacher-panel::messages.panel') }}</span>
            @foreach ($sidebar as $key => [$icon, $label, $route])
                <a href="{{ route($route) }}" @if ($isActive($key)) aria-current="page" @endif class="flex min-h-11 items-center gap-3 rounded-xl px-3 font-semibold no-underline {{ $isActive($key) ? 'bg-[rgba(255,255,255,.14)] text-[#FFD21F]' : 'text-[#E6F2EA]' }}"><x-core::icon :name="$icon" :size="22" class="w-[22px]" />{{ $label }}</a>
            @endforeach
            @foreach ($extras as $item)
                <a href="{{ route($item->route) }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 font-semibold no-underline {{ request()->routeIs($item->route.'*') ? 'bg-[rgba(255,255,255,.14)] text-[#FFD21F]' : 'text-[#E6F2EA]' }}"><x-core::icon name="pencil" :size="22" class="w-[22px]" />{{ $item->label }}</a>
            @endforeach
            <form method="POST" action="{{ route('logout') }}" class="mt-auto">@csrf
                <button class="flex min-h-11 w-full cursor-pointer items-center gap-3 rounded-xl border border-[rgba(255,255,255,.2)] bg-transparent px-3 text-left font-semibold text-white"><span class="w-[22px] text-center">⎋</span>{{ __('teacher-panel::messages.logout') }}</button>
            </form>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 flex flex-none items-center gap-3 border-b border-line bg-surface px-4 py-2.5">
                @if ($backView)
                    <a href="{{ $backUrl }}" aria-label="{{ __('teacher-panel::messages.back') }}" class="grid size-11 flex-none place-items-center rounded-full border border-line bg-surface text-[18px] text-ink no-underline lg:hidden">←</a>
                @else
                    <img src="{{ $identity['logo'] }}" alt="" class="size-10 flex-none rounded-full bg-white object-cover lg:hidden">
                @endif
                <strong class="min-w-0 flex-1 font-head text-[20px] font-bold leading-[1.35] text-heading">@yield('screen_title')</strong>
                <button type="button" @click="toggleTheme()" aria-label="{{ __('teacher-panel::messages.theme') }}" class="pub-theme size-11 flex-none cursor-pointer rounded-full border border-line bg-surface text-ink" :data-dark="theme === 'dark'"><x-core::icon name="moon" :size="22" class="pub-tg-moon" /><x-core::icon name="sun" :size="22" class="pub-tg-sun" /></button>
            </header>

            <main class="flex-1 px-4 pb-[120px] pt-5">
                <div class="mx-auto grid max-w-[1120px] content-start gap-4">
                    @yield('content')
                </div>
            </main>
        </div>

        @hasSection('fab')
            <a href="{{ route('teacher.diary.write') }}" class="fixed bottom-[88px] right-4 z-[25] flex h-14 items-center whitespace-nowrap rounded-full bg-red-600 px-[22px] text-[17px] font-bold text-white no-underline shadow-[0_10px_24px_rgba(214,32,42,.35)] lg:bottom-6">+ {{ __('teacher-panel::messages.write_diary') }}</a>
        @endif

        <div class="fixed inset-x-0 bottom-0 z-40 lg:left-[248px]">
            @yield('action_bar')
            <nav class="grid grid-cols-5 border-t border-line bg-surface px-1 pt-1.5 pb-[calc(6px+env(safe-area-inset-bottom))] lg:hidden" aria-label="{{ __('teacher-panel::messages.menu') }}">
                @foreach ($bottom as $key => [$icon, $label, $route])
                    <a href="{{ route($route) }}" @if ($isActive($key)) aria-current="page" @endif class="grid min-h-14 place-items-center text-[12px] font-bold no-underline {{ $isActive($key) ? 'text-green-700' : 'text-ink-muted' }}"><span class="grid h-7 w-11 place-items-center rounded-full text-[17px] {{ $isActive($key) ? 'bg-green-100' : '' }}"><x-core::icon :name="$icon" :size="20" /></span>{{ $label }}</a>
                @endforeach
            </nav>
        </div>

        <div x-show="toast" x-cloak x-transition.opacity role="status" class="fixed bottom-[140px] left-1/2 z-[80] max-w-[calc(100%-32px)] -translate-x-1/2 rounded-full bg-ink px-5 py-3 text-center font-semibold text-bg shadow-[var(--shadow-lg)]" x-text="toast"></div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('tpShell', () => ({
                theme: document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light', toast: '', timer: null,
                init() {
                    let flash = @js(session('status'));
                    try { const kept = sessionStorage.getItem('tp_toast'); if (kept) { flash = kept; sessionStorage.removeItem('tp_toast'); } } catch (e) {}
                    if (flash) { this.flash(flash); }
                },
                flash(message) { this.toast = message; clearTimeout(this.timer); this.timer = setTimeout(() => { this.toast = ''; }, 2600); },
                toggleTheme() {
                    this.theme = this.theme === 'dark' ? 'light' : 'dark';
                    document.documentElement.dataset.theme = this.theme;
                    try { localStorage.setItem('arm_theme', this.theme); } catch (e) {}
                },
            }));
        });
    </script>
@endsection
