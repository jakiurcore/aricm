@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.fees'))

@section('content')
    <div class="space-y-6">
        @if (session('payment_submitted'))<p class="rounded-md bg-success-bg p-3 font-medium text-success">{{ __('student-portal::messages.fees_page.submitted') }}</p>@endif
        @if (session('payment_status') === 'approved')<p class="rounded-md bg-success-bg p-3 font-medium text-success">{{ __('student-portal::messages.fees_page.paid_online') }}</p>@endif
        @if (in_array(session('payment_status'), ['failed', 'pending'], true))<p class="rounded-md bg-warn-bg p-3 font-medium text-warn">{{ __('student-portal::messages.fees_page.not_confirmed') }}</p>@endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="font-head text-2xl text-heading">{{ __('student-portal::messages.fees') }}</h1>
            <div class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('portal.fees.payments') }}" class="rounded-md border border-line-strong px-3 py-2 font-semibold no-underline">{{ __('student-portal::messages.fees_page.history') }}</a>
                <a href="{{ route('portal.fees.ledger') }}" class="rounded-md border border-line-strong px-3 py-2 font-semibold no-underline">{{ __('student-portal::messages.fees_page.ledger') }}</a>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                <p class="text-sm text-ink-muted">{{ __('student-portal::messages.fees_page.total_due') }}</p>
                <p class="font-head text-4xl {{ $due->isPositive() ? 'text-danger' : 'text-success' }}">{{ $due->format() }}</p>
                @if ($credit->isPositive())<p class="mt-1 text-sm text-success">{{ __('student-portal::messages.fees_page.credit', ['amount' => $credit->format()]) }}</p>@endif
            </div>
            <a href="{{ route('portal.fees.pay') }}" class="flex items-center justify-center rounded-2xl bg-brand p-5 text-xl font-semibold text-white no-underline shadow-md">💳 {{ __('student-portal::messages.fees_page.pay_now') }}</a>
        </div>

        <section>
            <h2 class="mb-2 font-head text-lg text-heading">{{ __('student-portal::messages.fees_page.months') }}</h2>
            <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-6">
                @foreach ($grid as $month)
                    @php($style = ['paid' => 'bg-success-bg text-success border-success/30', 'waived' => 'bg-info-bg text-info border-info/30', 'partial' => 'bg-warn-bg text-warn border-warn/30', 'unpaid' => 'bg-warn-bg text-warn border-warn/30', 'overdue' => 'bg-danger-bg text-danger border-danger/30', 'upcoming' => 'bg-surface-2 text-faint border-line', 'none' => 'bg-surface-2 text-faint border-line'][$month['status']])
                    @php($tag = $month['invoice_id'] ? 'a' : 'div')
                    <{{ $tag }} @if ($month['invoice_id']) href="{{ route('portal.fees.invoice', $month['invoice_id']) }}" @endif class="rounded-lg border p-2.5 text-center no-underline {{ $style }}">
                        <p class="text-sm font-semibold">{{ \Modules\Core\Support\Bangla::monthName($month['month']) }}</p>
                        <p class="text-xs">{{ __('fees::messages.summary.status.'.$month['status']) }}</p>
                    </{{ $tag }}>
                @endforeach
            </div>
        </section>

        @if ($open->isNotEmpty())
            <section>
                <h2 class="mb-2 font-head text-lg text-heading">{{ __('student-portal::messages.fees_page.open') }}</h2>
                <div class="divide-y divide-line overflow-hidden rounded-xl border border-line bg-surface">
                    @foreach ($open as $invoice)
                        <a href="{{ route('portal.fees.invoice', $invoice) }}" class="flex items-center justify-between gap-3 p-4 no-underline hover:bg-surface-2">
                            <span><span class="block font-semibold text-ink">{{ $invoice->billing_month ? \Modules\Core\Support\Bangla::monthYear($invoice->billing_month) : $invoice->type->label() }}</span><span class="text-xs text-ink-muted">{{ $invoice->invoice_no }} · {{ __('student-portal::messages.fees_page.due_date') }} {{ bn_date($invoice->due_date) }}</span></span>
                            <span class="text-right"><span class="block font-semibold text-danger">{{ $invoice->due->format() }}</span>@if ($invoice->late_fine->isPositive())<span class="text-xs text-danger">{{ __('student-portal::messages.fees_page.fine') }} {{ $invoice->late_fine->format() }}</span>@endif</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
