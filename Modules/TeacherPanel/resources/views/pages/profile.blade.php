@extends('teacher-panel::layouts.panel')

@section('title', __('teacher-panel::messages.titles.profile'))
@section('screen_title', __('teacher-panel::messages.titles.profile'))

@php
    use Modules\TeacherPanel\Support\PanelFormat;

    $rows = [
        [__('teacher-panel::messages.profile.id'), 'T-'.bn_digits(str_pad((string) $teacher->id, 4, '0', STR_PAD_LEFT))],
        [__('teacher-panel::messages.profile.phone'), bn_digits($teacher->phone)],
        [__('teacher-panel::messages.profile.email'), $user->email],
        [__('teacher-panel::messages.profile.joined'), \Modules\Core\Support\Bangla::date($teacher->created_at)],
    ];
    $head = 'm-0 bg-surface-2 px-[18px] py-3.5 text-[17px] font-bold';
@endphp

@section('content')
    <div class="grid gap-3.5">
        <div class="flex flex-wrap items-center gap-4 rounded-[20px] border border-line bg-surface p-5">
            <div class="size-24 flex-none overflow-hidden rounded-full bg-surface-2">@include('teacher-panel::partials.photo-slot', ['glyph' => 28])</div>
            <div class="grid leading-[1.5]"><strong class="font-head text-[22px] font-bold text-heading">{{ $teacher->displayName() }}</strong><span class="text-ink-muted">{{ $teacher->designation }}</span></div>
        </div>

        <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,340px),1fr))] items-start gap-3.5">
            <div class="overflow-hidden rounded-[20px] border border-line bg-surface">
                <h3 class="{{ $head }}">{{ __('teacher-panel::messages.profile.personal') }}</h3>
                @foreach ($rows as [$key, $value])
                    <div class="grid grid-cols-[minmax(100px,38%)_1fr] gap-2.5 border-t border-line px-[18px] py-2.5"><span class="text-ink-muted">{{ $key }}</span><span class="break-words font-semibold">{{ $value }}</span></div>
                @endforeach
                <div class="grid grid-cols-[minmax(100px,38%)_1fr] items-center gap-2.5 border-t border-line px-[18px] py-2.5"><span class="text-ink-muted">{{ __('teacher-panel::messages.profile.language') }}</span>
                    <span class="flex gap-1.5">
                        <a href="{{ route('core.locale', 'bn') }}" class="rounded-full border px-3 py-0.5 text-[14px] font-bold no-underline {{ app()->getLocale() === 'bn' ? 'border-green-700 bg-green-700 text-white' : 'border-line-strong text-ink' }}">বাংলা</a>
                        <a href="{{ route('core.locale', 'en') }}" class="rounded-full border px-3 py-0.5 text-[14px] font-bold no-underline {{ app()->getLocale() === 'en' ? 'border-green-700 bg-green-700 text-white' : 'border-line-strong text-ink' }}">English</a>
                    </span>
                </div>
                <div class="flex flex-wrap gap-2 border-t border-line px-[18px] py-3">
                    <a href="{{ route('teacher.password') }}" class="flex h-11 items-center rounded-full border border-line-strong bg-surface px-4 font-semibold text-ink no-underline">🔑 {{ __('teacher-panel::messages.profile.change_password') }}</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="h-11 cursor-pointer rounded-full border border-red-600 bg-red-50 px-4 font-bold text-danger">⎋ {{ __('teacher-panel::messages.logout') }}</button></form>
                </div>
            </div>

            <div class="overflow-hidden rounded-[20px] border border-line bg-surface">
                <h3 class="{{ $head }} flex flex-wrap justify-between gap-2">{{ __('teacher-panel::messages.profile.assignments') }} <span class="self-center rounded-full bg-info-bg px-2.5 py-0.5 text-[12px] font-semibold text-info">🔒 {{ __('teacher-panel::messages.profile.read_only') }}</span></h3>
                @forelse ($assignments as $assignment)
                    <div class="flex flex-wrap items-center gap-2 border-t border-line px-[18px] py-2.5"><span class="flex-1 font-semibold">{{ $assignment['label'] }}</span>@if ($assignment['class_teacher'])<span class="rounded-full bg-navy-50 px-2.5 py-px text-[12px] font-bold text-navy-800">★ {{ __('teacher-panel::messages.class_teacher') }}</span>@endif</div>
                @empty
                    <div class="border-t border-line px-[18px] py-2.5 text-ink-muted">{{ __('teacher-panel::messages.no_assignments') }}</div>
                @endforelse
            </div>

            <div class="overflow-hidden rounded-[20px] border border-line bg-surface">
                <h3 class="{{ $head }}">📢 {{ __('teacher-panel::messages.profile.notices') }}</h3>
                @forelse ($notices as $notice)
                    @php($dm = PanelFormat::dayMonth($notice->shownAt()))
                    <a href="{{ route('notices.public.show', $notice) }}" class="grid grid-cols-[52px_1fr] items-center gap-3 border-t border-line px-[18px] py-2.5 text-ink no-underline"><span class="grid rounded-xl bg-green-50 py-1.5 text-center leading-[1.4] text-green-700"><strong class="font-head text-[18px] font-bold">{{ $dm['day'] }}</strong><span class="text-[11px] font-bold">{{ $dm['month'] }}</span></span><span class="font-semibold leading-[1.5]">{{ $notice->title() }}</span></a>
                @empty
                    <div class="border-t border-line px-[18px] py-2.5 text-ink-muted">{{ __('teacher-panel::messages.profile.no_notices') }}</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
