@extends('teacher-panel::layouts.panel')

@section('title', __('teacher-panel::messages.marks.title'))

@php
    $config = [
        'url' => route('results.teacher.show', ['exam' => $exam->id, 'class' => $class->id, 'section' => $section->id]), 'csrf' => csrf_token(),
        'text' => ['saved' => __('teacher-panel::messages.marks.saved'), 'changed' => __('teacher-panel::messages.marks.changed', ['count' => ':count']), 'published' => __('teacher-panel::messages.marks.published')],
    ];
@endphp

@section('content')
    <div class="space-y-4" x-data="marksEntry(@js($config))" x-init="boot()">
        <a href="{{ route('teacher.marks') }}" class="text-sm">← {{ __('teacher-panel::messages.marks.title') }}</a>
        <div>
            <h1 class="font-head text-2xl text-heading">{{ $exam->displayName() }}</h1>
            <p class="text-sm text-ink-muted">{{ $class->displayName() }} — {{ $section->displayName() }}</p>
        </div>

        <p x-show="message" x-cloak x-text="message" class="rounded-md bg-success-bg p-3 text-success"></p>
        <p x-show="error" x-cloak x-text="error" class="rounded-md bg-danger-bg p-3 text-danger"></p>
        <p x-show="grid && grid.published" x-cloak class="rounded-md bg-warn-bg p-3 text-warn">{{ __('teacher-panel::messages.marks.published') }}</p>

        <template x-if="grid">
            <div class="space-y-3">
                <div class="text-sm text-ink-muted" x-show="grid.columns.length"><template x-for="column in grid.columns" :key="column.key"><span class="mr-3">{{ __('teacher-panel::messages.marks.full') }} <strong x-text="column.label + ': ' + column.full"></strong></span></template></div>
                <template x-for="student in grid.students" :key="student.id">
                    <div class="rounded-xl border border-line bg-surface p-3 shadow-sm">
                        <p class="mb-2 font-semibold"><span class="text-ink-muted" x-text="student.roll ? '{{ __('teacher-panel::messages.marks.roll') }} ' + student.roll + ' · ' : ''"></span><span x-text="student.name"></span></p>
                        <div class="grid grid-cols-2 gap-3">
                            <template x-for="column in grid.columns" :key="column.key">
                                <div>
                                    <label class="mb-1 block text-xs text-ink-muted" x-text="column.label + ' (' + column.full + ')'"></label>
                                    <input type="number" inputmode="decimal" step="0.25" min="0" :max="column.full" :disabled="grid.published || cell(student, column).absent" :value="cell(student, column).obtained ?? ''" @input="set(student, column, 'obtained', $event.target.value)" class="min-h-11 w-full rounded-lg border border-line-strong bg-surface px-3 text-lg">
                                    <label class="mt-1 flex items-center gap-2 text-xs"><input type="checkbox" :disabled="grid.published" :checked="cell(student, column).absent" @change="set(student, column, 'absent', $event.target.checked)"> {{ __('teacher-panel::messages.marks.absent') }}</label>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <div class="sticky bottom-20 z-20 flex items-center justify-between gap-3 rounded-xl border border-line bg-surface p-3 shadow-md lg:bottom-4" x-show="grid && !grid.published" x-cloak>
            <span class="text-sm text-warn" x-text="changedCount ? text.changed.replace(':count', changedCount) : ''"></span>
            <button type="button" class="min-h-11 rounded-lg bg-brand px-6 font-semibold text-white" :disabled="busy || !changedCount" @click="save()">{{ __('teacher-panel::messages.marks.save') }}</button>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('marksEntry', (cfg) => ({
                grid: null, edits: {}, busy: false, message: '', error: '', text: cfg.text,
                get changedCount() { return Object.keys(this.edits).length; },
                headers() { return { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.csrf }; },
                async boot() { await this.load(); },
                async load() {
                    const response = await fetch(cfg.url, { credentials: 'same-origin', headers: this.headers() });
                    const data = await response.json();
                    if (!response.ok) { this.error = data.message || 'Error'; return; }
                    this.grid = data.data; this.edits = {};
                },
                cell(student, column) {
                    const edited = this.edits[student.id + ':' + column.key];
                    if (edited) { return edited; }
                    const stored = (this.grid.marks[student.id] || {})[column.key];
                    return stored ? { obtained: stored.obtained, absent: stored.absent } : { obtained: null, absent: false };
                },
                set(student, column, field, value) {
                    const next = { ...this.cell(student, column), [field]: field === 'obtained' ? (value === '' ? null : value) : value };
                    if (field === 'absent' && value) { next.obtained = null; }
                    this.edits[student.id + ':' + column.key] = { ...next, student_id: student.id, exam_subject_id: column.exam_subject_id, component_id: column.component_id };
                },
                async save() {
                    this.busy = true; this.error = ''; this.message = '';
                    const entries = Object.values(this.edits).map((edit) => ({ student_id: edit.student_id, exam_subject_id: edit.exam_subject_id, component_id: edit.component_id, obtained: edit.obtained, is_absent: !!edit.absent }));
                    try {
                        const response = await fetch(cfg.url, { method: 'PUT', credentials: 'same-origin', headers: this.headers(), body: JSON.stringify({ entries }) });
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) { throw new Error(data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Error')); }
                        this.message = cfg.text.saved; await this.load();
                    } catch (e) { this.error = e.message; } finally { this.busy = false; }
                },
            }));
        });
    </script>
@endsection
