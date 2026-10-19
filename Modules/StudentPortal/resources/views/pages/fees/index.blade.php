@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.nav.fees'))

@section('content')
    @php
        $hasDue = $due->isPositive();
        $thisStatus = $thisMonth ? \Modules\StudentPortal\Support\FeeDisplay::status($thisMonth['status']) : null;
    @endphp

    <div data-screen-label="Portal fees" style="display: grid; gap: 14px;">
        @include('student-portal::partials.fee-tabs', ['active' => 'overview'])

        @if ($result)
            @include('student-portal::partials.fee-result', ['result' => $result])
        @endif

        <div data-screen-label="Fee overview" style="display: grid; gap: 14px;">
            <div style="padding: 20px; border-radius: 24px; background: var(--surface); border: 2px solid {{ $hasDue ? 'var(--red-600)' : 'var(--green-500)' }}; box-shadow: var(--shadow-md); display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr)); gap: 16px; align-items: center;">
                <div style="display: grid; gap: 4px;">
                    <span style="font-size: 15px; font-weight: 700; color: var(--ds-muted);">{{ __('student-portal::messages.fees_page.total_due') }}</span>
                    <strong class="pp-head" style="font-size: 42px; line-height: 1.2; color: {{ $hasDue ? 'var(--danger)' : 'var(--success)' }};">{{ $due->format() }}</strong>
                    <span style="font-size: 14px; color: var(--ds-muted);">{{ $hasDue ? $due->inWords() : __('student-portal::messages.fees_page.all_paid') }}</span>
                </div>
                <div style="display: grid; gap: 8px;">
                    @if ($thisStatus)
                        <span style="display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; font-size: 15px;"><span style="color: var(--ds-muted);">{{ __('student-portal::messages.fees_page.this_month', ['month' => \Modules\Core\Support\Bangla::monthName($thisMonth['month'])]) }}</span><span class="pp-pill {{ $thisStatus['class'] }}">{{ $thisStatus['label'] }}</span></span>
                    @endif
                    <span style="display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; font-size: 15px;"><span style="color: var(--ds-muted);">{{ __('student-portal::messages.fees_page.last_payment') }}</span><strong>{{ $lastPayment ? bn_date($lastPayment->paid_at ?? $lastPayment->created_at).' · '.$lastPayment->amount->format() : '—' }}</strong></span>
                    @if ($pendingAmount->isPositive())
                        <span class="pp-warn" style="padding: 8px 12px; border-radius: 12px; font-weight: 700; font-size: 14px;">⏳ {{ __('student-portal::messages.fees_page.checking') }}: {{ $pendingAmount->format() }}</span>
                    @endif
                </div>
                @if ($hasDue)
                    <a href="{{ route('portal.fees.pay') }}" class="pp-btn-red" style="padding: 0 22px; border-radius: 999px; font-size: 17px; grid-column: 1 / -1; min-height: 56px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-weight: 700; color: #fff;">{{ __('student-portal::messages.fees_page.pay_now') }} →</a>
                @else
                    <div class="pp-ok" style="grid-column: 1 / -1; padding: 16px; border-radius: 16px; text-align: center; display: grid; gap: 2px;">
                        <strong style="font-size: 20px;">{{ __('student-portal::messages.fees_page.no_due') }} 🎉</strong>
                        <span style="color: var(--ink); font-size: 14px;">{{ __('student-portal::messages.fees_page.no_due_body') }}</span>
                        <a href="{{ route('portal.fees.pay') }}" class="pp-btn" style="justify-self: center; margin-top: 6px;">{{ __('student-portal::messages.fees_page.pay_advance') }}</a>
                    </div>
                @endif
            </div>

            <div class="pp-card" style="padding: 16px; display: grid; gap: 12px;">
                <div style="display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; align-items: baseline;">
                    <h3 class="pp-head" style="margin: 0; color: var(--heading); font-size: 18px;">{{ __('student-portal::messages.fees_page.session_title', ['session' => bn_digits($student->session?->name)]) }}</h3>
                    <span style="font-size: 14px; color: var(--ds-muted);">{{ __('student-portal::messages.fees_page.monthly_meta', ['amount' => $monthly->format(), 'day' => bn_digits($dueDay)]) }}</span>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                    @foreach (['paid', 'partial', 'due', 'upcoming', 'waived'] as $key)
                        @php($legend = \Modules\StudentPortal\Support\FeeDisplay::status($key))
                        <span class="pp-pill {{ $legend['class'] }}" style="font-size: 12px;">{{ $legend['glyph'] }} {{ $legend['label'] }}</span>
                    @endforeach
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(98px, 1fr)); gap: 8px;">
                    @foreach ($months as $month)
                        @php($tone = \Modules\StudentPortal\Support\FeeDisplay::status($month['status']))
                        @php($tag = $month['invoice_id'] ? 'a' : 'div')
                        <{{ $tag }} @if ($month['invoice_id']) href="{{ route('portal.fees.invoice', $month['invoice_id']) }}" @endif aria-label="{{ \Modules\Core\Support\Bangla::monthName($month['month']) }}: {{ $tone['label'] }}" style="min-height: 92px; padding: 10px 8px; border-radius: 16px; background: {{ $tone['bg'] }}; border: 2px solid {{ $month['current'] ? 'var(--navy-800)' : 'transparent' }}; color: var(--ink); display: grid; gap: 2px; align-content: center; justify-items: center; text-align: center; line-height: 1.35; text-decoration: none;">
                            <strong style="font-size: 15px;">{{ \Modules\Core\Support\Bangla::monthName($month['month']) }}</strong>
                            <span style="font-size: 13px; font-weight: 700; color: {{ $tone['fg'] }};">{{ $tone['glyph'] }} {{ $tone['label'] }}</span>
                            <span style="font-size: 12px; color: var(--ds-muted);">{{ $month['amount'] }}</span>
                        </{{ $tag }}>
                    @endforeach
                </div>
            </div>

            @if ($others->isNotEmpty())
                <div class="pp-card" style="overflow: hidden;">
                    <h3 class="pp-section-head">{{ __('student-portal::messages.fees_page.other_fees') }}</h3>
                    @foreach ($others as $other)
                        @php($tone = \Modules\StudentPortal\Support\FeeDisplay::status(\Modules\StudentPortal\Support\FeeDisplay::invoiceStatus($other, in_array($other->id, $pendingIds, true))))
                        <a href="{{ route('portal.fees.invoice', $other) }}" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; padding: 12px 18px; border-top: 1px solid var(--line); color: var(--ink); text-decoration: none; min-height: 60px;">
                            <span style="flex: 1; min-width: 180px; display: grid; line-height: 1.45;"><strong style="font-size: 16px;">{{ \Modules\StudentPortal\Support\FeeDisplay::invoiceTitle($other) }}</strong><span style="font-size: 13px; color: var(--ds-muted);">{{ $other->status === \Modules\Fees\Enums\InvoiceStatus::Paid ? __('student-portal::messages.fee_status.paid') : __('student-portal::messages.fees_page.deadline').': '.bn_date($other->due_date) }}</span></span>
                            <strong style="font-size: 16px;">{{ $other->total->format() }}</strong>
                            <span class="pp-pill {{ $tone['class'] }}">{{ $tone['glyph'] }} {{ $tone['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
