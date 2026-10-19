@extends('core::layouts.base')

@section('title', __('core::messages.verify.title'))

@section('body')
    <main class="mx-auto flex min-h-screen max-w-lg flex-col justify-center gap-4 px-4 py-10">
        <div class="rounded-2xl border border-line bg-surface p-6 shadow-md">
            @if ($result)
                <p class="mb-2 inline-block rounded-full bg-success-bg px-3 py-1 text-sm font-semibold text-success">
                    ✔ {{ __('core::messages.verify.valid') }}
                </p>
                <h1 class="mb-4 font-head text-2xl text-heading">{{ $result->title }}</h1>
                <dl class="divide-y divide-line">
                    @foreach ($result->details as $label => $value)
                        <div class="flex justify-between gap-4 py-2">
                            <dt class="text-ink-muted">{{ $label }}</dt>
                            <dd class="text-right font-medium">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <p class="mb-2 inline-block rounded-full bg-danger-bg px-3 py-1 text-sm font-semibold text-danger">
                    ✖ {{ __('core::messages.verify.invalid') }}
                </p>
                <h1 class="font-head text-2xl text-heading">{{ __('core::messages.verify.not_found') }}</h1>
            @endif
        </div>
    </main>
@endsection
