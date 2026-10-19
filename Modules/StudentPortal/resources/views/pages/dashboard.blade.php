@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.home'))

@section('content')
    <div class="space-y-6">
        <section class="flex items-center gap-4 rounded-2xl bg-gradient-to-br from-green-700 to-green-900 p-5 text-white shadow-md">
            <div class="grid size-16 flex-none place-items-center overflow-hidden rounded-full border-2 border-yellow-400 bg-white/15 text-2xl">
                @if ($student->photo_url)<img src="{{ $student->photo_url }}" alt="" class="size-full object-cover">@else{{ mb_substr($student->name_bn, 0, 1) }}@endif
            </div>
            <div class="min-w-0">
                <p class="text-sm text-white/80">{{ __('student-portal::messages.welcome') }}</p>
                <p class="truncate font-head text-2xl">{{ $student->displayName() }}</p>
                <p class="text-sm text-white/85">{{ $student->student_id }} · {{ $student->schoolClass?->displayName() }}{{ $student->section ? ' / '.$student->section->displayName() : '' }} · {{ __('student-portal::messages.roll') }} {{ $student->roll ? bn_digits($student->roll) : '—' }}</p>
            </div>
        </section>

        @if ($summary)
            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($summary as $item)
                    @php($tone = ['success' => 'text-success', 'warn' => 'text-warn', 'danger' => 'text-danger'][$item->tone] ?? 'text-heading')
                    @php($link = $item->route && \Illuminate\Support\Facades\Route::has($item->route) ? route($item->route) : null)
                    @if ($link)
                        <a href="{{ $link }}" class="block rounded-xl border border-line bg-surface p-4 no-underline shadow-sm">
                            <p class="text-sm text-ink-muted">{{ $item->label }}</p>
                            <p class="mt-1 font-head text-2xl {{ $tone }}">{{ $item->value }}</p>
                        </a>
                    @else
                        <div class="rounded-xl border border-line bg-surface p-4 shadow-sm">
                            <p class="text-sm text-ink-muted">{{ $item->label }}</p>
                            <p class="mt-1 font-head text-2xl {{ $tone }}">{{ $item->value }}</p>
                        </div>
                    @endif
                @endforeach
            </section>
        @endif

        @if ($notifications->isNotEmpty())
            <section class="rounded-xl border border-line bg-surface p-4 shadow-sm">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="font-head text-lg text-heading">{{ __('student-portal::messages.notifications') }}</h2>
                    <form method="POST" action="{{ route('portal.notifications.read') }}">@csrf<button class="text-sm underline">{{ __('student-portal::messages.mark_read') }}</button></form>
                </div>
                <ul class="divide-y divide-line">
                    @foreach ($notifications as $notification)
                        <li class="py-2"><p class="font-semibold">{{ $notification->data['title'] ?? '' }}</p><p class="text-sm text-ink-muted">{{ $notification->data['body'] ?? '' }}</p></li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section>
            <div class="mb-2 flex items-center justify-between"><h2 class="font-head text-lg text-heading">{{ __('student-portal::messages.latest_notices') }}</h2><a href="{{ route('portal.notices') }}" class="text-sm font-semibold">{{ __('student-portal::messages.view_all') }} →</a></div>
            <div class="space-y-2">
                @forelse ($notices as $notice)
                    <a href="{{ route('portal.notices.show', $notice) }}" class="block rounded-xl border border-line bg-surface p-3 no-underline shadow-sm"><p class="text-xs text-ink-muted">{{ bn_date($notice->shownAt()) }} · {{ $notice->category->label() }}</p><p class="font-semibold text-heading">{{ $notice->title() }}</p></a>
                @empty
                    <p class="text-ink-muted">{{ __('student-portal::messages.no_notices') }}</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
