@extends('core::layouts.public')

@section('title', __('results::messages.public.title'))

@section('content')
    <div style="max-width: 1040px; margin: 0 auto; padding: 40px 16px 0; display: grid; gap: 20px;">
        <div style="display: grid; gap: 4px;"><h1 class="pub-h1-sm">{{ __('results::messages.public.title') }}</h1><span style="color: var(--muted);">{{ __('results::messages.public.subtitle') }}</span></div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap: 20px; align-items: start;">
            @if ($exams->isEmpty())
                <div style="padding: 40px 20px; border-radius: 20px; border: 2px dashed var(--line-strong); text-align: center; display: grid; gap: 6px; grid-column: 1 / -1;"><x-core::icon name="school" size="32" badge /><strong style="font-size: 18px;">{{ __('results::messages.public.none_published') }}</strong></div>
            @else
                @include('results::partials.search-form', ['exams' => $exams, 'classMap' => $classMap])
                <div style="display: grid;">
                    @if ($notFound)
                        <div role="alert" style="padding: 36px 20px; border-radius: 20px; background: var(--danger-bg); text-align: center; display: grid; gap: 6px;"><span style="font-size: 32px;">✕</span><strong style="font-size: 18px; color: var(--danger);">{{ __('results::messages.public.not_found_title') }}</strong><span>{{ __('results::messages.public.not_found') }}</span></div>
                    @elseif ($blocked)
                        <div role="alert" style="padding: 36px 20px; border-radius: 20px; background: var(--warn-bg); text-align: center; display: grid; gap: 6px;"><x-core::icon name="clock" size="32" badge /><strong style="font-size: 18px; color: var(--warn);">{{ __('results::messages.public.blocked_title') }}</strong><span>{{ $blocked }}</span></div>
                    @else
                        <div style="padding: 40px 20px; border-radius: 20px; border: 2px dashed var(--line-strong); text-align: center; display: grid; gap: 6px;"><x-core::icon name="school" size="32" badge /><strong style="font-size: 18px;">{{ __('results::messages.public.idle_title') }}</strong><span style="color: var(--muted);">{{ __('results::messages.public.idle_body') }}</span></div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
