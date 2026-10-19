@extends('core::layouts.base')

@php
    $user = auth()->user();
    $menu = app(\Modules\TeacherPanel\Services\TeacherMenu::class);
    $items = $menu->items($user);
    $parts = $menu->split($items);
    $identity = app(\Modules\Core\Services\PublicSite::class)->identity();
    $icons = ['home' => '🏠', 'book' => '📒', 'users' => '👥', 'edit' => '✏️'];
    $home = new \Modules\Core\Support\MenuItem(__('teacher-panel::messages.home'), 'teacher.home', 'home', 0);
@endphp

@section('body')
    <div x-data="{ more: false }" class="min-h-screen bg-bg pb-20 lg:pb-0">
        <header class="sticky top-0 z-30 border-b border-line bg-surface shadow-sm">
            <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-2.5">
                <a href="{{ route('teacher.home') }}" class="flex min-w-0 flex-1 items-center gap-3 no-underline">
                    <img src="{{ $identity['logo'] }}" alt="" class="size-10 rounded-full border border-line object-cover">
                    <span class="min-w-0 leading-tight"><span class="block truncate font-head text-base text-heading">{{ $identity['name'] }}</span><span class="text-xs text-ink-muted">{{ __('teacher-panel::messages.panel') }}</span></span>
                </a>
                <div class="flex rounded-full border border-line bg-surface-2 p-0.5 text-xs font-bold">
                    <a href="{{ route('core.locale', 'bn') }}" class="rounded-full px-2.5 py-1 no-underline {{ app()->getLocale() === 'bn' ? 'bg-brand text-white' : 'text-ink' }}">বাং</a>
                    <a href="{{ route('core.locale', 'en') }}" class="rounded-full px-2.5 py-1 no-underline {{ app()->getLocale() === 'en' ? 'bg-brand text-white' : 'text-ink' }}">EN</a>
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="min-h-10 rounded-md border border-line-strong px-3 text-sm font-semibold">{{ __('teacher-panel::messages.logout') }}</button></form>
            </div>
        </header>

        <div class="mx-auto flex max-w-6xl gap-6 px-4 py-6">
            <nav class="sticky top-20 hidden h-fit w-56 flex-none space-y-1 lg:block" aria-label="{{ __('teacher-panel::messages.menu') }}">
                @foreach ([$home, ...$items] as $item)
                    <a href="{{ route($item->route) }}" class="flex items-center justify-between rounded-lg px-3 py-2.5 no-underline {{ request()->routeIs($item->route.'*') ? 'bg-green-100 font-semibold text-green-700' : 'text-ink hover:bg-surface-2' }}">
                        <span>{{ $icons[$item->icon] ?? '•' }} {{ $item->label }}</span>
                    </a>
                @endforeach
            </nav>

            <main class="min-w-0 flex-1">
                @if (session('status'))<p class="mb-4 rounded-md bg-success-bg p-3 font-medium text-success">{{ session('status') }}</p>@endif
                @yield('content')
            </main>
        </div>

        {{-- Phone bottom bar: home, up to three flagged items, and "More". --}}
        <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface lg:hidden" aria-label="{{ __('teacher-panel::messages.menu') }}">
            <div class="mx-auto grid max-w-md grid-cols-5">
                <a href="{{ route('teacher.home') }}" class="flex flex-col items-center gap-0.5 py-2 text-xs no-underline {{ request()->routeIs('teacher.home') ? 'font-semibold text-green-700' : 'text-ink-muted' }}"><span class="text-xl">🏠</span>{{ __('teacher-panel::messages.home') }}</a>
                @foreach ($parts['bottom'] as $item)
                    <a href="{{ route($item->route) }}" class="flex flex-col items-center gap-0.5 py-2 text-xs no-underline {{ request()->routeIs($item->route.'*') ? 'font-semibold text-green-700' : 'text-ink-muted' }}"><span class="text-xl">{{ $icons[$item->icon] ?? '•' }}</span>{{ $item->label }}</a>
                @endforeach
                @for ($i = count($parts['bottom']); $i < 3; $i++)<span></span>@endfor
                <button type="button" class="flex flex-col items-center gap-0.5 py-2 text-xs text-ink-muted" @click="more = true"><span class="text-xl">☰</span>{{ __('teacher-panel::messages.more') }}</button>
            </div>
        </nav>

        <div x-show="more" x-cloak style="display: none;" x-transition.opacity class="fixed inset-0 z-50 bg-black/50 lg:hidden" @click.self="more = false">
            <div class="absolute inset-x-0 bottom-0 rounded-t-2xl bg-surface p-4 pb-8">
                <div class="mb-3 flex items-center justify-between"><p class="font-head text-lg">{{ __('teacher-panel::messages.more') }}</p><button type="button" class="size-10 rounded-full border border-line" @click="more = false" aria-label="{{ __('teacher-panel::messages.close') }}">✕</button></div>
                <div class="grid gap-1">
                    @foreach ($parts['more'] as $item)
                        <a href="{{ route($item->route) }}" class="rounded-lg px-3 py-3 text-lg no-underline hover:bg-surface-2">{{ $icons[$item->icon] ?? '•' }} {{ $item->label }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
