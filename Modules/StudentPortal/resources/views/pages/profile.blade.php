@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.nav.profile'))

@section('content')
    @php
        $dash = '—';
        $groups = [
            ['title' => __('student-portal::messages.personal'), 'rows' => [
                ['name', $student->displayName()],
                ['id', $student->student_id],
                ['class', $student->schoolClass?->displayName()],
                ['section', $student->section?->displayName()],
                ['roll', $student->roll ? bn_digits($student->roll) : null],
                ['session', $student->session ? bn_digits($student->session->name) : null],
                ['dob', $student->date_of_birth ? bn_date($student->date_of_birth) : null],
                ['blood', $student->blood_group],
                ['division', $student->schoolClass?->division?->label()],
            ]],
            ['title' => __('student-portal::messages.guardian'), 'rows' => [
                ['father', $student->father_name],
                ['mother', $student->mother_name],
                ['relation', $student->guardian_relation],
                ['mobile', $student->guardian_phone ? bn_digits($student->guardian_phone) : null],
                ['address', $student->present_address],
            ]],
        ];
    @endphp

    <div data-screen-label="Portal profile" style="display: grid; gap: 16px;">
        <div class="pp-card" style="padding: 20px; display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
            <div style="width: 96px; height: 116px; border-radius: 16px; overflow: hidden; background: var(--surface-2); flex: none; color: var(--ds-muted);">@include('student-portal::partials.photo', ['student' => $student, 'radius' => 16])</div>
            <div style="display: grid; line-height: 1.5;">
                <strong class="pp-head" style="color: var(--heading); font-size: 22px;">{{ $student->displayName() }}</strong>
                <span style="color: var(--ds-muted);">ID: {{ $student->student_id }}</span>
                <span class="pp-info" style="font-size: 13px; padding: 2px 10px; border-radius: 999px; justify-self: start; font-weight: 600; margin-top: 4px;">🔒 {{ __('student-portal::messages.read_only') }}</span>
            </div>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap: 16px;">
            @foreach ($groups as $group)
                <div class="pp-card" style="overflow: hidden;">
                    <h3 class="pp-section-head">{{ $group['title'] }}</h3>
                    @foreach ($group['rows'] as [$key, $value])
                        <div style="display: grid; grid-template-columns: minmax(110px, 40%) 1fr; gap: 10px; padding: 10px 18px; border-top: 1px solid var(--line);"><span style="color: var(--ds-muted);">{{ __('student-portal::messages.fields.'.$key) }}</span><span style="font-weight: 600;">{{ filled($value) ? $value : $dash }}</span></div>
                    @endforeach
                </div>
            @endforeach
        </div>
        <a href="{{ route('student.password.edit') }}" class="pp-btn" style="justify-self: start;">🔑 {{ __('student-portal::messages.change_password') }}</a>
    </div>
@endsection
