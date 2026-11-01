@extends('core::layouts.public')

@section('title', __('results::messages.public.title'))

@section('content')
    @php
        $student = $result->student;
        $position = $result->position_in_class;
        $ordinal = null;
        if ($position) {
            $ordinal = app()->getLocale() === 'bn'
                ? bn_digits($position).match ((int) $position) { 1 => 'ম', 2, 3 => 'য়', 4 => 'র্থ', 5, 7, 8, 9, 10 => 'ম', 6 => 'ষ্ঠ', default => 'তম' }
                : $position.match (true) { in_array($position % 100, [11, 12, 13], true) => 'th', $position % 10 === 1 => 'st', $position % 10 === 2 => 'nd', $position % 10 === 3 => 'rd', default => 'th' };
        }
        $meta = collect([$student->schoolClass?->displayName(), __('results::messages.sheet.roll').': '.($result->roll ? bn_digits($result->roll) : '—'), 'ID: '.$student->student_id])->filter()->implode(' · ');
    @endphp
    <div style="max-width: 1040px; margin: 0 auto; padding: 40px 16px 0; display: grid; gap: 20px;">
        <div style="display: grid; gap: 4px;"><h1 class="pub-h1-sm">{{ __('results::messages.public.title') }}</h1><span style="color: var(--muted);">{{ __('results::messages.public.subtitle') }}</span></div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap: 20px; align-items: start;">
            @include('results::partials.search-form', ['exams' => $exams, 'classMap' => $classMap])
            <div style="border-radius: 20px; overflow: hidden; background: var(--surface); border: 1px solid var(--line); box-shadow: var(--shadow-md); animation: arm-fade .25s ease;">
                <div style="padding: 16px 20px; background: var(--green-700); color: #fff; display: flex; justify-content: space-between; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <strong style="font-size: 17px;">{{ $result->exam->displayName() }}</strong>
                    <span style="padding: 2px 12px; border-radius: 999px; background: {{ $result->is_passed ? 'var(--yellow-400)' : 'var(--danger-bg)' }}; color: {{ $result->is_passed ? '#1a1a1a' : 'var(--danger)' }}; font-weight: 700; font-size: 14px; display: inline-flex; align-items: center; gap: 4px;">@if ($result->is_passed)<x-core::icon name="check" size="14" />@endif{{ $result->is_passed ? __('results::messages.passed') : __('results::messages.failed') }}</span>
                </div>
                <div style="padding: 20px; display: grid; gap: 16px;">
                    <div style="display: flex; gap: 14px; align-items: center;">
                        <div style="flex: none; width: 64px; height: 64px; border-radius: 16px; overflow: hidden; background: var(--surface-2); display: grid; place-items: center; color: var(--faint); font-size: 12px;">
                            @if ($student->photo_url)<img src="{{ $student->photo_url }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">@else<span>{{ __('results::messages.public.photo') }}</span>@endif
                        </div>
                        <span style="display: grid; line-height: 1.45;"><strong style="font-size: 19px;">{{ $student->displayName() }}</strong><span style="color: var(--muted); font-size: 14px;">{{ $meta }}</span></span>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(90px, 1fr)); gap: 10px;">
                        <div style="padding: 12px; border-radius: 14px; background: var(--green-50); display: grid;"><span style="font-size: 13px; font-weight: 600; color: var(--muted);">{{ __('results::messages.public.gpa') }}</span><span style="font-family: var(--f-head); font-size: 28px; color: var(--green-700); line-height: 1.3;">{{ bn_number($result->gpa, 2) }}</span></div>
                        <div style="padding: 12px; border-radius: 14px; background: var(--navy-50); display: grid;"><span style="font-size: 13px; font-weight: 600; color: var(--muted);">{{ __('results::messages.sheet.grade') }}</span><span style="font-family: Poppins, sans-serif; font-weight: 700; font-size: 26px; color: var(--navy-800); line-height: 1.35;">{{ $result->grade }}</span></div>
                        <div style="padding: 12px; border-radius: 14px; background: var(--yellow-50); display: grid;"><span style="font-size: 13px; font-weight: 600; color: var(--muted);">{{ __('results::messages.public.position') }}</span><span style="font-family: var(--f-head); font-size: 28px; line-height: 1.3;">{{ $ordinal ?? '—' }}</span></div>
                        <div style="padding: 12px; border-radius: 14px; background: var(--surface-2); display: grid;"><span style="font-size: 13px; font-weight: 600; color: var(--muted);">{{ __('results::messages.sheet.total') }}</span><span style="font-family: var(--f-head); font-size: 24px; line-height: 1.4;">{{ bn_number($result->total_marks) }}/{{ bn_number($result->full_marks) }}</span></div>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                        <a href="#marksheet" class="pub-btn-green" style="height: 44px;">{{ __('results::messages.public.view_marksheet') }}</a>
                        <button type="button" onclick="window.print()" class="pub-btn-outline">{{ __('results::messages.public.print') }}</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="marksheet" style="scroll-margin-top: 140px;"><x-results::marksheet-wrapper :result="$result" /></div>
        <p style="text-align: center;"><a class="pub-link" href="{{ route('results.public') }}">{{ __('results::messages.public.back') }}</a></p>
    </div>
@endsection
