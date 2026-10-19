@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.nav.notices'))

@section('content')
    <div data-screen-label="Portal notices" style="display: grid; gap: 10px;">
        @forelse ($notices as $notice)
            @php($shown = $notice->shownAt())
            <a href="{{ route('portal.notices.show', $notice) }}" style="display: grid; grid-template-columns: 60px 1fr; gap: 12px; align-items: center; padding: 12px; border-radius: 16px; background: var(--surface); border: 1px solid var(--line); text-decoration: none; color: var(--ink);">
                <span style="display: grid; text-align: center; line-height: 1.45; padding: 8px 0; border-radius: 12px; background: var(--green-50); color: var(--green-700);"><span class="pp-head" style="font-size: 22px;">{{ bn_digits($shown->day) }}</span><span style="font-size: 12px; font-weight: 700;">{{ \Modules\Core\Support\Bangla::monthName($shown) }}</span></span>
                <span style="font-weight: 600; line-height: 1.5;">{{ $notice->title() }}</span>
            </a>
        @empty
            @include('student-portal::partials.empty', ['title' => __('student-portal::messages.no_notices')])
        @endforelse
        {{ $notices->links() }}
    </div>
@endsection
