@extends('core::layouts.public')

@section('title', __('notices::messages.title'))

@section('content')
    <div style="max-width: 960px; margin: 0 auto; padding: 40px 16px 0; display: grid; gap: 20px;">
        <h1 class="pub-h1-sm">{{ __('notices::messages.title') }}</h1>

        <form method="GET" action="{{ route('notices.public.index') }}" role="search">
            @if ($category)<input type="hidden" name="category" value="{{ $category }}">@endif
            <input type="search" name="q" value="{{ request('q') }}" placeholder="🔍  {{ __('notices::messages.search') }}" aria-label="{{ __('notices::messages.search') }}" style="width: 100%; height: 52px; padding: 0 16px; border-radius: 16px; border: 1px solid var(--line-strong); background: var(--surface); font-size: 16px;">
        </form>

        <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px;">
            @php($chips = [['url' => route('notices.public.index', array_filter(['q' => request('q')])), 'label' => __('notices::messages.all'), 'on' => ! $category]])
            @foreach ($categories as $item)
                @php($chips[] = ['url' => route('notices.public.index', array_filter(['category' => $item->value, 'q' => request('q')])), 'label' => $item->label(), 'on' => $category === $item->value])
            @endforeach
            @foreach ($chips as $chip)
                <a href="{{ $chip['url'] }}" @if ($chip['on']) aria-current="true" @endif style="flex: none; height: 40px; padding: 0 16px; border-radius: 999px; display: inline-flex; align-items: center; text-decoration: none; border: 1px solid {{ $chip['on'] ? 'var(--green-700)' : 'var(--line-strong)' }}; background: {{ $chip['on'] ? 'var(--green-700)' : 'var(--surface)' }}; color: {{ $chip['on'] ? '#fff' : 'var(--ink)' }}; font-weight: 600;">{{ $chip['label'] }}</a>
            @endforeach
        </div>

        @if ($notices->isEmpty())
            <div style="padding: 40px 20px; border-radius: 20px; border: 2px dashed var(--line-strong); text-align: center; display: grid; gap: 6px;"><span style="font-size: 32px;">📭</span><strong style="font-size: 18px;">{{ __('notices::messages.none') }}</strong><span style="color: var(--muted);">{{ __('notices::messages.none_body') }}</span></div>
        @else
            <div style="display: grid; gap: 10px;">
                @foreach ($notices as $notice)
                    <x-notices::notice-row :notice="$notice" :list="true" />
                @endforeach
            </div>
        @endif

        <div>{{ $notices->links() }}</div>
    </div>
@endsection
