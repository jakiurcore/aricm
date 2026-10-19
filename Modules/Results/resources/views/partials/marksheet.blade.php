@php
    $student = $result->student;
    $subjects = collect($result->subject_results);
@endphp

<article class="rounded-2xl border border-line bg-surface p-5 shadow-md">
    <header class="mb-4 border-b border-line pb-4 text-center">
        <h2 class="font-head text-xl text-heading">{{ setting('institute_name_'.app()->getLocale()) }}</h2>
        <p class="font-semibold">{{ $result->exam->displayName() }}</p>
    </header>

    <dl class="mb-4 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2">
        <div class="flex justify-between"><dt class="text-ink-muted">{{ __('results::messages.sheet.name') }}</dt><dd class="font-medium">{{ $student->displayName() }}</dd></div>
        <div class="flex justify-between"><dt class="text-ink-muted">{{ __('results::messages.sheet.student_id') }}</dt><dd class="font-medium">{{ $student->student_id }}</dd></div>
        <div class="flex justify-between"><dt class="text-ink-muted">{{ __('results::messages.sheet.class') }}</dt><dd class="font-medium">{{ $student->schoolClass?->displayName() }}{{ $student->section ? ' / '.$student->section->displayName() : '' }}</dd></div>
        <div class="flex justify-between"><dt class="text-ink-muted">{{ __('results::messages.sheet.roll') }}</dt><dd class="font-medium">{{ $result->roll ? bn_digits($result->roll) : '—' }}</dd></div>
    </dl>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-line text-left text-ink-muted">
                    <th class="py-2">{{ __('results::messages.sheet.subject') }}</th>
                    <th class="py-2 text-right">{{ __('results::messages.sheet.full') }}</th>
                    <th class="py-2 text-right">{{ __('results::messages.sheet.obtained') }}</th>
                    <th class="py-2 text-right">{{ __('results::messages.sheet.grade') }}</th>
                    <th class="py-2 text-right">{{ __('results::messages.sheet.point') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subjects as $subject)
                    <tr class="border-b border-line">
                        <td class="py-2">
                            {{ $subject['name'] }}
                            @if ($subject['isOptional'])<span class="text-xs text-ink-muted">({{ __('results::messages.sheet.optional') }})</span>@endif
                            @if (count($subject['components']) > 1)
                                <div class="text-xs text-ink-muted">
                                    @foreach ($subject['components'] as $component)
                                        {{ $component['name'] }}: {{ $component['absent'] ? __('results::messages.sheet.absent') : bn_number($component['obtained'] ?? 0, 0) }}/{{ bn_number($component['full']) }}@if (! $loop->last) · @endif
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="py-2 text-right">{{ bn_number($subject['fullMarks']) }}</td>
                        <td class="py-2 text-right">{{ $subject['absent'] ? __('results::messages.sheet.absent') : bn_number($subject['total']) }}</td>
                        <td class="py-2 text-right font-semibold {{ $subject['passed'] ? '' : 'text-danger' }}">{{ $subject['grade'] }}</td>
                        <td class="py-2 text-right">{{ bn_number($subject['point'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <footer class="mt-4 grid gap-3 text-center sm:grid-cols-4">
        <div class="rounded-lg bg-surface-2 p-3"><div class="text-xs text-ink-muted">{{ __('results::messages.sheet.total') }}</div><div class="text-lg font-semibold">{{ bn_number($result->total_marks) }}/{{ bn_number($result->full_marks) }}</div></div>
        <div class="rounded-lg bg-surface-2 p-3"><div class="text-xs text-ink-muted">GPA</div><div class="text-lg font-semibold">{{ bn_number($result->gpa, 2) }}</div></div>
        <div class="rounded-lg bg-surface-2 p-3"><div class="text-xs text-ink-muted">{{ __('results::messages.sheet.grade') }}</div><div class="text-lg font-semibold">{{ $result->grade }}</div></div>
        <div class="rounded-lg p-3 {{ $result->is_passed ? 'bg-success-bg text-success' : 'bg-danger-bg text-danger' }}">
            <div class="text-xs">{{ __('results::messages.sheet.result') }}</div>
            <div class="text-lg font-semibold">{{ $result->is_passed ? __('results::messages.passed') : __('results::messages.failed') }}</div>
        </div>
    </footer>

    @if ($result->position_in_class)
        <p class="mt-3 text-center text-sm text-ink-muted">{{ __('results::messages.sheet.position') }}: {{ bn_digits($result->position_in_class) }}</p>
    @endif
</article>
