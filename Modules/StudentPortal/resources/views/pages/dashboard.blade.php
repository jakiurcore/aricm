@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.nav.dashboard'))

@section('content')
    @php
        $locale = app()->getLocale();
        $chips = array_filter([
            $student->schoolClass?->displayName(),
            $student->section ? __('student-portal::messages.chip_section').': '.$student->section->displayName() : null,
            $student->roll ? __('student-portal::messages.roll').': '.bn_digits($student->roll) : null,
            $student->session ? __('student-portal::messages.chip_session').': '.bn_digits($student->session->name) : null,
        ]);
        $cardLink = 'text-align: left; padding: 18px; border-radius: 20px; border: 1px solid var(--line); background: var(--surface); box-shadow: var(--shadow-sm); display: grid; gap: 6px; text-decoration: none; color: var(--ink);';
    @endphp

    <div data-screen-label="Portal dashboard" style="display: grid; gap: 16px;">
        <div style="border-radius: 24px; padding: 20px; background: linear-gradient(135deg, var(--green-700), var(--green-900)); color: #fff; display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
            <div style="width: 76px; height: 76px; border-radius: 20px; overflow: hidden; border: 3px solid #fff; background: rgba(255,255,255,.15); flex: none;">@include('student-portal::partials.photo', ['student' => $student, 'radius' => 16])</div>
            <div style="flex: 1; min-width: 200px; display: grid; line-height: 1.45;">
                <span style="font-size: 15px; opacity: .9;">{{ __('student-portal::messages.salam') }}</span>
                <strong class="pp-head" style="font-size: 24px;">{{ $student->displayName() }}</strong>
                <span style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px;">
                    @foreach ($chips as $chip)<span style="padding: 2px 10px; border-radius: 999px; background: rgba(255,255,255,.16); font-size: 13px; font-weight: 600;">{{ $chip }}</span>@endforeach
                </span>
            </div>
        </div>

        @if ($due->isPositive())
            <div style="padding: 18px; border-radius: 20px; border: 2px solid var(--red-600); background: var(--red-50); display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
                <span class="bg-red-600" style="width: 52px; height: 52px; border-radius: 16px; color: #fff; display: grid; place-items: center; font-size: 24px; font-weight: 700; flex: none;">৳</span>
                <span style="flex: 1; min-width: 180px; display: grid; line-height: 1.45;">
                    <span style="font-size: 13px; font-weight: 700; color: var(--danger);">{{ __('student-portal::messages.due') }}</span>
                    <strong class="pp-head" style="font-size: 26px; color: var(--danger);">{{ $due->format() }}</strong>
                    <span style="font-size: 14px; color: var(--ink);">{{ __('student-portal::messages.dash_invoices', ['count' => bn_digits($openInvoices->count())]) }}@if ($firstDue) · {{ __('student-portal::messages.last_date') }} {{ bn_date($firstDue) }}@endif</span>
                </span>
                <a href="{{ route('portal.fees.pay', ['invoice' => $openInvoices->first()?->id]) }}" class="pp-btn-red" style="min-height: 48px; padding: 0 20px; border-radius: 999px; font-size: 16px; display: inline-flex; align-items: center; text-decoration: none; font-weight: 700; color: #fff;">{{ __('student-portal::messages.pay_arrow') }}</a>
            </div>
        @endif

        @if (\Illuminate\Support\Facades\Route::has('portal.diary'))
            <a href="{{ route('portal.diary') }}" style="padding: 18px; border-radius: 20px; border: 1px solid {{ $diary['unseen'] && ! $diary['acknowledged'] ? 'var(--red-600)' : 'var(--line)' }}; background: var(--surface); box-shadow: var(--shadow-sm); display: flex; gap: 14px; align-items: center; flex-wrap: wrap; color: var(--ink); text-decoration: none;">
                <span style="width: 52px; height: 52px; border-radius: 16px; background: var(--green-700); color: #fff; display: grid; place-items: center; font-size: 24px; flex: none;">📓</span>
                <span style="flex: 1; min-width: 200px; display: grid; line-height: 1.5;">
                    <span style="font-size: 13px; font-weight: 700; color: var(--green-700);">{{ __('student-portal::messages.today_diary') }} · {{ \Modules\StudentPortal\Support\PortalFormat::dayMonth($diary['date']) }}</span>
                    <strong style="font-size: 17px;">@if ($diary['hasEntries']){{ bn_digits($diary['subjects']) }}{{ __('student-portal::messages.n_subjects') }} · {{ bn_digits($diary['homework']) }}{{ __('student-portal::messages.n_homework') }}@else{{ __('student-portal::messages.no_diary') }}@endif</strong>
                    <span style="font-size: 14px; color: var(--ds-muted);">@if ($diary['hasEntries'] && $diary['prepSubjects']){{ __('student-portal::messages.prep_short') }}: {{ \Modules\Core\Support\Bangla::weekday($diary['prepDay']) }} — {{ implode(', ', $diary['prepSubjects']) }}@elseif (! $diary['hasEntries']){{ __('student-portal::messages.no_diary_body') }}@endif</span>
                </span>
                @if ($diary['hasEntries'])
                    <span class="pp-pill {{ $diary['acknowledged'] ? 'pp-ok' : 'pp-bad' }}" style="padding: 4px 12px; font-size: 14px;">{{ $diary['acknowledged'] ? '✓ '.__('student-portal::messages.acked') : '● '.__('student-portal::messages.not_acked') }}</span>
                @endif
            </a>
        @endif

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 240px), 1fr)); gap: 12px;">
            <a href="{{ route('portal.routine') }}" style="{{ $cardLink }}">
                <span style="font-size: 13px; font-weight: 700; color: var(--navy-800);">🗓 {{ __('student-portal::messages.upcoming') }}</span>
                @if ($exam)
                    <strong style="font-size: 17px; color: var(--ink);">{{ $exam->displayName() }}</strong>
                    <span style="color: var(--ds-muted); font-size: 14px;">{{ __('student-portal::messages.starts') }}: {{ bn_date($exam->start_date) }}</span>
                @else
                    <strong style="font-size: 17px; color: var(--ink);">{{ __('student-portal::messages.no_exam') }}</strong>
                @endif
            </a>
            <a href="{{ route('portal.notices') }}" style="{{ $cardLink }}">
                <span style="font-size: 13px; font-weight: 700; color: var(--green-700);">📢 {{ __('student-portal::messages.latest_notice') }}</span>
                @if ($notice)
                    <strong style="font-size: 16px; color: var(--ink); line-height: 1.5;">{{ $notice->title() }}</strong>
                    <span style="color: var(--ds-muted); font-size: 14px;">{{ bn_date($notice->shownAt()) }}</span>
                @else
                    <strong style="font-size: 16px; color: var(--ink); line-height: 1.5;">{{ __('student-portal::messages.no_notices') }}</strong>
                @endif
            </a>
            <a href="{{ $result ? route('portal.results.show', $result->exam_id) : route('portal.results') }}" style="{{ $cardLink }}">
                <span style="font-size: 13px; font-weight: 700; color: var(--green-700);">🏅 {{ __('student-portal::messages.latest_result') }}</span>
                @if ($result)
                    <strong style="font-size: 16px; color: var(--ink);">{{ $result->exam->displayName() }}</strong>
                    @if ($resultEligibility?->allowed)
                        <span style="font-size: 14px;"><strong style="color: var(--green-700);">GPA {{ bn_number($result->gpa, 2) }}</strong> · {{ $result->grade }}</span>
                    @else
                        <span style="font-size: 14px; color: var(--warn);">⏳ {{ $resultEligibility?->reason }}</span>
                    @endif
                @else
                    <strong style="font-size: 16px; color: var(--ink);">{{ __('student-portal::messages.no_results') }}</strong>
                @endif
            </a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px;">
            @foreach ([['portal.admit', '🎫', 'admit'], ['portal.idcard', '🪪', 'idcard'], ['portal.fees', '৳', 'fees'], ['portal.profile', '👤', 'profile']] as [$route, $icon, $key])
                <a href="{{ route($route) }}" style="min-height: 76px; border-radius: 16px; border: 1px solid var(--line); background: var(--surface); display: grid; place-items: center; align-content: center; gap: 2px; font-weight: 600; color: var(--ink); text-decoration: none;"><span style="font-size: 22px;">{{ $icon }}</span>{{ __('student-portal::messages.nav.'.$key) }}</a>
            @endforeach
        </div>

        @if ($notifications->isNotEmpty())
            <section class="pp-card pp-card-sm" style="overflow: hidden;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 12px 18px; background: var(--surface-2);">
                    <h3 style="margin: 0; font-size: 17px;">🔔 {{ __('student-portal::messages.notifications') }}</h3>
                    <form method="POST" action="{{ route('portal.notifications.read') }}">@csrf<button type="submit" class="pp-btn" style="min-height: 40px; font-size: 14px;">{{ __('student-portal::messages.mark_read') }}</button></form>
                </div>
                @foreach ($notifications as $notification)
                    <div style="padding: 12px 18px; border-top: 1px solid var(--line); display: grid; line-height: 1.5;"><strong>{{ $notification->data['title'] ?? '' }}</strong><span style="color: var(--ds-muted); font-size: 14px;">{{ $notification->data['body'] ?? '' }}</span></div>
                @endforeach
            </section>
        @endif
    </div>
@endsection
