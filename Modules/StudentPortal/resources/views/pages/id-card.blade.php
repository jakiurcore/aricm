@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.nav.idcard'))

@section('content')
    @php
        $identity = app(\Modules\Core\Services\PublicSite::class)->identity();
        $details = array_filter([$student->schoolClass?->displayName(), $student->roll ? __('student-portal::messages.roll').' '.bn_digits($student->roll) : null, $student->blood_group]);
    @endphp

    <div data-screen-label="Portal ID card" style="display: grid; gap: 16px; justify-items: center;">
        <div style="width: 260px; border-radius: 18px; overflow: hidden; background: #fff; color: #17212B; box-shadow: var(--shadow-lg); border: 1px solid var(--line);">
            <div style="background: #0B6B3A; color: #fff; padding: 12px; display: grid; justify-items: center; text-align: center; gap: 4px;">
                <img src="{{ $identity['logo'] }}" alt="" style="width: 44px; height: 44px; border-radius: 50%; background: #fff; object-fit: cover;">
                <strong style="font-size: 13px; line-height: 1.35; font-family: 'Noto Serif Bengali', serif;">{{ $identity['name'] }}</strong>
            </div>
            <div style="padding: 14px; display: grid; justify-items: center; gap: 8px; text-align: center;">
                <div style="width: 92px; height: 110px; border-radius: 12px; overflow: hidden; border: 3px solid #FFD21F; background: #F3F4EE; color: #566170;">@include('student-portal::partials.photo', ['student' => $student, 'radius' => 8, 'caption' => 12])</div>
                <strong style="font-size: 16px; line-height: 1.35;">{{ $student->displayName() }}</strong>
                <span style="font-size: 13px; padding: 1px 10px; border-radius: 999px; background: #D6202A; color: #fff; font-weight: 700;">{{ __('student-portal::messages.id_label') }} {{ $student->student_id }}</span>
                <span style="font-size: 13px; color: #566170;">{{ implode(' · ', $details) }}</span>
            </div>
        </div>
        <a href="{{ route('portal.idcard.pdf') }}" target="_blank" class="pp-btn-green" style="height: 52px; padding: 0 26px; border-radius: 999px; display: flex; align-items: center; text-decoration: none; font-weight: 700; color: #fff;">⬇ {{ __('student-portal::messages.download_id') }}</a>
    </div>
@endsection
