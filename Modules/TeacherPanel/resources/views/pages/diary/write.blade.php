@extends('teacher-panel::layouts.panel')

@section('title', __('teacher-panel::messages.titles.'.($exists ? 'write_edit' : 'write_new')))
@section('screen_title', __('teacher-panel::messages.titles.'.($exists ? 'write_edit' : 'write_new')))

@php
    $isGeneral = $subject === null;
    $nextSchoolDay = app(\Modules\Academic\Services\SchoolCalendar::class)->nextSchoolDay($date)->toDateString();
    $config = [
        'class_id' => $section->class_id, 'section_id' => $section->id, 'subject_id' => $subject?->id, 'date' => $date->toDateString(), 'writable' => $writable, 'nextSchoolDay' => $nextSchoolDay,
        'csrf' => csrf_token(), 'maxAttachments' => $maxAttachments, 'maxBytes' => $maxKb * 1024, 'students' => $students, 'options' => $options, 'others' => $others, 'home' => route('teacher.home'),
        'locale' => app()->getLocale() === 'bn' ? 'bn-BD' : 'en-US',
        'urls' => [
            'write' => route('teacher.diary.write'), 'previous' => route('teacher.diary.previous'),
            'show' => route('diary.teacher.show'), 'save' => route('diary.teacher.save'), 'phrases' => route('diary.teacher.phrases'), 'addPhrase' => route('diary.teacher.phrases.add'),
            'entries' => url('teacher/diary/entries'), 'attachments' => url('teacher/diary/attachments'),
        ],
        'text' => [
            'savedAt' => __('teacher-panel::messages.diary.saved_at', ['time' => ':time']), 'unsaved' => __('teacher-panel::messages.diary.unsaved'), 'saving' => __('teacher-panel::messages.diary.saving'), 'idle' => __('teacher-panel::messages.diary.autosave_on'),
            'restored' => __('teacher-panel::messages.diary.restored'), 'offline' => __('teacher-panel::messages.diary.offline'), 'saveFirst' => __('teacher-panel::messages.diary.save_first'),
            'empty' => __('teacher-panel::messages.diary.empty'), 'draftSaved' => __('teacher-panel::messages.diary.draft_saved'), 'publishedToast' => __('teacher-panel::messages.diary.published_toast'),
            'publishedMany' => __('teacher-panel::messages.diary.published_many', ['count' => ':count']), 'phraseSaved' => __('teacher-panel::messages.diary.phrase_saved'),
            'noPrevious' => __('teacher-panel::messages.diary.no_previous'), 'copied' => __('teacher-panel::messages.diary.copied', ['date' => ':date']), 'tooBig' => __('teacher-panel::messages.diary.too_big', ['mb' => bn_digits(round($maxKb / 1024))]),
            'nextDay' => __('teacher-panel::messages.next_day'), 'update' => __('teacher-panel::messages.diary.update'), 'publish' => __('teacher-panel::messages.diary.publish'), 'publishMany' => __('teacher-panel::messages.diary.publish_many', ['count' => ':count']),
            'reload' => __('teacher-panel::messages.diary.reloading'),
        ],
    ];
    $fields = [
        'lesson' => ['icon' => '📖', 'tone' => 'bg-green-50 text-green-700', 'phrases' => 'lesson'],
        'next_lesson' => ['icon' => '→', 'tone' => 'bg-navy-50 text-navy-800', 'phrases' => 'lesson'],
        'homework' => ['icon' => '✎', 'tone' => 'bg-yellow-50 text-warn', 'phrases' => 'homework'],
        'note' => ['icon' => '!', 'tone' => 'bg-red-50 text-danger', 'phrases' => 'note'],
    ];
    $inputClass = 'h-12 w-full rounded-xl border border-line-strong bg-surface px-3';
@endphp

@section('content')
    <div class="grid gap-3.5" x-data="diaryEditor(@js($config))" x-init="boot()" @tp-save.window="save($event.detail.publish)">
        <div class="flex flex-wrap items-center justify-between gap-2.5">
            <span class="inline-flex items-center gap-2 whitespace-nowrap rounded-full px-3 py-1 text-[13px] font-bold" :class="statusClass" x-text="statusText">{{ __('teacher-panel::messages.diary.autosave_on') }}</span>
            @if ($writable)<button type="button" @click="copyPrevious()" class="h-10 cursor-pointer whitespace-nowrap rounded-full border border-line-strong bg-surface px-3.5 text-[14px] font-semibold">⟲ {{ __('teacher-panel::messages.diary.copy_previous') }}</button>@endif
        </div>

        <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,200px),1fr))] gap-3 rounded-[20px] border border-line bg-surface p-3.5">
            <label class="grid gap-1 text-[14px] font-bold">{{ __('teacher-panel::messages.diary.date') }}<input type="date" value="{{ $date->toDateString() }}" max="{{ $today }}" @change="go({ date: $event.target.value || cfg.date })" class="{{ $inputClass }}"></label>
            <label class="grid gap-1 text-[14px] font-bold">{{ __('teacher-panel::messages.diary.class_section') }}
                <select @change="go({ section: $event.target.value })" class="{{ $inputClass }}">
                    @foreach ($options as $option)<option value="{{ $option['key'] }}" @selected($option['section_id'] === $section->id)>{{ $option['label'] }}</option>@endforeach
                </select>
            </label>
            <label class="grid gap-1 text-[14px] font-bold">{{ __('teacher-panel::messages.diary.subject') }}
                <select @change="go({ subject: $event.target.value })" class="{{ $inputClass }}">
                    @php($current = $options->firstWhere('section_id', $section->id))
                    @foreach ($current['subjects'] as $row)<option value="{{ $row['id'] }}" @selected($subject?->id === $row['id'])>{{ $row['name'] }}</option>@endforeach
                    @if ($current['class_teacher'])<option value="general" @selected($isGeneral)>{{ __('teacher-panel::messages.diary.general') }}</option>@endif
                </select>
            </label>
            @if ($dateNote)<span class="col-span-full rounded-xl bg-warn-bg px-3 py-2 text-[14px] font-semibold text-warn">{{ $dateNote }}</span>@endif
        </div>

        @unless ($writable)<p class="rounded-[14px] bg-warn-bg px-3.5 py-3 font-semibold text-warn">{{ __('teacher-panel::messages.diary.locked') }}</p>@endunless
        <p x-show="notice" x-cloak x-text="notice" class="rounded-[14px] bg-warn-bg px-3.5 py-3 text-[14px] font-semibold text-warn"></p>

        <div x-show="loading" class="grid gap-3" aria-hidden="true">
            @for ($i = 0; $i < 4; $i++)<div class="tp-skeleton h-[104px] rounded-[20px]"></div>@endfor
        </div>

        <div x-show="loadError" x-cloak role="alert" class="grid justify-items-center gap-2 rounded-[20px] bg-danger-bg px-5 py-8 text-center">
            <span class="text-[32px]">⚠</span>
            <strong class="text-[18px] text-danger">{{ __('teacher-panel::messages.diary.load_failed') }}</strong>
            <span>{{ __('teacher-panel::messages.diary.load_failed_help') }}</span>
            <button type="button" @click="boot()" class="h-11 cursor-pointer rounded-full bg-danger px-[18px] font-bold text-white">{{ __('teacher-panel::messages.diary.retry') }}</button>
        </div>

        <div x-show="!loading && !loadError" x-cloak class="grid items-start gap-3.5 min-[900px]:grid-cols-[minmax(0,1fr)_340px]">
            <div class="grid min-w-0 gap-3">
                @foreach ($fields as $name => $field)
                    @continue($isGeneral && $name !== 'note')
                    <div class="grid gap-2.5 rounded-[20px] border border-line bg-surface p-3.5">
                        <label for="f-{{ $name }}" class="flex items-center gap-2.5 text-[16px] font-bold"><span class="grid size-8 place-items-center rounded-[10px] text-[15px] {{ $field['tone'] }}">{{ $field['icon'] }}</span>{{ __('teacher-panel::messages.diary.fields.'.$name) }}</label>
                        <textarea id="f-{{ $name }}" rows="3" x-model="form.{{ $name }}" @input="touch()" @disabled(! $writable) placeholder="{{ __('teacher-panel::messages.diary.placeholders.'.$name) }}" class="min-h-[84px] w-full resize-y rounded-xl border border-line-strong bg-surface px-3 py-2.5 leading-[1.6]"></textarea>
                        @if ($name === 'homework')
                            <label class="flex flex-wrap items-center gap-2.5 text-[14px] font-semibold">{{ __('teacher-panel::messages.diary.due') }}
                                <input type="date" x-model="form.homework_due_date" @input="touch()" @disabled(! $writable) class="h-11 rounded-xl border border-line-strong bg-surface px-2.5">
                                <span class="rounded-full bg-yellow-50 px-2.5 py-0.5 text-[13px] font-bold text-warn" x-text="dueLabel"></span>
                            </label>
                        @endif
                        @if ($writable)
                            <div class="tp-scroll flex gap-1.5 overflow-x-auto pb-0.5 min-[768px]:flex-wrap">
                                <template x-for="phrase in phrases['{{ $field['phrases'] }}'] || []" :key="phrase.id">
                                    <button type="button" @click="addPhrase('{{ $name }}', phrase.text)" class="min-h-9 flex-none cursor-pointer whitespace-nowrap rounded-full border px-3 text-[14px] text-ink" :class="phrase.own ? 'border-yellow-400 bg-yellow-50' : 'border-line bg-surface-2'" x-text="(phrase.own ? '★ ' : '') + phrase.text"></button>
                                </template>
                                <button type="button" @click="addFor = addFor === '{{ $name }}' ? '' : '{{ $name }}'; phraseIn = ''" class="min-h-9 flex-none cursor-pointer whitespace-nowrap rounded-full border border-dashed border-green-700 bg-transparent px-3 text-[14px] font-bold text-green-700">＋ {{ __('teacher-panel::messages.diary.own_phrase') }}</button>
                            </div>
                            <div x-show="addFor === '{{ $name }}'" x-cloak class="flex gap-2">
                                <input type="text" x-model="phraseIn" maxlength="255" @keydown.enter.prevent="savePhrase('{{ $field['phrases'] }}')" placeholder="{{ __('teacher-panel::messages.diary.phrase_placeholder') }}" class="h-11 min-w-0 flex-1 rounded-xl border border-line-strong bg-surface px-3">
                                <button type="button" @click="savePhrase('{{ $field['phrases'] }}')" class="h-11 cursor-pointer rounded-xl bg-green-700 px-4 font-bold text-white">{{ __('teacher-panel::messages.diary.save_phrase') }}</button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="grid min-w-0 gap-3">
                <div class="grid gap-2.5 rounded-[20px] border border-line bg-surface p-3.5">
                    <strong class="text-[16px]">📎 {{ __('teacher-panel::messages.diary.attachments') }}</strong>
                    <span class="text-[13px] text-ink-muted">{{ __('teacher-panel::messages.diary.attachments_hint', ['mb' => bn_digits(round($maxKb / 1024))]) }}</span>
                    @if ($writable)
                        <div class="grid grid-cols-2 gap-2">
                            <label class="grid min-h-[52px] cursor-pointer place-items-center rounded-[14px] border border-line-strong bg-surface-2 p-1.5 text-center font-bold"><input type="file" accept="image/*" capture="environment" @change="upload($event)" class="hidden">📷 {{ __('teacher-panel::messages.diary.take_photo') }}</label>
                            <label class="grid min-h-[52px] cursor-pointer place-items-center rounded-[14px] border border-line-strong bg-surface-2 p-1.5 text-center font-bold"><input type="file" accept="image/png,image/jpeg,image/webp,application/pdf" @change="upload($event)" class="hidden">📄 {{ __('teacher-panel::messages.diary.pdf_file') }}</label>
                        </div>
                    @endif
                    <div x-show="entry && entry.attachments.length" x-cloak class="grid grid-cols-[repeat(auto-fill,minmax(96px,1fr))] gap-2">
                        <template x-for="file in entry ? entry.attachments : []" :key="file.id">
                            <div class="relative grid aspect-square place-items-center overflow-hidden rounded-[14px] border border-line bg-surface-2">
                                <a x-show="file.is_image" :href="file.url" target="_blank" rel="noopener" role="img" :aria-label="file.name" class="absolute inset-0 bg-cover bg-center bg-no-repeat" :style="`background-image: url('${file.url}')`"></a>
                                <a x-show="!file.is_image" :href="file.url" target="_blank" rel="noopener" class="grid justify-items-center gap-0.5 p-1.5 text-center no-underline"><strong class="text-[15px] text-danger">PDF</strong><span class="break-all text-[11px] leading-[1.3] text-ink-muted" x-text="file.name"></span></a>
                                @if ($writable)<button type="button" @click="removeFile(file)" aria-label="{{ __('teacher-panel::messages.diary.remove') }}" class="absolute right-1 top-1 size-7 min-h-0 cursor-pointer rounded-full bg-[rgba(23,33,43,.75)] text-[14px] leading-none text-white">✕</button>@endif
                            </div>
                        </template>
                    </div>
                </div>

                @if ($writable && $others->isNotEmpty())
                    <div class="grid gap-2.5 rounded-[20px] border border-line bg-surface p-3.5">
                        <strong class="text-[16px]">⇉ {{ __('teacher-panel::messages.diary.also_title') }}</strong>
                        <span class="text-[13px] text-ink-muted">{{ __('teacher-panel::messages.diary.also_hint') }}</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($others as $other)
                                <button type="button" @click="toggleAlso({{ $other['section_id'] }})" class="min-h-11 cursor-pointer rounded-full border px-3.5 text-[14px] font-bold" :class="also.includes({{ $other['section_id'] }}) ? 'border-green-700 bg-green-700 text-white' : 'border-line-strong bg-surface text-ink'"><span x-text="also.includes({{ $other['section_id'] }}) ? '✓' : '+'"></span> {{ $other['label'] }}</button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div x-show="error" x-cloak role="alert" class="rounded-[14px] bg-danger-bg px-3.5 py-3 font-semibold text-danger" x-text="error"></div>
                <span class="text-[13px] leading-[1.55] text-ink-muted">{{ __('teacher-panel::messages.diary.edit_until', ['hours' => bn_digits($editHours)]) }}</span>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('diary', { publishLabel: @js(__('teacher-panel::messages.diary.publish')), busy: false });
            Alpine.data('diaryEditor', (cfg) => ({
                cfg, entry: null, form: { lesson: '', next_lesson: '', homework: '', note: '', homework_due_date: cfg.nextSchoolDay }, dirty: false, busy: false, loading: true, loadError: false, savedAt: null, error: '', notice: '', leaving: false,
                phrases: {}, students: cfg.students, addFor: '', phraseIn: '', also: [],
                get key() { return ['diary', cfg.class_id, cfg.section_id, cfg.subject_id ?? 'general', cfg.date].join(':'); },
                get statusText() { return this.busy ? cfg.text.saving : (this.dirty ? cfg.text.unsaved : (this.savedAt ? cfg.text.savedAt.replace(':time', this.savedAt) : cfg.text.idle)); },
                get statusClass() { return this.busy || this.dirty ? 'bg-warn-bg text-warn' : (this.savedAt ? 'bg-success-bg text-success' : 'bg-surface-2 text-ink-muted'); },
                get dueLabel() {
                    const due = this.form.homework_due_date;
                    return !due || due === cfg.nextSchoolDay ? cfg.text.nextDay : new Date(due + 'T00:00:00').toLocaleDateString(cfg.locale, { weekday: 'long' });
                },
                clock(value) { return new Date(value).toLocaleTimeString(cfg.locale, { hour: 'numeric', minute: '2-digit' }); },
                headers(json = true) { return { Accept: 'application/json', 'X-CSRF-TOKEN': cfg.csrf, ...(json ? { 'Content-Type': 'application/json' } : {}) }; },
                async call(url, options = {}) {
                    const response = await fetch(url, { credentials: 'same-origin', ...options, headers: { ...this.headers(!(options.body instanceof FormData)), ...(options.headers || {}) } });
                    const data = response.status === 204 ? null : await response.json().catch(() => ({}));
                    if (!response.ok) { const first = data && data.errors ? Object.values(data.errors)[0][0] : (data && data.message) || 'Error'; throw new Error(first); }
                    return data;
                },
                syncStore() { Alpine.store('diary').busy = this.busy; Alpine.store('diary').publishLabel = this.also.length ? cfg.text.publishMany.replace(':count', this.bn(this.also.length + 1)) : (this.entry && this.entry.status === 'published' ? cfg.text.update : cfg.text.publish); },
                bn(number) { return cfg.locale === 'bn-BD' ? String(number).replace(/[0-9]/g, (digit) => '০১২৩৪৫৬৭৮৯'[digit]) : String(number); },
                async boot() {
                    this.loading = true; this.loadError = false;
                    try { this.phrases = (await this.call(cfg.urls.phrases)).data; } catch (e) {}
                    try {
                        const query = new URLSearchParams({ class_id: cfg.class_id, section_id: cfg.section_id, date: cfg.date, ...(cfg.subject_id ? { subject_id: cfg.subject_id } : {}) });
                        const data = (await this.call(cfg.urls.show + '?' + query)).data;
                        if (data) { this.apply(data); }
                    } catch (e) { this.loadError = true; this.loading = false; this.error = e.message; return; }
                    this.restoreBackup();
                    this.loading = false; this.syncStore();
                    if (!this.started) {
                        this.started = true;
                        if (cfg.writable) { setInterval(() => { if (this.dirty && !this.busy) { this.save(false, true); } }, 20000); }
                        window.addEventListener('beforeunload', (event) => { if (this.dirty && !this.leaving) { event.preventDefault(); event.returnValue = ''; } });
                    }
                },
                apply(data) {
                    this.entry = data;
                    this.form = { lesson: data.lesson || '', next_lesson: data.next_lesson || '', homework: data.homework || '', note: data.note || '', homework_due_date: data.homework_due_date || cfg.nextSchoolDay };
                    this.savedAt = data.saved_at ? this.clock(data.saved_at) : null;
                    this.syncStore();
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
                touch() { this.dirty = true; this.error = ''; this.backup(); },
                addPhrase(field, text) { const current = this.form[field]; this.form[field] = current ? current.replace(/\s+$/, '') + (/[।.,]$/.test(current.trim()) ? ' ' : '। ') + text : text; this.touch(); },
                toggleAlso(sectionId) { this.also = this.also.includes(sectionId) ? this.also.filter((id) => id !== sectionId) : [...this.also, sectionId]; this.syncStore(); },
                payload(target = {}) { return { class_id: cfg.class_id, section_id: cfg.section_id, subject_id: cfg.subject_id, entry_date: cfg.date, ...this.form, homework_due_date: this.form.homework && this.form.homework_due_date ? this.form.homework_due_date : null, ...target }; },
                async save(publish = false, silent = false) {
                    if (this.busy || !cfg.writable) { return; }
                    if (!silent && !Object.entries(this.form).some(([name, value]) => name !== 'homework_due_date' && String(value).trim())) { this.error = cfg.text.empty; return; }
                    this.busy = true; this.syncStore(); if (!silent) { this.error = ''; }
                    try {
                        const data = (await this.call(cfg.urls.save, { method: 'PUT', body: JSON.stringify({ ...this.payload(), publish }) })).data;
                        this.apply(data); this.dirty = false; this.clearBackup(); this.notice = '';
                        if (silent) { return; }
                        let message = publish ? cfg.text.publishedToast : cfg.text.draftSaved;
                        if (publish && this.also.length) { message = await this.publishAlso(); }
                        this.leave(message);
                    } catch (e) {
                        this.error = navigator.onLine ? e.message : cfg.text.offline;
                    } finally { this.busy = false; this.syncStore(); }
                },
                async publishAlso() {
                    let done = 1;
                    for (const sectionId of this.also) {
                        const target = cfg.others.find((row) => row.section_id === sectionId);
                        try { await this.call(cfg.urls.save, { method: 'PUT', body: JSON.stringify({ ...this.payload({ class_id: target.class_id, section_id: target.section_id }), publish: true }) }); done++; }
                        catch (e) { this.error = target.label + ': ' + e.message; }
                    }
                    return cfg.text.publishedMany.replace(':count', this.bn(done));
                },
                leave(message) { this.leaving = true; try { sessionStorage.setItem('tp_toast', message); } catch (e) {} window.location.href = cfg.home; },
                async go(patch) {
                    const options = cfg.options;
                    let section = options.find((row) => row.section_id === cfg.section_id);
                    let subject = cfg.subject_id ? String(cfg.subject_id) : 'general';
                    if (patch.section) {
                        section = options.find((row) => row.key === patch.section);
                        subject = section.subjects.length ? String(section.subjects[0].id) : 'general';
                    }
                    if (patch.subject) { subject = patch.subject; }
                    if (this.dirty && cfg.writable) { await this.save(false, true); }
                    this.leaving = true;
                    const query = new URLSearchParams({ class_id: section.class_id, section_id: section.section_id, date: patch.date || cfg.date, ...(subject !== 'general' ? { subject_id: subject } : {}) });
                    window.location.href = cfg.urls.write + '?' + query;
                },
                async copyPrevious() {
                    try {
                        const query = new URLSearchParams({ class_id: cfg.class_id, section_id: cfg.section_id, date: cfg.date, ...(cfg.subject_id ? { subject_id: cfg.subject_id } : {}) });
                        const previous = (await this.call(cfg.urls.previous + '?' + query)).data;
                        if (!previous) { window.dispatchEvent(new CustomEvent('tp-toast', { detail: cfg.text.noPrevious })); return; }
                        this.form = { ...this.form, lesson: previous.lesson || '', next_lesson: previous.next_lesson || '', homework: previous.homework || '', note: previous.note || '' };
                        this.touch();
                        window.dispatchEvent(new CustomEvent('tp-toast', { detail: cfg.text.copied.replace(':date', previous.label) }));
                    } catch (e) { this.error = e.message; }
                },
                async savePhrase(field) {
                    const text = this.phraseIn.trim();
                    if (!text) { return; }
                    try {
                        const data = (await this.call(cfg.urls.addPhrase, { method: 'POST', body: JSON.stringify({ field, text_bn: text }) })).data;
                        this.phrases[field] = [...(this.phrases[field] || []), data]; this.phraseIn = ''; this.addFor = '';
                        window.dispatchEvent(new CustomEvent('tp-toast', { detail: cfg.text.phraseSaved }));
                    } catch (e) { this.error = e.message; }
                },
                async ensureEntry() { if (!this.entry) { await this.save(false, true); } return this.entry; },
                async upload(event) {
                    const file = event.target.files[0]; event.target.value = '';
                    if (!file) { return; }
                    if (file.size > cfg.maxBytes) { window.dispatchEvent(new CustomEvent('tp-toast', { detail: cfg.text.tooBig })); return; }
                    if (!(await this.ensureEntry())) { this.error = cfg.text.saveFirst; return; }
                    const form = new FormData(); form.append('file', file);
                    try { const data = await this.call(cfg.urls.entries + '/' + this.entry.id + '/attachments', { method: 'POST', body: form }); this.entry.attachments.push(data.data); } catch (e) { this.error = e.message; }
                },
                async removeFile(file) { try { await this.call(cfg.urls.attachments + '/' + file.id, { method: 'DELETE' }); this.entry.attachments = this.entry.attachments.filter((item) => item.id !== file.id); } catch (e) { this.error = e.message; } },
            }));
        });
    </script>
@endsection

@if ($writable)
    @section('action_bar')
        <div x-data class="grid grid-cols-[1fr_1.4fr] gap-2.5 border-t border-line bg-surface px-4 py-2.5 shadow-[0_-6px_20px_rgba(0,0,0,.06)]">
            <button type="button" :disabled="$store.diary.busy" @click="$dispatch('tp-save', { publish: false })" class="h-[52px] cursor-pointer rounded-full border-2 border-green-700 bg-surface text-[16px] font-bold text-green-700">{{ __('teacher-panel::messages.diary.save_draft') }}</button>
            <button type="button" :disabled="$store.diary.busy" @click="$dispatch('tp-save', { publish: true })" class="h-[52px] cursor-pointer rounded-full bg-green-700 text-[16px] font-bold text-white" x-text="$store.diary.publishLabel">{{ __('teacher-panel::messages.diary.publish') }}</button>
        </div>
    @endsection
@endif
