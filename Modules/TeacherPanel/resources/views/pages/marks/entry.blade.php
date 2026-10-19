@extends('teacher-panel::layouts.panel')

@section('title', __('teacher-panel::messages.marks.title'))
@section('screen_title', $exam->displayName())

@php
    $config = [
        'url' => route('results.teacher.show', ['exam' => $exam->id, 'class' => $class->id, 'section' => $section->id]), 'csrf' => csrf_token(),
        'text' => ['saved' => __('teacher-panel::messages.marks.saved'), 'changed' => __('teacher-panel::messages.marks.changed', ['count' => ':count']), 'published' => __('teacher-panel::messages.marks.published')],
    ];
@endphp

@section('content')
    <div class="grid gap-3.5" x-data="marksEntry(@js($config))" x-init="boot()">
        <span class="grid leading-[1.45]"><strong class="font-head text-[20px] font-bold text-heading">{{ $exam->displayName() }}</strong><span class="text-[14px] text-ink-muted">{{ \Modules\TeacherPanel\Support\PanelFormat::section($class->displayName(), $section->displayName()) }}</span></span>

        <p x-show="message" x-cloak x-text="message" class="rounded-[14px] bg-success-bg px-3.5 py-3 font-semibold text-success"></p>
        <p x-show="error" x-cloak x-text="error" role="alert" class="rounded-[14px] bg-danger-bg px-3.5 py-3 font-semibold text-danger"></p>
        <p x-show="grid && grid.published" x-cloak class="rounded-[14px] bg-warn-bg px-3.5 py-3 font-semibold text-warn">{{ __('teacher-panel::messages.marks.published') }}</p>

        <div x-show="!grid && !error" class="grid gap-3" aria-hidden="true">@for ($i = 0; $i < 4; $i++)<div class="tp-skeleton h-[104px] rounded-[20px]"></div>@endfor</div>

        <template x-if="grid">
            <div class="grid gap-3">
                <div class="flex flex-wrap gap-2 text-[13px] text-ink-muted" x-show="grid.columns.length"><template x-for="column in grid.columns" :key="column.key"><span class="rounded-full bg-surface-2 px-2.5 py-0.5">{{ __('teacher-panel::messages.marks.full') }} <strong x-text="column.label + ': ' + column.full"></strong></span></template></div>
                <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,320px),1fr))] gap-3">
                    <template x-for="student in grid.students" :key="student.id">
                        <div class="grid gap-2.5 rounded-[20px] border border-line bg-surface p-3.5">
                            <p class="m-0 flex items-center gap-3 font-bold"><span class="grid size-11 flex-none place-items-center rounded-full bg-green-100 text-[18px] text-green-700" x-text="(student.name || '').charAt(0)"></span><span class="grid leading-[1.4]"><span class="text-[16px]" x-text="student.name"></span><span class="text-[13px] font-normal text-ink-muted" x-text="student.roll ? '{{ __('teacher-panel::messages.marks.roll') }} ' + student.roll : ''"></span></span></p>
                            <div class="grid grid-cols-2 gap-3">
                                <template x-for="column in grid.columns" :key="column.key">
                                    <div>
                                        <label class="mb-1 block text-[12px] font-semibold text-ink-muted" x-text="column.label + ' (' + column.full + ')'"></label>
                                        <input type="number" inputmode="decimal" step="0.25" min="0" :max="column.full" :disabled="grid.published || cell(student, column).absent" :value="cell(student, column).obtained ?? ''" @input="set(student, column, 'obtained', $event.target.value)" class="h-12 w-full rounded-xl border border-line-strong bg-surface px-3 text-[18px]">
                                        <label class="mt-1 flex items-center gap-2 text-[13px]"><input type="checkbox" class="size-5 min-h-0" :disabled="grid.published" :checked="cell(student, column).absent" @change="set(student, column, 'absent', $event.target.checked)"> {{ __('teacher-panel::messages.marks.absent') }}</label>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('marks', { visible: false, changed: '', disabled: true });
            Alpine.data('marksEntry', (cfg) => ({
                grid: null, edits: {}, busy: false, message: '', error: '', text: cfg.text,
                get changedCount() { return Object.keys(this.edits).length; },
                headers() { return { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.csrf }; },
                async boot() {
                    window.addEventListener('tp-marks-save', () => this.save());
                    ['edits', 'busy', 'grid'].forEach((name) => this.$watch(name, () => this.sync()));
                    await this.load();
                },
                sync() {
                    const store = Alpine.store('marks');
                    store.visible = !!this.grid && !this.grid.published;
                    store.disabled = this.busy || !this.changedCount;
                    store.changed = this.changedCount ? this.text.changed.replace(':count', this.changedCount) : '';
                },
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

@section('action_bar')
    <div x-data class="flex items-center gap-2.5 border-t border-line bg-surface px-4 py-2.5 shadow-[0_-6px_20px_rgba(0,0,0,.06)]" x-show="$store.marks.visible" x-cloak>
        <span class="flex-1 text-[14px] font-semibold text-warn" x-text="$store.marks.changed"></span>
        <button type="button" :disabled="$store.marks.disabled" @click="window.dispatchEvent(new CustomEvent('tp-marks-save'))" class="h-[52px] cursor-pointer rounded-full bg-green-700 px-8 text-[16px] font-bold text-white disabled:opacity-60">{{ __('teacher-panel::messages.marks.save') }}</button>
    </div>
@endsection
