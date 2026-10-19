@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.nav.fees'))

@section('content')
    @php
        $identity = app(\Modules\Core\Services\PublicSite::class)->identity();
        $status = \Modules\StudentPortal\Support\FeeDisplay::status(\Modules\StudentPortal\Support\FeeDisplay::invoiceStatus($invoice, $pending));
        $meta = [
            [__('student-portal::messages.fees_page.month_purpose'), \Modules\StudentPortal\Support\FeeDisplay::invoiceTitle($invoice)],
            [__('student-portal::messages.fees_page.student'), $student->displayName().' · '.$student->student_id],
            [__('student-portal::messages.fees_page.issued'), bn_date($invoice->issued_at ?? $invoice->created_at)],
            [__('student-portal::messages.fees_page.deadline_full'), $invoice->due_date ? bn_date($invoice->due_date) : '—'],
        ];
        $canPay = $invoice->isOpen() && ! $pending;
        $note = $invoice->status === \Modules\Fees\Enums\InvoiceStatus::Waived ? $invoice->waive_reason : ($pending ? __('student-portal::messages.fees_page.invoice_checking') : null);
    @endphp

    <div data-screen-label="Invoice detail" style="display: grid; gap: 12px; max-width: 720px; width: 100%; justify-self: center;">
        <a href="{{ route('portal.fees') }}" class="pp-btn" style="min-height: 44px; justify-self: start;">← {{ __('student-portal::messages.nav.fees') }}</a>
        <div style="border-radius: 24px; background: var(--surface); border: 1px solid var(--line); box-shadow: var(--shadow-md); overflow: hidden;">
            <div style="padding: 16px 18px; background: var(--green-50); display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <img src="{{ $identity['logo'] }}" alt="" style="width: 48px; height: 48px; border-radius: 50%; background: #fff; object-fit: cover;">
                <span style="flex: 1; min-width: 160px; display: grid; line-height: 1.4;"><strong class="pp-head" style="color: var(--heading); font-size: 20px;">{{ __('student-portal::messages.fees_page.invoice') }}</strong><span class="pp-en" style="font-size: 14px; color: var(--ds-muted);">{{ $invoice->invoice_no }}</span></span>
                <span class="pp-pill {{ $status['class'] }}" style="font-size: 14px;">{{ $status['glyph'] }} {{ $status['label'] }}</span>
            </div>
            <div style="padding: 14px 18px; display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 200px), 1fr)); gap: 8px 16px; border-bottom: 1px solid var(--line);">
                @foreach ($meta as [$key, $value])
                    <span style="display: grid; line-height: 1.45;"><span style="font-size: 13px; color: var(--ds-muted);">{{ $key }}</span><strong style="font-size: 15px;">{{ $value }}</strong></span>
                @endforeach
            </div>
            <div style="padding: 6px 18px 14px;">
                <div style="display: flex; justify-content: space-between; padding: 8px 0; font-size: 13px; font-weight: 700; color: var(--ds-muted); border-bottom: 1px solid var(--line);"><span>{{ __('student-portal::messages.fees_page.fee_head') }}</span><span>{{ __('student-portal::messages.fees_page.amount') }}</span></div>
                @foreach ($invoice->items as $item)
                    <div style="display: flex; justify-content: space-between; gap: 10px; padding: 10px 0; border-bottom: 1px dashed var(--line); font-size: 16px; color: var(--ink);"><span>{{ $item->head->displayName() }}</span><span style="font-weight: 600; white-space: nowrap;">{{ $item->amount->format() }}</span></div>
                @endforeach
                @if ($invoice->discount->isPositive())
                    <div style="display: flex; justify-content: space-between; gap: 10px; padding: 8px 0; font-size: 15px; font-weight: 600; color: var(--success);"><span>{{ __('student-portal::messages.fees_page.discount') }}</span><span style="white-space: nowrap;">− {{ $invoice->discount->format() }}</span></div>
                @endif
                @if ($invoice->late_fine->isPositive())
                    <div style="display: flex; justify-content: space-between; gap: 10px; padding: 8px 0; font-size: 15px; font-weight: 600; color: var(--danger);"><span>{{ __('student-portal::messages.fees_page.fine') }}</span><span style="white-space: nowrap;">+ {{ $invoice->late_fine->format() }}</span></div>
                @endif
                <div style="display: flex; justify-content: space-between; gap: 10px; padding: 8px 0; font-size: 17px; font-weight: 700; color: var(--ink);"><span>{{ __('student-portal::messages.fees_page.total') }}</span><span style="white-space: nowrap;">{{ $invoice->total->format() }}</span></div>
                <div style="display: flex; justify-content: space-between; gap: 10px; padding: 8px 0; font-size: 15px; font-weight: 600; color: var(--success);"><span>{{ __('student-portal::messages.fees_page.paid') }}</span><span style="white-space: nowrap;">{{ $invoice->paid->format() }}</span></div>
                <div style="display: flex; justify-content: space-between; gap: 10px; padding: 8px 0; font-size: 20px; font-weight: 700; color: {{ $invoice->due->isPositive() ? 'var(--danger)' : 'var(--ink)' }};"><span>{{ __('student-portal::messages.fees_page.due') }}</span><span style="white-space: nowrap;">{{ $invoice->due->format() }}</span></div>
                @if ($note)<div class="pp-info" style="margin-top: 8px; padding: 10px 12px; border-radius: 12px; font-size: 14px; font-weight: 600;">{{ $note }}</div>@endif
            </div>
            <div style="padding: 14px 18px; border-top: 1px solid var(--line); display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end;">
                <a href="{{ route('portal.fees.invoice.pdf', $invoice) }}" target="_blank" class="pp-btn">⬇ {{ __('student-portal::messages.fees_page.download_pdf') }}</a>
                @if ($canPay)<a href="{{ route('portal.fees.pay', ['invoice' => $invoice->id]) }}" class="pp-btn-red" style="min-height: 48px; padding: 0 22px; border-radius: 999px; font-size: 16px; display: inline-flex; align-items: center; text-decoration: none; font-weight: 700; color: #fff;">{{ __('student-portal::messages.fees_page.pay_this') }}</a>@endif
            </div>
        </div>
    </div>
@endsection
