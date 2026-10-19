@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.notices'))

@section('content')
    <div class="space-y-4">
        <h1 class="font-head text-2xl text-heading">{{ __('student-portal::messages.notices') }}</h1>
        @forelse ($notices as $notice)
            <a href="{{ route('portal.notices.show', $notice) }}" class="block rounded-xl border border-line bg-surface p-4 no-underline shadow-sm"><p class="text-xs text-ink-muted">{{ bn_date($notice->shownAt()) }} · {{ $notice->category->label() }}@if ($notice->is_pinned) · 📌@endif</p><p class="font-semibold text-heading">{{ $notice->title() }}</p></a>
        @empty
            <p class="text-ink-muted">{{ __('student-portal::messages.no_notices') }}</p>
        @endforelse
        {{ $notices->links() }}
    </div>
@endsection
