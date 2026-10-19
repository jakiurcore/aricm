@extends('teacher-panel::layouts.panel')

@section('title', __('teacher-panel::messages.diary.title'))

@php
    $config = [
        'class_id' => $section->class_id, 'section_id' => $section->id, 'subject_id' => $subject?->id, 'date' => $date->toDateString(), 'writable' => $writable,
        'csrf' => csrf_token(), 'maxAttachments' => $maxAttachments, 'students' => $students,
        'urls' => [
            'show' => route('diary.teacher.show'), 'save' => route('diary.teacher.save'), 'phrases' => route('diary.teacher.phrases'), 'addPhrase' => route('diary.teacher.phrases.add'),
            'entries' => url('teacher/diary/entries'), 'attachments' => url('teacher/diary/attachments'), 'remarks' => url('teacher/diary/remarks'), 'phraseBase' => url('teacher/diary/phrases'),
        ],
        'text' => [
            'savedAt' => __('teacher-panel::messages.diary.saved_at', ['time' => ':time']), 'unsaved' => __('teacher-panel::messages.diary.unsaved'), 'saving' => __('teacher-panel::messages.diary.saving'),
            'published' => __('teacher-panel::messages.diary.published_notice'), 'restored' => __('teacher-panel::messages.diary.restored'), 'offline' => __('teacher-panel::messages.diary.offline'), 'saveFirst' => __('teacher-panel::messages.diary.save_first'),
        ],
    ];
    $fields = ['lesson', 'homework', 'note'];
@endphp

@section('content')
    <div class="mx-auto max-w-2xl space-y-4" x-data="diaryEditor(@js($config))" x-init="boot()">
        <a href="{{ route('teacher.diary', ['date' => $date->toDateString()]) }}" class="text-sm">← {{ __('teacher-panel::messages.diary.back') }}</a>

        <div>
            <h1 class="font-head text-2xl text-heading">{{ $subject?->displayName() ?? __('teacher-panel::messages.diary.general') }}</h1>
            <p class="text-sm text-ink-muted">{{ $section->schoolClass->displayName() }} — {{ $section->displayName() }} · {{ \Modules\Core\Support\Bangla::date($date) }}</p>
        </div>

        <div class="sticky top-16 z-20 flex items-center justify-between gap-2 rounded-lg border border-line bg-surface px-3 py-2 text-sm shadow-sm">
            <span x-text="statusText" :class="dirty ? 'text-warn' : 'text-ink-muted'"></span>
            <span x-show="entry && entry.status === 'published'" x-cloak class="rounded-full bg-success-bg px-2.5 py-0.5 text-xs font-bold text-success">{{ __('teacher-panel::messages.status.published') }}</span>
        </div>

        <p x-show="notice" x-cloak x-text="notice" class="rounded-md bg-warn-bg p-3 text-sm text-warn"></p>
        <p x-show="error" x-cloak x-text="error" class="rounded-md bg-danger-bg p-3 text-sm text-danger"></p>
        @unless ($writable)<p class="rounded-md bg-warn-bg p-3 text-sm text-warn">{{ __('teacher-panel::messages.diary.locked') }}</p>@endunless

        <form @submit.prevent="save(false)" class="space-y-5">
            @foreach ($fields as $field)
                @continue($subject === null && $field !== 'note')
                <div>
                    <label for="f-{{ $field }}" class="mb-1 block font-semibold">{{ __('teacher-panel::messages.diary.'.$field) }}</label>
                    <div class="mb-2 flex gap-2 overflow-x-auto pb-1" x-show="(phrases['{{ $field }}'] || []).length">
                        <template x-for="phrase in phrases['{{ $field }}'] || []" :key="phrase.id">
                            <button type="button" class="flex-none rounded-full border border-line-strong bg-surface-2 px-3 py-1.5 text-sm" @click="addPhrase('{{ $field }}', phrase.text)" x-text="phrase.text"></button>
                        </template>
                    </div>
                    <textarea id="f-{{ $field }}" rows="{{ $field === 'note' ? 3 : 4 }}" x-model="form.{{ $field }}" @input="touch()" @disabled(! $writable) class="min-h-24 w-full rounded-lg border border-line-strong bg-surface p-3 text-base"></textarea>
                </div>
                @if ($field === 'homework' && $subject)
                    <div>
                        <label for="f-due" class="mb-1 block text-sm font-semibold">{{ __('teacher-panel::messages.diary.due') }}</label>
                        <input id="f-due" type="date" x-model="form.homework_due_date" @input="touch()" @disabled(! $writable) class="min-h-11 rounded-lg border border-line-strong bg-surface px-3">
                    </div>
                @endif
            @endforeach

            @if ($writable)
                <details class="rounded-lg border border-line bg-surface-2 p-3">
                    <summary class="cursor-pointer text-sm font-semibold">{{ __('teacher-panel::messages.diary.add_phrase') }}</summary>
                    <div class="mt-3 grid gap-2">
                        <select x-model="newPhrase.field" class="min-h-11 rounded-lg border border-line-strong bg-surface px-3">
                            @foreach ([...$fields, 'remark'] as $field)<option value="{{ $field }}">{{ $field === 'remark' ? __('teacher-panel::messages.diary.remarks') : __('teacher-panel::messages.diary.'.$field) }}</option>@endforeach
                        </select>
                        <input type="text" x-model="newPhrase.text" maxlength="255" placeholder="{{ __('teacher-panel::messages.diary.phrase_placeholder') }}" class="min-h-11 rounded-lg border border-line-strong bg-surface px-3">
                        <button type="button" class="min-h-11 rounded-md border border-line-strong font-semibold" @click="createPhrase()">+ {{ __('teacher-panel::messages.diary.add_phrase') }}</button>
                    </div>
                </details>

                <div class="grid gap-2 sm:grid-cols-2">
                    <button type="submit" class="min-h-12 rounded-lg border border-line-strong font-semibold" :disabled="busy">{{ __('teacher-panel::messages.diary.save_draft') }}</button>
                    <button type="button" class="min-h-12 rounded-lg bg-brand font-semibold text-white" :disabled="busy" @click="save(true)" x-text="entry && entry.status === 'published' ? '{{ __('teacher-panel::messages.diary.update') }}' : '{{ __('teacher-panel::messages.diary.publish') }}'"></button>
                </div>
                <p class="text-xs text-ink-muted">{{ __('teacher-panel::messages.diary.autosave') }}</p>
            @endif
        </form>

        {{-- Attachments and remarks need a saved entry. --}}
        <section class="space-y-2 rounded-xl border border-line bg-surface p-4">
            <h2 class="font-head text-lg text-heading">{{ __('teacher-panel::messages.diary.attachments') }}</h2>
            <ul class="space-y-1 text-sm">
                <template x-for="file in entry ? entry.attachments : []" :key="file.id">
                    <li class="flex items-center justify-between gap-2"><a :href="file.url" target="_blank" rel="noopener" x-text="file.name"></a><button type="button" class="text-danger" @click="removeFile(file)">{{ __('teacher-panel::messages.diary.remove') }}</button></li>
                </template>
            </ul>
            @if ($writable)<input type="file" accept="image/png,image/jpeg,image/webp,application/pdf" @change="upload($event)" class="block w-full text-sm" aria-label="{{ __('teacher-panel::messages.diary.upload') }}">@endif
        </section>

        @if ($subject)
            <section class="space-y-3 rounded-xl border border-line bg-surface p-4">
                <h2 class="font-head text-lg text-heading">{{ __('teacher-panel::messages.diary.remarks') }}</h2>
                <ul class="space-y-2 text-sm">
                    <template x-for="remark in entry ? entry.remarks : []" :key="remark.id">
                        <li class="flex items-start justify-between gap-2 rounded-md bg-surface-2 p-2"><span><strong x-text="remark.student"></strong> — <span x-text="remark.text"></span></span><button type="button" class="text-danger" @click="removeRemark(remark)">✕</button></li>
                    </template>
                </ul>
                <div class="grid gap-2">
                    <select x-model="remark.student_id" class="min-h-11 rounded-lg border border-line-strong bg-surface px-3"><option value="">{{ __('teacher-panel::messages.diary.student') }} — {{ __('teacher-panel::messages.diary.choose') }}</option><template x-for="student in students" :key="student.id"><option :value="student.id" x-text="(student.roll ? student.roll + '. ' : '') + student.name"></option></template></select>
                    <select x-model="remark.kind" class="min-h-11 rounded-lg border border-line-strong bg-surface px-3">@foreach ($kinds as $kind)<option value="{{ $kind['value'] }}">{{ $kind['label'] }}</option>@endforeach</select>
                    <div class="flex gap-2 overflow-x-auto pb-1"><template x-for="phrase in phrases['remark'] || []" :key="phrase.id"><button type="button" class="flex-none rounded-full border border-line-strong bg-surface-2 px-3 py-1.5 text-sm" @click="remark.text = phrase.text" x-text="phrase.text"></button></template></div>
                    <input type="text" x-model="remark.text" maxlength="500" placeholder="{{ __('teacher-panel::messages.diary.remark_text') }}" class="min-h-11 rounded-lg border border-line-strong bg-surface px-3">
                    <button type="button" class="min-h-11 rounded-md border border-line-strong font-semibold" :disabled="!remark.student_id || !remark.text" @click="addRemark()">+ {{ __('teacher-panel::messages.diary.add_remark') }}</button>
                </div>
            </section>
        @endif
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('diaryEditor', (cfg) => ({
                entry: null, form: { lesson: '', homework: '', note: '', homework_due_date: '' }, dirty: false, busy: false, savedAt: null, error: '', notice: '',
                phrases: {}, students: cfg.students, newPhrase: { field: 'lesson', text: '' }, remark: { student_id: '', kind: 'info', text: '' },
                get key() { return ['diary', cfg.class_id, cfg.section_id, cfg.subject_id ?? 'general', cfg.date].join(':'); },
                get statusText() { return this.busy ? cfg.text.saving : (this.dirty ? cfg.text.unsaved : (this.savedAt ? cfg.text.savedAt.replace(':time', this.savedAt) : '')); },
                headers(json = true) { return { Accept: 'application/json', 'X-CSRF-TOKEN': cfg.csrf, ...(json ? { 'Content-Type': 'application/json' } : {}) }; },
                async call(url, options = {}) {
                    const response = await fetch(url, { credentials: 'same-origin', ...options, headers: { ...this.headers(!(options.body instanceof FormData)), ...(options.headers || {}) } });
                    const data = response.status === 204 ? null : await response.json().catch(() => ({}));
                    if (!response.ok) { const first = data && data.errors ? Object.values(data.errors)[0][0] : (data && data.message) || 'Error'; throw new Error(first); }
                    return data;
                },
                async boot() {
                    try { this.phrases = (await this.call(cfg.urls.phrases)).data; } catch (e) {}
                    try {
                        const query = new URLSearchParams({ class_id: cfg.class_id, section_id: cfg.section_id, date: cfg.date, ...(cfg.subject_id ? { subject_id: cfg.subject_id } : {}) });
                        const data = (await this.call(cfg.urls.show + '?' + query)).data;
                        if (data) { this.apply(data); }
                    } catch (e) { this.error = e.message; }
                    this.restoreBackup();
                    if (cfg.writable) { setInterval(() => { if (this.dirty && !this.busy) { this.save(false, true); } }, 20000); }
                    window.addEventListener('beforeunload', (event) => { if (this.dirty) { event.preventDefault(); event.returnValue = ''; } });
                },
                apply(data) {
                    this.entry = data;
                    this.form = { lesson: data.lesson || '', homework: data.homework || '', note: data.note || '', homework_due_date: data.homework_due_date || '' };
                    this.savedAt = data.saved_at ? new Date(data.saved_at).toLocaleTimeString() : null;
                },
                // The phone keeps a copy of every keystroke, so a dropped connection or a closed tab never loses the lesson.
                backup() { try { localStorage.setItem(this.key, JSON.stringify({ form: this.form, at: Date.now() })); } catch (e) {} },
                clearBackup() { try { localStorage.removeItem(this.key); } catch (e) {} },
                restoreBackup() {
                    try {
                        const saved = JSON.parse(localStorage.getItem(this.key) || 'null');
                        if (!saved) { return; }
                        const serverAt = this.entry && this.entry.saved_at ? Date.parse(this.entry.saved_at) : 0;
                        const differs = JSON.stringify(saved.form) !== JSON.stringify(this.form);
                        if (saved.at > serverAt && differs) { this.form = { ...this.form, ...saved.form }; this.dirty = true; this.notice = cfg.text.restored; }
                        else { this.clearBackup(); }
                    } catch (e) {}
                },
                touch() { this.dirty = true; this.backup(); },
                addPhrase(field, text) { this.form[field] = (this.form[field] ? this.form[field].trimEnd() + ' ' : '') + text; this.touch(); },
                async save(publish = false, silent = false) {
                    if (this.busy || !cfg.writable) { return; }
                    this.busy = true; if (!silent) { this.error = ''; }
                    try {
                        const body = { class_id: cfg.class_id, section_id: cfg.section_id, subject_id: cfg.subject_id, entry_date: cfg.date, ...this.form, homework_due_date: this.form.homework_due_date || null, publish };
                        const data = (await this.call(cfg.urls.save, { method: 'PUT', body: JSON.stringify(body) })).data;
                        this.apply(data); this.dirty = false; this.clearBackup(); this.notice = publish ? cfg.text.published : '';
                    } catch (e) {
                        this.error = navigator.onLine ? e.message : cfg.text.offline;
                    } finally { this.busy = false; }
                },
                async ensureEntry() { if (!this.entry) { await this.save(false); } return this.entry; },
                async upload(event) {
                    const file = event.target.files[0]; event.target.value = '';
                    if (!file) { return; }
                    if (!(await this.ensureEntry())) { this.error = cfg.text.saveFirst; return; }
                    const form = new FormData(); form.append('file', file);
                    try { const data = await this.call(cfg.urls.entries + '/' + this.entry.id + '/attachments', { method: 'POST', body: form }); this.entry.attachments.push(data.data); } catch (e) { this.error = e.message; }
                },
                async removeFile(file) { try { await this.call(cfg.urls.attachments + '/' + file.id, { method: 'DELETE' }); this.entry.attachments = this.entry.attachments.filter((item) => item.id !== file.id); } catch (e) { this.error = e.message; } },
                async addRemark() {
                    if (!(await this.ensureEntry())) { this.error = cfg.text.saveFirst; return; }
                    try { const data = await this.call(cfg.urls.entries + '/' + this.entry.id + '/remarks', { method: 'POST', body: JSON.stringify(this.remark) }); this.entry.remarks.push(data.data); this.remark.text = ''; } catch (e) { this.error = e.message; }
                },
                async removeRemark(remark) { try { await this.call(cfg.urls.remarks + '/' + remark.id, { method: 'DELETE' }); this.entry.remarks = this.entry.remarks.filter((item) => item.id !== remark.id); } catch (e) { this.error = e.message; } },
                async createPhrase() {
                    if (!this.newPhrase.text.trim()) { return; }
                    try {
                        const data = (await this.call(cfg.urls.addPhrase, { method: 'POST', body: JSON.stringify({ field: this.newPhrase.field, text_bn: this.newPhrase.text }) })).data;
                        this.phrases[this.newPhrase.field] = [...(this.phrases[this.newPhrase.field] || []), data]; this.newPhrase.text = '';
                    } catch (e) { this.error = e.message; }
                },
            }));
        });
    </script>
@endsection
