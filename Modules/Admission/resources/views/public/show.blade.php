@extends('core::layouts.public')

@section('title', __('admission::messages.title'))

@section('content')
    @php
        $start = $settings->get('admission_start');
        $classStart = $settings->get('class_start');
        $sessionYear = (int) \Illuminate\Support\Carbon::parse($start ?: now())->format('Y');
        $session = bn_digits($sessionYear).'–'.bn_digits(str_pad((string) (($sessionYear + 1) % 100), 2, '0', STR_PAD_LEFT));
    @endphp
    <div style="max-width: 1140px; margin: 0 auto; padding: 40px 16px 0; display: grid; gap: 32px;">
        <div style="display: grid; gap: 10px;">
            <span style="justify-self: start; padding: 4px 14px; border-radius: 999px; background: var(--red-600); color: #fff; font-weight: 700; font-size: 14px;">{{ ($settings->bannerEnabled() ? $settings->text('banner_title') : '') ?: __('core::nav.admission_open') }}</span>
            <h1 class="pub-h1">{{ site_content()->text('admission.page_title', ['session' => $session]) }}</h1>
            <p style="margin: 0; font-size: 18px; color: var(--muted); max-width: 66ch;">{{ site_content()->text('admission.intro', ['start' => $start ? bn_date($start) : '—', 'class_start' => $classStart ? bn_date($classStart) : '—']) }}</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap: 20px; align-items: start;">
            <div style="display: grid; gap: 20px;">
                <div class="pub-card" style="display: grid; gap: 12px;">
                    <h2 style="margin: 0; font-family: var(--f-head); color: var(--heading); font-size: 22px;">{{ site_content()->text('admission.docs_title') }}</h2>
                    @foreach (site_content()->items('admission_docs') as $doc)
                        <div style="display: grid; grid-template-columns: 28px 1fr; gap: 10px; align-items: start;"><x-core::icon name="check" size="16" badge /><span>{{ $doc->title() }}</span></div>
                    @endforeach
                    @if ($settings->text('content'))
                        <p style="margin: 6px 0 0; padding-top: 12px; border-top: 1px solid var(--line); color: var(--muted); font-size: 15px; white-space: pre-line;">{{ $settings->text('content') }}</p>
                    @endif
                </div>
                @if (site_content()->priceRows()->isNotEmpty())
                <div style="border-radius: 20px; background: var(--surface); border: 1px solid var(--line); box-shadow: var(--shadow-sm); overflow: hidden;">
                    <h2 style="margin: 0; padding: 18px 22px 10px; font-family: var(--f-head); color: var(--heading); font-size: 22px;">{{ site_content()->text('admission.fees_title') }}</h2>
                    @php
                        $priceColumns = site_content()->priceColumns();
                        $priceTemplate = 'grid-template-columns: 1.4fr'.str_repeat(' 1fr', max($priceColumns->count(), 1)).';';
                    @endphp
                    <div style="display: grid; {{ $priceTemplate }} padding: 10px 22px; background: var(--surface-2); font-size: 14px; font-weight: 700; color: var(--muted);"><span>{{ site_content()->text('admission.fee_item') }}</span>@foreach ($priceColumns as $column)<span>{{ $column->title() }}</span>@endforeach</div>
                    @foreach (site_content()->priceRows() as $row)
                        <div style="display: grid; {{ $priceTemplate }} padding: 12px 22px; border-top: 1px solid var(--line);"><span>{{ $row->label() }}@if ($row->note())<small style="display: block; color: var(--muted);">{{ $row->note() }}</small>@endif</span>@foreach ($priceColumns as $key => $column)<span style="font-weight: 600;">@if ($row->price($key) !== null)৳ {{ bn_number($row->price($key)) }}@else—@endif</span>@endforeach</div>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="pub-card" style="box-shadow: var(--shadow-md); display: grid; gap: 14px;">
                <h2 style="margin: 0; font-family: var(--f-head); color: var(--heading); font-size: 22px;">{{ site_content()->text('admission.inquiry_title') }}</h2>

                @if (session('inquiry_sent'))
                    <div role="status" style="padding: 28px 16px; border-radius: 16px; background: var(--success-bg); color: var(--success); text-align: center; display: grid; gap: 6px;">
                        <x-core::icon name="check" size="32" badge ring style="margin: 4px;" /><strong style="font-size: 18px;">{{ site_content()->text('admission.sent_title') }}</strong><span style="color: var(--ink);">{{ site_content()->text('admission.sent_body') }}</span>
                        <a href="{{ route('admission.public') }}" style="justify-self: center; margin-top: 6px; height: 44px; padding: 0 18px; border-radius: 999px; border: 2px solid var(--green-700); background: transparent; color: var(--green-700); font-weight: 700; display: inline-flex; align-items: center; text-decoration: none;">{{ __('admission::messages.send_another') }}</a>
                    </div>
                @else
                    <form method="POST" action="{{ route('admission.inquiry.store') }}" style="display: grid; gap: 12px;">
                        @csrf
                        <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                        <label class="pub-field" for="student_name">{{ __('admission::messages.student_name') }} *
                            <input id="student_name" name="student_name" type="text" value="{{ old('student_name') }}" required class="pub-input" @error('student_name') aria-invalid="true" @enderror>
                            @error('student_name')<span class="pub-err">{{ $message }}</span>@enderror
                        </label>
                        <label class="pub-field" for="guardian_name">{{ __('admission::messages.guardian_name') }}
                            <input id="guardian_name" name="guardian_name" type="text" value="{{ old('guardian_name') }}" class="pub-input">
                            @error('guardian_name')<span class="pub-err">{{ $message }}</span>@enderror
                        </label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 160px), 1fr)); gap: 12px;">
                            <label class="pub-field" for="phone">{{ __('admission::messages.phone') }} *
                                <input id="phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" placeholder="{{ __('admission::messages.phone_placeholder') }}" required class="pub-input" @error('phone') aria-invalid="true" @enderror>
                                @error('phone')<span class="pub-err">{{ $message }}</span>@enderror
                            </label>
                            <label class="pub-field" for="desired_class">{{ __('admission::messages.desired_class') }}
                                <select id="desired_class" name="desired_class" class="pub-input">
                                    @foreach ($settings->classes() as $class)<option value="{{ $class }}" @selected(old('desired_class') === $class)>{{ $class }}</option>@endforeach
                                </select>
                                @error('desired_class')<span class="pub-err">{{ $message }}</span>@enderror
                            </label>
                        </div>
                        <label class="pub-field" for="message">{{ __('admission::messages.message') }}
                            <textarea id="message" name="message" rows="3" class="pub-input" style="height: auto; padding: 10px 12px; resize: vertical;">{{ old('message') }}</textarea>
                        </label>
                        <button type="submit" style="height: 52px; border: 0; border-radius: 999px; background: var(--red-600); color: #fff; font-weight: 700; font-size: 17px; cursor: pointer;">{{ __('admission::messages.send') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
