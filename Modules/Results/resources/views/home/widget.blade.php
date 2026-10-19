@if ($exams->isNotEmpty())
    <div class="pub-wrap" style="margin-top: 64px;">
        <section id="result" style="border-radius: 24px; background: var(--surface); border: 1px solid var(--line); box-shadow: var(--shadow-md); padding: clamp(20px, 3vw, 32px); display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap: 24px; align-items: center;">
            <div style="display: grid; gap: 6px;">
                <span class="pub-kicker">{{ __('results::messages.public.kicker') }}</span>
                <h2 style="margin: 0; font-family: var(--f-head); color: var(--heading); font-size: clamp(26px, 3.5vw, 34px); line-height: 1.3;">{{ __('results::messages.public.quick_title') }}</h2>
                <span style="color: var(--muted);">{{ __('results::messages.public.hint') }}</span>
            </div>
            <form method="GET" action="{{ route('results.public') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 160px), 1fr)); gap: 10px; align-items: end; grid-column: span 2;">
                <label class="pub-field" for="home_exam_id">{{ __('results::messages.public.exam') }}
                    <select id="home_exam_id" name="exam_id" class="pub-input" style="font-weight: 500;">
                        @foreach ($exams as $exam)<option value="{{ $exam->id }}">{{ $exam->displayName() }}</option>@endforeach
                    </select>
                </label>
                <label class="pub-field" for="home_identifier">{{ __('results::messages.public.roll') }}
                    <input id="home_identifier" name="identifier" inputmode="numeric" placeholder="{{ __('results::messages.public.identifier_placeholder') }}" class="pub-input">
                </label>
                <button type="submit" style="height: 48px; border: 0; border-radius: 12px; background: var(--green-700); color: #fff; font-weight: 700; cursor: pointer;">{{ __('results::messages.public.search') }}</button>
            </form>
        </section>
    </div>
@endif
