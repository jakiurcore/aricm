@extends('core::layouts.base')

@php
    $student = auth('student')->user();
    $menu = app(\Modules\StudentPortal\Services\PortalMenu::class);
    $items = $menu->items($student);
    $parts = $menu->split($items);
    $identity = app(\Modules\Core\Services\PublicSite::class)->identity();
    $icons = ['home' => '🏠', 'wallet' => '💳', 'chart' => '📊', 'bell' => '🔔', 'calendar' => '📅', 'card' => '🪪', 'book' => '📘', 'user' => '👤', 'diary' => '📒'];
    $unread = $student->unreadNotifications()->count();
@endphp

@section('body')
    <div x-data="{ more: false }" class="min-h-screen bg-bg pb-20 lg:pb-0">
        <header class="sticky top-0 z-30 border-b border-line bg-surface shadow-sm">
            <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-2.5">
                <a href="{{ route('portal.dashboard') }}" class="flex min-w-0 flex-1 items-center gap-3 no-underline">
                    <img src="{{ $identity['logo'] }}" alt="" class="size-10 rounded-full border border-line object-cover">
                    <span class="min-w-0 leading-tight"><span class="block truncate font-head text-base text-heading">{{ $identity['name'] }}</span><span class="text-xs text-ink-muted">{{ __('student-portal::messages.portal') }}</span></span>
                </a>
                <div class="flex rounded-full border border-line bg-surface-2 p-0.5 text-xs font-bold">
                    <a href="{{ route('core.locale', 'bn') }}" class="rounded-full px-2.5 py-1 no-underline {{ app()->getLocale() === 'bn' ? 'bg-brand text-white' : 'text-ink' }}">বাং</a>
                    <a href="{{ route('core.locale', 'en') }}" class="rounded-full px-2.5 py-1 no-underline {{ app()->getLocale() === 'en' ? 'bg-brand text-white' : 'text-ink' }}">EN</a>
                </div>
                <form method="POST" action="{{ route('student.logout') }}">@csrf<button class="min-h-10 rounded-md border border-line-strong px-3 text-sm font-semibold">{{ __('student-portal::messages.logout') }}</button></form>
            </div>
        </header>

        <div class="mx-auto flex max-w-6xl gap-6 px-4 py-6">
            <nav class="sticky top-20 hidden h-fit w-56 flex-none space-y-1 lg:block" aria-label="{{ __('student-portal::messages.menu') }}">
                @foreach ([['portal.dashboard', 'home', __('student-portal::messages.home'), null], ...array_map(fn ($item) => [$item->route, $item->icon, $item->label, $item->badgeFor($student)], $items), ['portal.profile', 'user', __('student-portal::messages.profile'), null]] as [$route, $icon, $label, $badge])
                    <a href="{{ route($route) }}" class="flex items-center justify-between rounded-lg px-3 py-2.5 no-underline {{ request()->routeIs($route.'*') && ($route !== 'portal.dashboard' || request()->routeIs('portal.dashboard')) ? 'bg-green-100 font-semibold text-green-700' : 'text-ink hover:bg-surface-2' }}">
                        <span>{{ $icons[$icon] ?? '•' }} {{ $label }}</span>
                        @if ($badge)<span class="rounded-full bg-red-600 px-2 py-0.5 text-xs font-bold text-white">{{ bn_digits($badge) }}</span>@endif
                    </a>
                @endforeach
            </nav>

            <main class="min-w-0 flex-1">
                @if (session('status'))<p class="mb-4 rounded-md bg-success-bg p-3 font-medium text-success">{{ session('status') }}</p>@endif
                @yield('content')
            </main>
        </div>

        {{-- Phone bottom bar: home, the first three flagged items, and "More" for the rest. --}}
        <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface lg:hidden" aria-label="{{ __('student-portal::messages.menu') }}">
            <div class="mx-auto grid max-w-md grid-cols-5">
                <a href="{{ route('portal.dashboard') }}" class="flex flex-col items-center gap-0.5 py-2 text-xs no-underline {{ request()->routeIs('portal.dashboard') ? 'font-semibold text-green-700' : 'text-ink-muted' }}"><span class="text-xl">🏠</span>{{ __('student-portal::messages.home') }}</a>
                @foreach ($parts['bottom'] as $item)
                    <a href="{{ route($item->route) }}" class="relative flex flex-col items-center gap-0.5 py-2 text-xs no-underline {{ request()->routeIs($item->route.'*') ? 'font-semibold text-green-700' : 'text-ink-muted' }}">
                        <span class="text-xl">{{ $icons[$item->icon] ?? '•' }}</span>{{ $item->label }}
                        @if ($item->badgeFor($student))<span class="absolute right-3 top-1 rounded-full bg-red-600 px-1.5 text-[10px] font-bold text-white">{{ bn_digits($item->badgeFor($student)) }}</span>@endif
                    </a>
                @endforeach
                @for ($i = count($parts['bottom']); $i < 3; $i++)<span></span>@endfor
                <button type="button" class="flex flex-col items-center gap-0.5 py-2 text-xs text-ink-muted" @click="more = true"><span class="text-xl">☰</span>{{ __('student-portal::messages.more') }}</button>
            </div>
        </nav>

        <div x-show="more" x-cloak style="display: none;" x-transition.opacity class="fixed inset-0 z-50 bg-black/50 lg:hidden" @click.self="more = false">
            <div class="absolute inset-x-0 bottom-0 rounded-t-2xl bg-surface p-4 pb-8">
                <div class="mb-3 flex items-center justify-between"><p class="font-head text-lg">{{ __('student-portal::messages.more') }}</p><button type="button" class="size-10 rounded-full border border-line" @click="more = false" aria-label="{{ __('core::nav.close') }}">✕</button></div>
                <div class="grid gap-1">
                    @foreach ([...$parts['more'], new \Modules\Core\Support\MenuItem(__('student-portal::messages.profile'), 'portal.profile', 'user', 99)] as $item)
                        <a href="{{ route($item->route) }}" class="flex items-center justify-between rounded-lg px-3 py-3 text-lg no-underline hover:bg-surface-2"><span>{{ $icons[$item->icon] ?? '•' }} {{ $item->label }}</span>@if ($item->badgeFor($student))<span class="rounded-full bg-red-600 px-2 py-0.5 text-xs font-bold text-white">{{ bn_digits($item->badgeFor($student)) }}</span>@endif</a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
