@extends('teacher-panel::layouts.panel')

@section('title', __('teacher-panel::messages.titles.remarks'))
@section('screen_title', __('teacher-panel::messages.titles.remarks'))

@php
    // Literal class strings so Tailwind keeps them; chosen by the kind's colour name.
    $tones = [
        'success' => ['soft' => 'bg-success-bg text-success', 'on' => 'border-success bg-success-bg text-success'],
        'warn' => ['soft' => 'bg-warn-bg text-warn', 'on' => 'border-warn bg-warn-bg text-warn'],
        'danger' => ['soft' => 'bg-danger-bg text-danger', 'on' => 'border-danger bg-danger-bg text-danger'],
        'info' => ['soft' => 'bg-info-bg text-info', 'on' => 'border-info bg-info-bg text-info'],
    ];
    $avatars = ['bg-green-100 text-green-700', 'bg-navy-50 text-navy-800', 'bg-yellow-50 text-warn', 'bg-red-50 text-danger'];
    $kindMap = $allKinds->map(fn (array $kind): array => ['label' => $kind['label'], 'icon' => $kind['icon'], 'soft' => $tones[$kind['color']]['soft'], 'on' => $tones[$kind['color']]['on']])->all();
    $config = [
        'csrf' => csrf_token(), 'url' => route('teacher.remarks.store'), 'section_id' => $current['section_id'] ?? null, 'sectionLabel' => $current['label'] ?? '', 'writable' => $isSchoolDay,
        'students' => $students, 'kinds' => $kinds->map(fn (array $kind): array => [...$kind, ...$tones[$kind['color']]])->values(), 'kindMap' => $kindMap,
        'text' => ['roll' => __('teacher-panel::messages.remarks.roll'), 'saved' => __('teacher-panel::messages.remarks.saved'), 'bulkSaved' => __('teacher-panel::messages.remarks.bulk_saved', ['count' => ':count']), 'pick' => __('teacher-panel::messages.remarks.pick_first'), 'selected' => __('teacher-panel::messages.remarks.selected', ['count' => ':count'])],
        'digits' => app()->getLocale() === 'bn',
    ];
@endphp

@section('content')
    <div class="grid gap-3.5" x-data="remarksBoard(@js($config))" @keydown.escape.window="sheet = null">
        @if (! $current)
            <div class="rounded-[20px] border border-line bg-surface p-5 text-ink-muted">{{ __('teacher-panel::messages.no_assignments') }}</div>
        @else
            <div class="flex flex-wrap items-end gap-2.5">
                <form method="GET" action="{{ route('teacher.remarks') }}" x-data class="grid flex-[1_1_220px]">
                    <label class="grid gap-1 text-[14px] font-bold">{{ __('teacher-panel::messages.diary.class_section') }}
                        <select name="section_id" @change="$el.form.submit()" class="h-12 w-full rounded-xl border border-line-strong bg-surface px-3">
                            @foreach ($sections as $row)<option value="{{ $row['section_id'] }}" @selected($row['section_id'] === $current['section_id'])>{{ $row['label'] }}</option>@endforeach
                        </select>
                    </label>
                </form>
                <button type="button" @click="toggleBulk()" class="h-12 cursor-pointer whitespace-nowrap rounded-xl border px-4 font-bold" :class="bulk ? 'border-green-700 bg-green-700 text-white' : 'border-line-strong bg-surface text-ink'" x-text="bulk ? '✕ {{ __('teacher-panel::messages.remarks.cancel_bulk') }}' : '☑ {{ __('teacher-panel::messages.remarks.bulk') }}'"></button>
            </div>
            @unless ($isSchoolDay)<p class="rounded-[14px] bg-warn-bg px-3.5 py-3 font-semibold text-warn">{{ __('teacher-panel::messages.remarks.holiday') }}</p>@endunless
            <span class="text-[14px] text-ink-muted" x-text="bulk ? '{{ __('teacher-panel::messages.remarks.hint_bulk') }}' : '{{ __('teacher-panel::messages.remarks.hint') }}'">{{ __('teacher-panel::messages.remarks.hint') }}</span>
            @if ($students->isEmpty())<p class="text-ink-muted">{{ __('teacher-panel::messages.remarks.no_students') }}</p>@endif

            <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,320px),1fr))] gap-2">
                <template x-for="(student, index) in students" :key="student.id">
                    <button type="button" @click="tap(student)" class="flex min-h-16 cursor-pointer items-center gap-3 rounded-2xl border px-3 py-2.5 text-left text-ink" :class="selected.includes(student.id) ? 'border-green-700 bg-green-50' : 'border-line bg-surface'">
                        <span x-show="bulk" x-cloak class="grid size-6 flex-none place-items-center rounded-[7px] border-2 text-[14px] font-bold text-white" :class="selected.includes(student.id) ? 'border-green-700 bg-green-700' : 'border-line-strong bg-transparent'" x-text="selected.includes(student.id) ? '✓' : ''"></span>
                        <span class="grid size-11 flex-none place-items-center rounded-full text-[18px] font-bold" :class="avatars[index % 4]" x-text="student.initial"></span>
                        <span class="grid min-w-0 flex-1 leading-[1.4]"><strong class="text-[16px]" x-text="student.name"></strong><span class="text-[13px] text-ink-muted" x-text="rollLabel(student)"></span></span>
                        <span class="flex max-w-[45%] flex-wrap justify-end gap-1">
                            <template x-for="kind in student.today" :key="kind"><span class="whitespace-nowrap rounded-full px-2 py-px text-[12px] font-bold" :class="kindMap[kind].soft" x-text="kindMap[kind].label"></span></template>
                        </span>
                    </button>
                </template>
            </div>

            <div x-show="sheet" x-cloak class="fixed inset-0 z-[60] flex items-end justify-center bg-[rgba(10,20,15,.45)] lg:items-center">
                <div role="dialog" aria-label="{{ __('teacher-panel::messages.remarks.dialog') }}" class="grid max-h-[88%] w-full max-w-[520px] gap-3.5 overflow-y-auto rounded-t-[24px] bg-surface px-[18px] pb-[22px] pt-[18px] shadow-[var(--shadow-lg)] lg:rounded-[24px]" x-show="sheet">
                    <template x-if="sheet">
                        <div class="grid gap-3.5">
                            <div class="flex items-center gap-3">
                                <span class="grid size-12 flex-none place-items-center rounded-full bg-green-100 text-[20px] font-bold text-green-700" x-text="sheet.initial"></span>
                                <span class="grid flex-1 leading-[1.4]"><strong class="text-[18px]" x-text="sheet.name"></strong><span class="text-[14px] text-ink-muted" x-text="rollLabel(sheet) + ' · ' + cfg.sectionLabel"></span></span>
                                <button type="button" @click="sheet = null" aria-label="{{ __('teacher-panel::messages.close') }}" class="size-11 cursor-pointer rounded-full border border-line bg-surface">✕</button>
                            </div>
                            <div class="grid gap-1.5">
                                <span class="text-[14px] font-bold">{{ __('teacher-panel::messages.remarks.kind') }}</span>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="kind in cfg.kinds" :key="kind.value">
                                        <button type="button" @click="form.kind = kind.value" class="min-h-11 cursor-pointer rounded-full border-2 px-3.5 text-[14px] font-bold" :class="form.kind === kind.value ? kind.on : 'border-line bg-surface text-ink'" x-text="kind.icon + ' ' + kind.label"></button>
                                    </template>
                                </div>
                            </div>
                            <label class="grid gap-1 text-[14px] font-bold">{{ __('teacher-panel::messages.remarks.text') }}<textarea x-model="form.text" rows="3" maxlength="500" placeholder="{{ __('teacher-panel::messages.remarks.placeholder') }}" class="w-full rounded-xl border border-line-strong bg-surface px-3 py-2.5 font-normal leading-[1.6]"></textarea></label>
                            <div x-show="sheet.recent.length" class="grid gap-1.5">
                                <span class="text-[14px] font-bold text-ink-muted">{{ __('teacher-panel::messages.remarks.previous') }}</span>
                                <template x-for="remark in sheet.recent" :key="remark.id">
                                    <div class="rounded-xl px-3 py-2 text-[14px] leading-[1.5] text-ink" :class="kindMap[remark.kind].soft.split(' ')[0]"><strong :class="kindMap[remark.kind].soft.split(' ')[1]" x-text="kindMap[remark.kind].label"></strong> · <span x-text="remark.when"></span><br><span x-text="remark.text || '—'"></span></div>
                                </template>
                            </div>
                            <p x-show="error" x-cloak x-text="error" class="rounded-[14px] bg-danger-bg px-3.5 py-3 font-semibold text-danger"></p>
                            <button type="button" @click="saveOne()" :disabled="busy" class="h-[52px] cursor-pointer rounded-full bg-green-700 text-[16px] font-bold text-white">{{ __('teacher-panel::messages.remarks.save') }}</button>
                        </div>
                    </template>
                </div>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('remarks', { label: '', active: false });
            Alpine.data('remarksBoard', (cfg) => ({
                cfg, students: cfg.students, kindMap: cfg.kindMap, avatars: @js($avatars), bulk: false, selected: [], sheet: null, form: { kind: 'praise', text: '' }, busy: false, error: '',
                init() { this.$watch('selected', () => this.sync()); this.$watch('bulk', () => this.sync()); window.addEventListener('tp-bulk-apply', () => this.saveBulk()); },
                sync() { Alpine.store('remarks').active = this.bulk; Alpine.store('remarks').label = cfg.text.selected.replace(':count', this.digits(this.selected.length)); },
                digits(value) { return cfg.digits ? String(value).replace(/[0-9]/g, (d) => '০১২৩৪৫৬৭৮৯'[d]) : String(value); },
                rollLabel(student) { return cfg.text.roll + ' ' + this.digits(String(student.roll ?? '').padStart(2, '0')); },
                toggleBulk() { this.bulk = !this.bulk; this.selected = []; },
                tap(student) {
                    if (!cfg.writable) { return; }
                    if (this.bulk) { this.selected = this.selected.includes(student.id) ? this.selected.filter((id) => id !== student.id) : [...this.selected, student.id]; return; }
                    this.sheet = student; this.form = { kind: 'praise', text: '' }; this.error = '';
                },
                async post(ids, kind, text) {
                    this.busy = true; this.error = '';
                    try {
                        const response = await fetch(cfg.url, { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.csrf }, body: JSON.stringify({ section_id: cfg.section_id, student_ids: ids, kind, text }) });
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) { throw new Error(data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Error')); }
                        data.data.forEach((row) => {
                            const student = this.students.find((item) => item.id === row.student_id);
                            if (student && !student.today.includes(row.kind)) { student.today.push(row.kind); }
                            if (student) { student.recent = [row, ...student.recent].slice(0, 3); }
                        });
                        return true;
                    } catch (e) { this.error = e.message; return false; } finally { this.busy = false; }
                },
                async saveOne() {
                    if (await this.post([this.sheet.id], this.form.kind, this.form.text.trim())) { this.sheet = null; window.dispatchEvent(new CustomEvent('tp-toast', { detail: cfg.text.saved })); }
                },
                async saveBulk() {
                    if (!this.selected.length) { window.dispatchEvent(new CustomEvent('tp-toast', { detail: cfg.text.pick })); return; }
                    const count = this.selected.length;
                    if (await this.post(this.selected, 'no_homework', '')) { this.selected = []; this.bulk = false; window.dispatchEvent(new CustomEvent('tp-toast', { detail: cfg.text.bulkSaved.replace(':count', this.digits(count)) })); }
                    else { window.dispatchEvent(new CustomEvent('tp-toast', { detail: this.error })); }
                },
            }));
        });
    </script>
@endsection

@section('action_bar')
    <div x-data x-show="$store.remarks && $store.remarks.active" x-cloak class="flex flex-wrap items-center gap-2.5 border-t border-line bg-surface px-4 py-2.5 shadow-[0_-6px_20px_rgba(0,0,0,.06)]">
        <span class="min-w-[120px] flex-1 font-bold" x-text="$store.remarks.label"></span>
        <button type="button" @click="window.dispatchEvent(new CustomEvent('tp-bulk-apply'))" class="h-12 cursor-pointer rounded-full bg-warn px-[18px] font-bold text-white">! {{ __('teacher-panel::messages.remarks.no_homework') }}</button>
    </div>
@endsection
