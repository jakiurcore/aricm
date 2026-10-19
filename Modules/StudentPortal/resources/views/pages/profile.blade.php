@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.profile'))

@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between"><h1 class="font-head text-2xl text-heading">{{ __('student-portal::messages.profile') }}</h1><a href="{{ route('portal.idcard') }}" target="_blank" class="rounded-md bg-brand px-4 py-2 font-semibold text-white no-underline">🪪 {{ __('student-portal::messages.id_card') }}</a></div>
        <div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <div class="mb-4 flex items-center gap-4">
                <div class="grid size-20 place-items-center overflow-hidden rounded-full border border-line bg-surface-2 text-3xl">@if ($student->photo_url)<img src="{{ $student->photo_url }}" alt="" class="size-full object-cover">@else{{ mb_substr($student->name_bn, 0, 1) }}@endif</div>
                <div><p class="font-head text-2xl text-heading">{{ $student->name_bn }}</p><p class="text-ink-muted">{{ $student->name_en }}</p><p class="text-sm">{{ $student->student_id }}</p></div>
            </div>
            <dl class="grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
                @foreach ([
                    ['class', ($student->schoolClass?->displayName() ?? '—').($student->section ? ' / '.$student->section->displayName() : '')], ['roll', $student->roll ? bn_digits($student->roll) : '—'], ['session', $student->session?->name],
                    ['father', $student->father_name], ['mother', $student->mother_name], ['guardian', trim(($student->guardian_name ?? '').' '.($student->guardian_relation ? '('.$student->guardian_relation.')' : ''))],
                    ['guardian_phone', $student->guardian_phone ? bn_digits($student->guardian_phone) : '—'], ['dob', bn_date($student->date_of_birth)], ['gender', $student->gender->label()],
                    ['blood', $student->blood_group], ['religion', $student->religion], ['admission', bn_date($student->admission_date)], ['residency', $student->residency_type->label()],
                    ['present_address', $student->present_address], ['permanent_address', $student->permanent_address],
                ] as [$key, $value])
                    <div class="flex justify-between gap-4 border-b border-line py-1.5"><dt class="text-ink-muted">{{ __("student-portal::messages.fields.{$key}") }}</dt><dd class="text-right font-medium">{{ $value ?: '—' }}</dd></div>
                @endforeach
            </dl>
            <p class="mt-4 text-xs text-ink-muted">{{ __('student-portal::messages.read_only') }}</p>
        </div>
        <a href="{{ route('student.password.edit') }}" class="inline-block rounded-md border border-line-strong px-4 py-2 font-semibold no-underline">🔑 {{ __('student-portal::messages.change_password') }}</a>
    </div>
@endsection
