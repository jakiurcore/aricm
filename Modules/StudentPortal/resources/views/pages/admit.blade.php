@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.nav.admit'))

@section('content')
    @php($identity = app(\Modules\Core\Services\PublicSite::class)->identity())
    <div data-screen-label="Portal admit card" style="display: grid; gap: 14px; max-width: 640px;">
        @forelse ($rows as $row)
            @php($eligibility = $row['eligibility'])
            @php($unpaid = ! $eligibility->allowed && $eligibility->actionRoute && \Illuminate\Support\Facades\Route::has($eligibility->actionRoute))
            <div class="pp-card" style="padding: 22px; border-radius: 24px; box-shadow: var(--shadow-md); display: grid; gap: 14px;">
                <div style="display: flex; gap: 12px; align-items: center;"><img src="{{ $identity['logo'] }}" alt="" style="width: 48px; height: 48px; border-radius: 50%; background: #fff; object-fit: cover;"><span style="display: grid; line-height: 1.4;"><strong style="font-size: 18px;">{{ __('student-portal::messages.admit_title') }}</strong><span style="color: var(--ds-muted);">{{ $row['exam']->displayName() }}</span></span></div>
                @if ($eligibility->allowed)
                    <div style="display: grid; gap: 12px;">
                        <div class="pp-ok" style="padding: 12px 14px; border-radius: 12px; font-weight: 600;">✓ {{ __('student-portal::messages.admit_ready') }}</div>
                        <a href="{{ route('portal.admit.pdf', $row['exam']) }}" target="_blank" class="pp-btn-green" style="height: 52px; border-radius: 999px; font-size: 17px; display: grid; place-items: center; text-decoration: none; font-weight: 700; color: #fff;">⬇ {{ __('student-portal::messages.download_admit') }}</a>
                    </div>
                @else
                    <div style="display: grid; gap: 12px;">
                        <div class="{{ $unpaid ? 'pp-bad' : 'pp-warn' }}" style="padding: 12px 14px; border-radius: 12px; font-weight: 600;">{{ $unpaid ? '⚠' : '⏳' }} {{ $eligibility->reason }}</div>
                        <button type="button" disabled style="height: 52px; border-radius: 999px; border: 0; background: var(--surface-2); color: var(--faint); font-weight: 700; font-size: 17px;">⬇ {{ __('student-portal::messages.download_admit') }}</button>
                        @if ($unpaid)<a href="{{ route($eligibility->actionRoute) }}" style="height: 44px; border-radius: 999px; border: 2px solid var(--red-600); background: transparent; color: var(--danger); font-weight: 700; display: grid; place-items: center; text-decoration: none;">{{ __('student-portal::messages.see_fees') }}</a>@endif
                    </div>
                @endif
            </div>
        @empty
            <div class="pp-card" style="padding: 22px; border-radius: 24px; box-shadow: var(--shadow-md); display: grid; gap: 14px;">
                <div style="display: flex; gap: 12px; align-items: center;"><img src="{{ $identity['logo'] }}" alt="" style="width: 48px; height: 48px; border-radius: 50%; background: #fff; object-fit: cover;"><span style="display: grid; line-height: 1.4;"><strong style="font-size: 18px;">{{ __('student-portal::messages.admit_title') }}</strong></span></div>
                <div style="display: grid; gap: 12px;">
                    <div class="pp-warn" style="padding: 12px 14px; border-radius: 12px; font-weight: 600;">⏳ {{ __('student-portal::messages.no_admit') }}</div>
                    <button type="button" disabled style="height: 52px; border-radius: 999px; border: 0; background: var(--surface-2); color: var(--faint); font-weight: 700; font-size: 17px;">⬇ {{ __('student-portal::messages.download_admit') }}</button>
                </div>
            </div>
        @endforelse
    </div>
@endsection
