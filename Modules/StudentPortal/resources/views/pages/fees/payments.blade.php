@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.nav.fees'))

@section('content')
    <div data-screen-label="Portal fees" style="display: grid; gap: 14px;">
        @include('student-portal::partials.fee-tabs', ['active' => 'history'])

        @if ($result)
            @include('student-portal::partials.fee-result', ['result' => $result])
        @endif

        <div data-screen-label="Payment history" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 360px), 1fr)); gap: 10px;">
            @forelse ($payments as $payment)
                @php
                    $tone = \Modules\StudentPortal\Support\FeeDisplay::status(['approved' => 'approved', 'pending' => 'pending'][$payment->status->value] ?? 'rejected');
                    $wallet = \Modules\StudentPortal\Support\FeeDisplay::wallet($payment->method->value);
                    $what = collect($payment->invoice_ids ?? [])->map(fn ($id) => $invoices->get($id))->filter()->map(fn ($invoice): string => \Modules\StudentPortal\Support\FeeDisplay::invoiceTitle($invoice))->implode(', ');
                @endphp
                <div class="pp-card" style="padding: 14px 16px; display: grid; grid-template-columns: 48px minmax(0, 1fr); gap: 12px;">
                    <span class="pp-en" style="width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; color: #fff; font-weight: 700; font-size: 18px; background: {{ $wallet['color'] }};">{{ $wallet['icon'] }}</span>
                    <div style="display: grid; gap: 4px; min-width: 0;">
                        <span style="display: flex; gap: 8px; justify-content: space-between; flex-wrap: wrap; align-items: center;"><strong style="font-size: 19px;">{{ $payment->amount->format() }}</strong><span class="pp-pill {{ $tone['class'] }}">{{ $tone['glyph'] }} {{ $payment->status->label() }}</span></span>
                        <span style="font-size: 14px; color: var(--ds-muted); line-height: 1.5;">{{ bn_date($payment->paid_at ?? $payment->created_at) }} · {{ $payment->method->label() }}@if ($payment->transaction_id) · {{ $payment->transaction_id }}@endif</span>
                        @if ($what)<span style="font-size: 14px; line-height: 1.5;">{{ $what }}</span>@endif
                        @if ($payment->rejection_reason)<span class="pp-bad" style="padding: 8px 10px; border-radius: 10px; font-size: 13px; font-weight: 600; line-height: 1.5;">{{ __('student-portal::messages.fees_page.reason') }}: {{ $payment->rejection_reason }}</span>@endif
                        @if ($payment->status->value === 'approved')<a href="{{ route('portal.fees.receipt', $payment) }}" target="_blank" style="justify-self: start; min-height: 40px; padding: 0 14px; border-radius: 999px; border: 1px solid var(--green-700); color: var(--green-700); font-weight: 700; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;">⬇ {{ __('student-portal::messages.fees_page.receipt_pdf') }}</a>@endif
                    </div>
                </div>
            @empty
                <div style="grid-column: 1 / -1;">@include('student-portal::partials.empty', ['title' => __('student-portal::messages.fees_page.no_payments')])</div>
            @endforelse
        </div>
    </div>
@endsection
