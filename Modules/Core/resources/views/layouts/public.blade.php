@extends('core::layouts.base')

@php
    $site = app(\Modules\Core\Services\PublicSite::class);
    $identity = $site->identity();
    $nav = $site->nav();
    $ticker = $site->ticker();
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
    <div x-data="{ drawer: false }" @keydown.escape.window="drawer = false">
        @if ($ticker)
            <div class="flex h-10 items-stretch overflow-hidden border-b border-line bg-surface">
                <div class="flex flex-none items-center bg-red-600 px-4 text-sm font-bold text-white">{{ __('core::nav.new') }}</div>
                <div class="min-w-0 flex-1 overflow-hidden">
                    <div class="flex h-full w-max items-center gap-10 whitespace-nowrap px-4 text-sm hover:[animation-play-state:paused]" style="animation: arm-tick 40s linear infinite;">
                        @foreach ([...$ticker, ...$ticker] as $item)
                            @if ($item->url)<a href="{{ $item->url }}" class="text-ink no-underline hover:underline">• {{ $item->text }}</a>@else<span>• {{ $item->text }}</span>@endif
                        @endforeach
                    </div>
                </div>
            </div>
            <style>@keyframes arm-tick { from { transform: translateX(0); } to { transform: translateX(-50%); } } @media (prefers-reduced-motion: reduce) { [style*="arm-tick"] { animation: none !important; } }</style>
        @endif

        <header class="sticky top-0 z-30 border-b border-line bg-surface shadow-sm">
            <div class="mx-auto flex max-w-page items-center gap-3 px-4 py-2.5">
                <a href="{{ $nav[0]['url'] ?? url('/') }}" class="flex min-w-0 flex-1 items-center gap-3 no-underline">
                    <img src="{{ $identity['logo'] }}" alt="" class="size-12 flex-none rounded-full border border-line object-cover">
                    <span class="grid min-w-0 leading-tight">
                        <span class="font-head text-base text-heading md:text-lg">{{ $identity['name'] }}</span>
                        <span class="text-xs text-ink-muted">{{ __('core::nav.established') }}: {{ $identity['established'] }} · {{ __('core::nav.place') }}</span>
                    </span>
                </a>
                <div class="flex flex-none items-center gap-2">
                    <div class="flex rounded-full border border-line bg-surface-2 p-0.5 text-xs font-bold">
                        <a href="{{ route('core.locale', 'bn') }}" class="rounded-full px-3 py-1.5 no-underline {{ app()->getLocale() === 'bn' ? 'bg-brand text-white' : 'text-ink' }}">বাং</a>
                        <a href="{{ route('core.locale', 'en') }}" class="rounded-full px-3 py-1.5 no-underline {{ app()->getLocale() === 'en' ? 'bg-brand text-white' : 'text-ink' }}">EN</a>
                    </div>
                    <div class="hidden gap-2 lg:flex">
                        @if (Route::has('admission.public'))<a href="{{ route('admission.public') }}" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white no-underline">{{ __('core::nav.admission_open') }}</a>@endif
                        <a href="{{ Route::has('student.login') ? route('student.login') : url('/') }}" class="rounded-md border border-line-strong px-4 py-2 text-sm font-semibold text-ink no-underline">{{ __('core::nav.login') }}</a>
                    </div>
                    <button type="button" class="grid size-11 place-items-center rounded-md border border-line-strong text-xl lg:hidden" @click="drawer = true" aria-label="{{ __('core::nav.menu') }}">☰</button>
                </div>
            </div>
            <nav class="hidden border-t border-line bg-green-700 lg:block">
                <div class="mx-auto flex max-w-page flex-wrap px-2">
                    @foreach ($nav as $item)
                        <a href="{{ $item['url'] }}" class="px-3.5 py-2.5 text-[15px] font-medium text-white no-underline hover:bg-white/15 {{ request()->routeIs($item['route']) ? 'bg-white/20' : '' }}">{{ $item['label'] }}</a>
                    @endforeach
                </div>
            </nav>
        </header>

        <div x-show="drawer" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex flex-col overflow-y-auto bg-green-700 text-white" style="display: none;">
            <div class="flex items-center justify-between border-b border-white/25 px-4 py-3">
                <span class="font-head text-lg">{{ __('core::nav.menu') }}</span>
                <button type="button" class="size-11 rounded-md border border-white text-xl" @click="drawer = false" aria-label="{{ __('core::nav.close') }}">✕</button>
            </div>
            <nav class="grid py-2">
                @foreach ($nav as $item)
                    <a href="{{ $item['url'] }}" class="border-b border-white/15 px-5 py-3 text-xl font-medium text-white no-underline">{{ $item['label'] }}</a>
                @endforeach
            </nav>
            <div class="grid gap-3 px-4 pb-8 pt-5">
                @if (Route::has('admission.public'))<a href="{{ route('admission.public') }}" class="rounded-md bg-red-600 px-4 py-3 text-center text-lg font-semibold text-white no-underline">{{ __('core::nav.admission_open') }}</a>@endif
                <a href="{{ Route::has('student.login') ? route('student.login') : url('/') }}" class="rounded-md border border-white px-4 py-3 text-center text-lg font-semibold text-white no-underline">{{ __('core::nav.login') }}</a>
                @foreach ($identity['phones'] as $phone)<a href="tel:{{ $phone }}" class="text-yellow-400">{{ bn_digits($phone) }}</a>@endforeach
            </div>
        </div>

        <main>@yield('content')</main>

        <footer class="mt-16 bg-green-900 text-white">
            <div class="mx-auto grid max-w-page gap-8 px-4 py-10 md:grid-cols-3">
                <div>
                    <p class="font-head text-lg">{{ $identity['name'] }}</p>
                    <p class="mt-2 text-sm text-white/80">{{ $identity['tagline'] }}</p>
                </div>
                <div class="text-sm">
                    <p class="mb-2 font-semibold">{{ __('core::nav.contact') }}</p>
                    <p class="text-white/80">{{ $identity['address'] }}</p>
                    @foreach ($identity['phones'] as $phone)<p><a href="tel:{{ $phone }}" class="text-yellow-400 no-underline">{{ bn_digits($phone) }}</a></p>@endforeach
                    @if ($identity['email'])<p><a href="mailto:{{ $identity['email'] }}" class="text-yellow-400 no-underline">{{ $identity['email'] }}</a></p>@endif
                </div>
                <div class="text-sm">
                    <p class="mb-2 font-semibold">{{ __('core::nav.links') }}</p>
                    <div class="grid gap-1">
                        @foreach (array_slice($nav, 0, 6) as $item)<a href="{{ $item['url'] }}" class="text-white/80 no-underline hover:text-white">{{ $item['label'] }}</a>@endforeach
                    </div>
                    @if ($identity['facebook'])<a href="{{ $identity['facebook'] }}" class="mt-2 block text-yellow-400 no-underline" rel="noopener">Facebook</a>@endif
                    @if ($identity['youtube'])<a href="{{ $identity['youtube'] }}" class="block text-yellow-400 no-underline" rel="noopener">YouTube</a>@endif
                </div>
            </div>
            <div class="border-t border-white/15 py-4 text-center text-xs text-white/70">© {{ bn_digits(now()->format('Y')) }} {{ $identity['name'] }}</div>
        </footer>
    </div>
@endsection
