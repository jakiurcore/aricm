@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.marksheet'))

@section('content')
    <div data-screen-label="Portal marksheet" style="display: grid; gap: 14px;">
        <div style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center;">
            <a href="{{ route('portal.results') }}" class="pp-btn" style="height: 44px; min-height: 44px; padding: 0 14px; border: 1px solid var(--line);">← {{ __('student-portal::messages.back') }}</a>
            @if ($eligibility->allowed)
                <a href="{{ route('portal.results.pdf', $exam) }}" target="_blank" class="pp-btn-red" style="height: 44px; padding: 0 18px; border-radius: 999px; display: flex; align-items: center; text-decoration: none; font-weight: 700; color: #fff;">⬇ {{ __('student-portal::messages.download_pdf') }}</a>
            @endif
        </div>

        @if ($eligibility->allowed)
            @php($subjects = collect($result->subject_results))
            <div style="padding: 14px 16px; border-radius: 16px; background: var(--navy-50); color: var(--navy-800); font-weight: 700;">{{ $exam->displayName() }}</div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 10px;">
                <div style="padding: 14px; border-radius: 16px; background: var(--green-700); color: #fff; display: grid;"><span style="font-size: 13px; opacity: .9;">GPA</span><strong class="pp-head" style="font-size: 28px; line-height: 1.3;">{{ bn_number($result->gpa, 2) }}</strong></div>
                <div style="padding: 14px; border-radius: 16px; background: var(--navy-50); display: grid;"><span style="font-size: 13px; color: var(--ds-muted);">{{ __('student-portal::messages.grade') }}</span><strong class="pp-en" style="font-size: 26px; color: var(--navy-800); line-height: 1.35;">{{ $result->grade }}</strong></div>
                <div style="padding: 14px; border-radius: 16px; background: var(--yellow-50); display: grid;"><span style="font-size: 13px; color: var(--ds-muted);">{{ __('student-portal::messages.position') }}</span><strong class="pp-head" style="font-size: 28px; line-height: 1.3;">{{ $result->position_in_class ? \Modules\StudentPortal\Support\PortalFormat::ordinal($result->position_in_class) : '—' }}</strong></div>
                <div class="pp-card" style="padding: 14px; border-radius: 16px; display: grid;"><span style="font-size: 13px; color: var(--ds-muted);">{{ __('student-portal::messages.total') }}</span><strong class="pp-head" style="font-size: 22px; line-height: 1.45;">{{ bn_number($result->total_marks) }}/{{ bn_number($result->full_marks) }}</strong></div>
            </div>
            <div class="pp-card" style="overflow-x: auto;">
                <table style="width: 100%; min-width: 620px; border-collapse: collapse; font-size: 15px;">
                    <thead><tr style="background: var(--surface-2); text-align: left;"><th style="padding: 12px 14px;">{{ __('student-portal::messages.subject') }}</th><th style="padding: 12px 8px;">{{ __('student-portal::messages.full') }}</th><th style="padding: 12px 8px;">{{ __('student-portal::messages.parts') }}</th><th style="padding: 12px 8px;">{{ __('student-portal::messages.obtained') }}</th><th style="padding: 12px 8px;">{{ __('student-portal::messages.grade') }}</th><th style="padding: 12px 14px;">GP</th></tr></thead>
                    <tbody>
                        @foreach ($subjects as $subject)
                            <tr style="border-top: 1px solid var(--line);">
                                <td style="padding: 11px 14px; font-weight: 600;">{{ $subject['name'] }}@if ($subject['isOptional']) <span style="font-weight: 400; color: var(--ds-muted); font-size: 13px;">({{ __('student-portal::messages.optional') }})</span>@endif</td>
                                <td style="padding: 11px 8px;">{{ bn_number($subject['fullMarks']) }}</td>
                                <td style="padding: 11px 8px; color: var(--ds-muted); font-size: 14px;">@if (count($subject['components']) > 1){{ collect($subject['components'])->map(fn ($component): string => $component['name'].' '.($component['absent'] ? __('student-portal::messages.absent') : bn_number($component['obtained'] ?? 0)).'/'.bn_number($component['full']))->implode(' · ') }}@else—@endif</td>
                                <td style="padding: 11px 8px; font-weight: 700;">{{ $subject['absent'] ? __('student-portal::messages.absent') : bn_number($subject['total']) }}</td>
                                <td style="padding: 11px 8px;"><span class="pp-en {{ $subject['passed'] ? '' : 'pp-bad' }}" style="padding: 1px 10px; border-radius: 999px; {{ $subject['passed'] ? 'background: var(--green-50); color: var(--green-700);' : '' }} font-weight: 700; font-size: 14px;">{{ $subject['grade'] }}</span></td>
                                <td style="padding: 11px 14px;">{{ bn_number($subject['point'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="pp-warn" style="padding: 24px 20px; border-radius: 20px; text-align: center; display: grid; gap: 10px; justify-items: center;">
                <strong style="font-size: 17px;">⏳ {{ $eligibility->reason }}</strong>
                @if ($eligibility->actionRoute && \Illuminate\Support\Facades\Route::has($eligibility->actionRoute))
                    <a href="{{ route($eligibility->actionRoute) }}" class="pp-btn-red" style="min-height: 48px; padding: 0 22px; border-radius: 999px; display: inline-flex; align-items: center; text-decoration: none; font-weight: 700; color: #fff;">{{ __('student-portal::messages.pay_now') }}</a>
                @endif
            </div>
        @endif
    </div>
@endsection
