@extends('core::layouts.public')

@section('title', __('website::messages.routine.title'))

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-10">
        <h1 class="mb-6 font-head text-3xl text-heading">{{ __('website::messages.routine.title') }}</h1>

        @if ($exams->isEmpty())
            <p class="text-ink-muted">{{ __('website::messages.routine.none') }}</p>
        @else
            <form method="GET" class="mb-6 flex flex-wrap gap-3">
                <select name="exam" onchange="this.form.submit()" class="rounded-md border border-line-strong bg-surface px-3" aria-label="{{ __('website::messages.routine.exam') }}">
                    @foreach ($exams as $item)<option value="{{ $item->id }}" @selected($exam?->id === $item->id)>{{ $item->displayName() }}</option>@endforeach
                </select>
                <select name="class" onchange="this.form.submit()" class="rounded-md border border-line-strong bg-surface px-3" aria-label="{{ __('website::messages.routine.class') }}">
                    @foreach ($exam->classes as $item)<option value="{{ $item->id }}" @selected($class?->id === $item->id)>{{ $item->displayName() }}</option>@endforeach
                </select>
                <noscript><button class="rounded-md bg-brand px-4 text-white">{{ __('website::messages.routine.show') }}</button></noscript>
            </form>

            {{-- A table from tablet up, one card per paper on a phone. --}}
            <div class="hidden overflow-hidden rounded-xl border border-line md:block">
                <table class="w-full text-sm">
                    <thead class="bg-green-700 text-white"><tr><th class="p-3 text-left">{{ __('website::messages.routine.date') }}</th><th class="p-3 text-left">{{ __('website::messages.routine.day') }}</th><th class="p-3 text-left">{{ __('website::messages.routine.subject') }}</th><th class="p-3 text-left">{{ __('website::messages.routine.time') }}</th><th class="p-3 text-left">{{ __('website::messages.routine.room') }}</th></tr></thead>
                    <tbody>
                        @forelse ($routine as $row)
                            <tr class="border-b border-line last:border-0"><td class="p-3">{{ bn_date($row->exam_date) }}</td><td class="p-3">{{ \Modules\Core\Support\Bangla::weekday($row->exam_date) }}</td><td class="p-3 font-semibold">{{ $row->subject->displayName() }}</td><td class="p-3">{{ bn_digits(substr($row->start_time, 0, 5)) }} – {{ bn_digits(substr($row->end_time, 0, 5)) }}</td><td class="p-3">{{ $row->room ? bn_digits($row->room) : '—' }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="p-6 text-center text-ink-muted">{{ __('website::messages.routine.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="grid gap-3 md:hidden">
                @forelse ($routine as $row)
                    <div class="rounded-xl border border-line bg-surface p-4 shadow-sm">
                        <p class="font-head text-lg text-heading">{{ $row->subject->displayName() }}</p>
                        <p class="text-sm text-ink-muted">{{ bn_date($row->exam_date) }} · {{ \Modules\Core\Support\Bangla::weekday($row->exam_date) }}</p>
                        <p class="text-sm">{{ bn_digits(substr($row->start_time, 0, 5)) }} – {{ bn_digits(substr($row->end_time, 0, 5)) }}@if ($row->room) · {{ __('website::messages.routine.room') }} {{ bn_digits($row->room) }}@endif</p>
                    </div>
                @empty
                    <p class="text-ink-muted">{{ __('website::messages.routine.empty') }}</p>
                @endforelse
            </div>
        @endif
    </div>
@endsection
