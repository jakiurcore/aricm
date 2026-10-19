{{-- The "payment result" card, shown once after a payment was submitted or an online checkout came back. --}}
@php
    $state = $result['state'];
    $payment = $result['payment'];
    $tone = ['approved' => ['pp-ok', 'var(--success-bg)', 'var(--success)', '✓'], 'pending' => ['pp-warn', 'var(--warn-bg)', 'var(--warn)', '⏳'], 'failed' => ['pp-bad', 'var(--danger-bg)', 'var(--danger)', '✕']][$state];
    $rows = $payment ? array_filter([
        [__('student-portal::messages.fees_page.amount_label'), $payment->amount->format()],
        [__('student-portal::messages.fees_page.method'), $payment->method->label()],
        $payment->transaction_id ? [__('student-portal::messages.fees_page.transaction_id'), $payment->transaction_id] : null,
        $payment->receipt_no ? [__('student-portal::messages.fees_page.reference'), $payment->receipt_no] : null,
    ]) : [];
@endphp
<div data-screen-label="Payment result" style="padding: 28px 20px; border-radius: 24px; background: {{ $tone[1] }}; border: 2px solid {{ $tone[2] }}; display: grid; gap: 10px; justify-items: center; text-align: center;">
    <span style="width: 72px; height: 72px; border-radius: 50%; display: grid; place-items: center; background: {{ $tone[2] }}; color: #fff; font-size: 34px;">{{ $tone[3] }}</span>
    <strong style="font-size: 21px; color: {{ $tone[2] }}; line-height: 1.45;">{{ __('student-portal::messages.fees_page.result_'.$state.'_title') }}</strong>
    <span style="max-width: 440px; line-height: 1.6;">{{ __('student-portal::messages.fees_page.result_'.$state.'_body') }}</span>
    @if ($rows)
        <div style="display: grid; gap: 4px; padding: 12px 16px; border-radius: 16px; background: var(--surface); min-width: min(100%, 300px); text-align: left;">
            @foreach ($rows as [$label, $value])
                <span style="display: flex; justify-content: space-between; gap: 12px; font-size: 15px;"><span style="color: var(--ds-muted);">{{ $label }}</span><strong style="text-align: right;">{{ $value }}</strong></span>
            @endforeach
        </div>
    @endif
    <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
        @if ($state === 'approved' && $payment && $payment->receipt_no)
            <a href="{{ route('portal.fees.receipt', $payment) }}" target="_blank" class="pp-btn-green" style="min-height: 48px; padding: 0 20px; border-radius: 999px; font-size: 16px; display: inline-flex; align-items: center; text-decoration: none; font-weight: 700; color: #fff;">🧾 {{ __('student-portal::messages.fees_page.view_receipt') }}</a>
        @elseif ($state === 'pending')
            <span class="pp-btn" style="cursor: default; color: var(--ds-muted);">🧾 {{ __('student-portal::messages.fees_page.receipt_after_check') }}</span>
        @endif
        <a href="{{ route('portal.fees') }}" class="pp-btn">{{ __('student-portal::messages.fees_page.back_to_fees') }}</a>
    </div>
</div>
