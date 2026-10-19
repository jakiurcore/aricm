@extends('student-portal::layouts.portal')

@section('title', __('diary::messages.portal.title'))

@section('content')
    @php
        use Modules\StudentPortal\Support\PortalFormat;
        use Modules\Core\Support\Bangla;

        $today = now()->startOfDay();
        $dueChip = function ($due) use ($today, $nextSchoolDay): array {
            $tomorrow = app(\Modules\Academic\Services\SchoolCalendar::class)->nextSchoolDay($today);

            return match (true) {
                $due->isSameDay($today) => ['label' => __('diary::messages.portal.today'), 'class' => 'pp-bad'],
                $due->isSameDay($tomorrow) => ['label' => __('diary::messages.portal.tomorrow').' · '.Bangla::weekday($due), 'class' => 'pp-warn'],
                default => ['label' => PortalFormat::dayMonth($due), 'class' => 'pp-mute'],
            };
        };
    @endphp

    <div data-screen-label="Portal diary" x-data="{ viewer: null }" style="display: grid; gap: 14px;">
        <div class="pp-card" style="padding: 12px; display: grid; gap: 10px;">
            <div style="display: flex; gap: 8px; align-items: center; justify-content: space-between; flex-wrap: wrap;">
                <strong style="font-size: 16px;">{{ Bangla::monthName($weekStart) }} {{ bn_digits($date->year) }}</strong>
                <span style="display: flex; gap: 6px; align-items: center;">
                    <a href="{{ route('portal.diary', ['date' => $previousWeek->toDateString()]) }}" aria-label="{{ __('diary::messages.portal.week_prev') }}" style="width: 44px; height: 44px; border-radius: 999px; border: 1px solid var(--line); background: var(--surface); display: grid; place-items: center; text-decoration: none; color: var(--ink);">‹</a>
                    <a href="{{ route('portal.diary', ['date' => $nextWeek->toDateString()]) }}" aria-label="{{ __('diary::messages.portal.week_next') }}" style="width: 44px; height: 44px; border-radius: 999px; border: 1px solid var(--line); background: var(--surface); display: grid; place-items: center; text-decoration: none; color: var(--ink);">›</a>
                    <label aria-label="{{ __('diary::messages.portal.calendar') }}" style="position: relative; height: 44px; padding: 0 12px; border-radius: 999px; border: 1px solid var(--line-strong); background: var(--surface-2); display: flex; align-items: center; gap: 6px; font-weight: 600; font-size: 14px; cursor: pointer;">🗓 {{ __('diary::messages.portal.calendar') }}<input type="date" value="{{ $date->toDateString() }}" onchange="if (this.value) { window.location = '{{ route('portal.diary') }}?date=' + this.value; }" style="position: absolute; inset: 0; opacity: 0; cursor: pointer; min-height: 0; width: 100%;"></label>
                </span>
            </div>
            <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 4px;">
                @foreach ($week as $day)
                    @php
                        $selected = $day['date']->isSameDay($date);
                        $isToday = $day['date']->isSameDay($today);
                        $off = ! $day['is_school_day'];
                    @endphp
                    <a href="{{ route('portal.diary', ['date' => $day['date']->toDateString()]) }}" @if ($selected) aria-current="date" @endif style="min-height: 68px; padding: 6px 0; border-radius: 14px; border: 2px solid {{ $isToday ? 'var(--yellow-400)' : ($selected ? 'var(--green-700)' : 'var(--line)') }}; background: {{ $selected ? 'var(--green-700)' : ($off ? 'var(--surface-2)' : 'var(--surface)') }}; color: {{ $selected ? '#fff' : ($off ? 'var(--faint)' : 'var(--ink)') }}; display: grid; justify-items: center; gap: 0; line-height: 1.3; text-decoration: none;">
                        <span style="font-size: 12px; font-weight: 600;">{{ $off ? __('diary::messages.portal.holiday_short') : PortalFormat::weekdayShort($day['date']) }}</span>
                        <strong class="pp-head" style="font-size: 18px;">{{ bn_digits($day['date']->day) }}</strong>
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: {{ $day['entries'] > 0 ? ($selected ? '#FFD21F' : 'var(--red-600)') : 'transparent' }};"></span>
                    </a>
                @endforeach
            </div>
        </div>
        <strong class="pp-head" style="color: var(--heading); font-size: 19px;">{{ Bangla::weekday($date) }}, {{ Bangla::date($date) }}</strong>

        @if ($locked)
            <div style="padding: 28px 20px; border-radius: 20px; border: 2px dashed var(--line-strong); text-align: center; display: grid; gap: 6px;"><span style="font-size: 30px;">{{ $locked === 'future' ? '🔒' : '🌙' }}</span><strong style="font-size: 17px;">{{ $locked === 'future' ? __('diary::messages.portal.future') : __('diary::messages.portal.holiday') }}</strong></div>
        @else
            <div style="display: grid; gap: 14px;">
                @if ($preparation->isNotEmpty())
                    <div style="padding: 18px; border-radius: 22px; background: linear-gradient(135deg, var(--navy-800), #0F2257); color: #fff; display: grid; gap: 10px;">
                        <span style="display: grid; line-height: 1.45;"><span style="font-size: 13px; font-weight: 700; color: #FFD21F;">{{ __('diary::messages.portal.prep_for') }}</span><strong style="font-size: 18px;">{{ Bangla::weekday($nextSchoolDay) }}, {{ Bangla::date($nextSchoolDay) }}</strong></span>
                        @foreach ($preparation as $item)
                            <div style="display: grid; grid-template-columns: 26px 1fr; gap: 8px; align-items: start; padding: 8px 10px; border-radius: 12px; background: rgba(255,255,255,.08);">
                                <span style="width: 26px; height: 26px; border-radius: 8px; display: grid; place-items: center; font-size: 13px; background: {{ $item['kind'] === 'note' ? '#FFD21F' : '#FFF4CC' }}; color: #17212B; font-weight: 700;">{{ $item['kind'] === 'note' ? '!' : '✎' }}</span>
                                <span style="line-height: 1.5; font-size: 15px;"><strong>{{ $item['subject'] }}:</strong> {{ $item['text'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                @foreach ($remarks as $item)
                    @php
                        $kind = $item['remark']->kind->value;
                        $tone = $kind === 'praise' ? ['var(--success-bg)', 'var(--success)', '★'] : ($kind === 'info' ? ['var(--info-bg)', 'var(--info)', 'ℹ'] : ['var(--warn-bg)', 'var(--warn)', '!']);
                    @endphp
                    <div style="padding: 14px 16px; border-radius: 18px; background: {{ $tone[0] }}; border: 2px solid {{ $tone[1] }}; display: grid; gap: 4px;">
                        <span style="font-weight: 700; color: {{ $tone[1] }};">{{ $tone[2] }} {{ __('diary::messages.portal.personal_remark') }}: {{ $item['remark']->kind->label() }}</span>
                        @if (filled($item['remark']->text))<span style="line-height: 1.6;">{{ $item['remark']->text }}</span>@endif
                        <span style="font-size: 13px; color: var(--ds-muted);">{{ $item['entry']->subjectName() }} · {{ $item['remark']->teacher?->displayName() }}</span>
                    </div>
                @endforeach

                @if ($entries->isEmpty())
                    @include('student-portal::partials.empty', ['icon' => '📓', 'title' => __('diary::messages.portal.no_entries'), 'body' => __('diary::messages.portal.no_entries_body')])
                @endif

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr)); gap: 12px; align-items: start;">
                    @foreach ($entries as $entry)
                        @php
                            $rows = array_filter([
                                filled($entry->lesson) ? [__('diary::messages.portal.lesson'), $entry->lesson, '📖', 'var(--green-50)', 'var(--green-700)', null] : null,
                                filled($entry->homework) ? [__('diary::messages.portal.homework'), $entry->homework, '✎', 'var(--yellow-50)', 'var(--warn)', $entry->homework_due_date] : null,
                                filled($entry->note) ? [__('diary::messages.portal.note'), $entry->note, '!', 'var(--red-50)', 'var(--danger)', null] : null,
                            ]);
                        @endphp
                        <div class="pp-card pp-card-sm" style="padding: 16px; display: grid; gap: 10px;">
                            <strong class="pp-head" style="font-size: 18px; color: var(--green-700);">{{ $entry->subjectName() }}</strong>
                            @foreach ($rows as [$key, $value, $icon, $iconBg, $iconFg, $due])
                                <div style="display: grid; grid-template-columns: 28px 1fr; gap: 8px; align-items: start;">
                                    <span style="width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center; font-size: 13px; background: {{ $iconBg }}; color: {{ $iconFg }};">{{ $icon }}</span>
                                    <span style="display: grid; gap: 2px;"><span style="font-size: 13px; font-weight: 700; color: var(--ds-muted);">{{ $key }}</span><span style="line-height: 1.6; white-space: pre-line;">{{ $value }}</span>@if ($due)<span style="justify-self: start; padding: 1px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; background: var(--yellow-50); color: var(--warn); border: 1px solid var(--yellow-400);">{{ __('diary::messages.portal.due_on') }}: {{ PortalFormat::dayMonth($due) }}</span>@endif</span>
                                </div>
                            @endforeach
                            @if ($entry->attachments->isNotEmpty())
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    @foreach ($entry->attachments as $file)
                                        @if ($file->isImage())
                                            <button type="button" @click="viewer = { url: @js($file->url()), name: @js($file->original_name) }" style="min-height: 44px; padding: 0 12px; border-radius: 12px; border: 1px solid var(--line-strong); background: var(--surface-2); font-size: 14px; font-weight: 600;">🖼 {{ $file->original_name }}</button>
                                        @else
                                            <a href="{{ $file->url() }}" target="_blank" rel="noopener" style="min-height: 44px; padding: 0 12px; border-radius: 12px; border: 1px solid var(--line-strong); background: var(--surface-2); font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; text-decoration: none; color: var(--ink);">📄 {{ $file->original_name }}</a>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                            <span style="font-size: 13px; color: var(--ds-muted); border-top: 1px solid var(--line); padding-top: 8px;">👤 {{ $entry->teacher?->displayName() }}@if ($entry->published_at) · {{ PortalFormat::time($entry->published_at) }}@endif</span>
                        </div>
                    @endforeach
                </div>

                @if ($canAcknowledge)
                    @if ($allAcknowledged)
                        <div style="min-height: 60px; border-radius: 999px; border: 2px solid var(--success); background: var(--success-bg); color: var(--success); font-weight: 700; font-size: 18px; display: grid; place-items: center; padding: 0 16px; text-align: center;">✓ {{ __('diary::messages.portal.acknowledged') }}@if ($acknowledgedAt), {{ PortalFormat::time($acknowledgedAt) }}@endif</div>
                    @else
                        <form method="POST" action="{{ route('portal.diary.acknowledge-day') }}" style="display: grid;">@csrf<input type="hidden" name="date" value="{{ $date->toDateString() }}"><button type="submit" style="min-height: 60px; border-radius: 999px; border: 0; background: var(--green-700); color: #fff; font-weight: 700; font-size: 18px;">{{ __('diary::messages.portal.acknowledge_btn') }}</button></form>
                    @endif
                @endif
            </div>
        @endif

        <div class="pp-card" style="overflow: hidden;">
            <h3 class="pp-section-head" style="background: var(--yellow-50); color: var(--warn);">✎ {{ __('diary::messages.portal.pending_homework') }} ({{ bn_digits($pending->count()) }})</h3>
            @foreach ($pending as $entry)
                @php($chip = $dueChip($entry->homework_due_date))
                <div style="display: flex; gap: 12px; align-items: center; padding: 12px 18px; border-top: 1px solid var(--line); flex-wrap: wrap;">
                    <span style="flex: 1; min-width: 200px; display: grid; line-height: 1.5;"><strong>{{ $entry->subjectName() }}</strong><span style="color: var(--ds-muted); font-size: 14px;">{{ $entry->homework }}</span></span>
                    <span class="pp-pill {{ $chip['class'] }}" style="padding: 2px 12px;">{{ __('diary::messages.portal.due_on') }}: {{ $chip['label'] }}</span>
                </div>
            @endforeach
            @if ($pending->isEmpty())<div style="padding: 14px 18px; color: var(--success); font-weight: 600;">✓ {{ __('diary::messages.portal.no_pending') }}</div>@endif
        </div>

        <div x-show="viewer" x-cloak x-transition.opacity @click="viewer = null" @keydown.escape.window="viewer = null" style="position: fixed; inset: 0; z-index: 90; background: rgba(10,20,15,.8); display: grid; place-items: center; padding: 20px;">
            <div style="width: 100%; max-width: 560px; display: grid; gap: 10px; justify-items: center; color: #fff; text-align: center;">
                <div style="width: 100%; aspect-ratio: 3 / 4; max-height: 70vh; border-radius: 16px; overflow: hidden; background: #fff;"><img :src="viewer?.url" alt="" style="width: 100%; height: 100%; object-fit: contain;"></div>
                <strong x-text="viewer?.name"></strong>
                <button type="button" @click="viewer = null" style="height: 44px; padding: 0 20px; border-radius: 999px; border: 0; background: #fff; color: #17212B; font-weight: 700;">✕ {{ __('student-portal::messages.close') }}</button>
            </div>
        </div>
    </div>
@endsection
