@extends('core::layouts.public')

@section('title', __('admission::messages.title'))

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-10">
        <h1 class="font-head text-3xl text-heading">{{ __('admission::messages.title') }}</h1>

        @if ($settings->bannerEnabled())
            <div class="mt-4 rounded-xl bg-green-50 p-5">
                <p class="font-head text-2xl text-green-700">{{ $settings->text('banner_title') }}</p>
                <p class="mt-1 text-ink">
                    @if ($settings->get('admission_start')){{ __('admission::messages.admission_start') }}: <strong>{{ bn_date($settings->get('admission_start')) }}</strong>@endif
                    @if ($settings->get('class_start')) · {{ __('admission::messages.class_start') }}: <strong>{{ bn_date($settings->get('class_start')) }}</strong>@endif
                </p>
                @if ($settings->text('free_class_note'))<p class="mt-2 font-semibold text-red-700">{{ $settings->text('free_class_note') }}</p>@endif
            </div>
        @endif

        <div class="mt-8 grid gap-8 lg:grid-cols-2">
            <div>
                @if ($settings->text('program'))<h2 class="mb-2 font-head text-xl text-heading">{{ $settings->text('program') }}</h2>@endif
                <div class="whitespace-pre-line leading-relaxed">{{ $settings->text('content') }}</div>
                <p class="mt-6 text-sm text-ink-muted">{{ __('admission::messages.call_us') }}:
                    @foreach ((array) setting('phones', []) as $phone)<a href="tel:{{ $phone }}">{{ bn_digits($phone) }}</a>@if (! $loop->last), @endif @endforeach
                </p>
            </div>

            <div class="rounded-2xl border border-line bg-surface p-6 shadow-md">
                <h2 class="mb-4 font-head text-xl text-heading">{{ __('admission::messages.form_title') }}</h2>

                @if (session('inquiry_sent'))
                    <p class="mb-4 rounded-md bg-success-bg p-3 font-medium text-success">{{ __('admission::messages.sent') }}</p>
                @endif

                <form method="POST" action="{{ route('admission.inquiry.store') }}" class="space-y-4">
                    @csrf
                    <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                    @foreach ([['student_name', 'text'], ['guardian_name', 'text'], ['phone', 'tel']] as [$name, $type])
                        <div>
                            <label for="{{ $name }}" class="mb-1 block text-sm font-medium">{{ __("admission::messages.{$name}") }}</label>
                            <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name) }}" required class="w-full rounded-md border border-line-strong bg-surface px-3">
                            @error($name)<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                        </div>
                    @endforeach

                    <div>
                        <label for="desired_class" class="mb-1 block text-sm font-medium">{{ __('admission::messages.desired_class') }}</label>
                        <select id="desired_class" name="desired_class" class="w-full rounded-md border border-line-strong bg-surface px-3">
                            <option value="">—</option>
                            @foreach ($settings->classes() as $class)<option value="{{ $class }}" @selected(old('desired_class') === $class)>{{ $class }}</option>@endforeach
                        </select>
                        @error('desired_class')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="message" class="mb-1 block text-sm font-medium">{{ __('admission::messages.message') }}</label>
                        <textarea id="message" name="message" rows="3" class="w-full rounded-md border border-line-strong bg-surface px-3 py-2">{{ old('message') }}</textarea>
                    </div>

                    <button type="submit" class="min-h-11 w-full rounded-md bg-brand px-5 font-semibold text-white">{{ __('admission::messages.submit') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection
