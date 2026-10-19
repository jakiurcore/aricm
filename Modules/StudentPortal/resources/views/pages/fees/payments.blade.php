@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.fees_page.history'))

@section('content')
    <div class="space-y-4">
        <a href="{{ route('portal.fees') }}" class="text-sm">← {{ __('student-portal::messages.fees') }}</a>
        <h1 class="font-head text-2xl text-heading">{{ __('student-portal::messages.fees_page.history') }}</h1>
        @if (session('payment_submitted'))<p class="rounded-md bg-success-bg p-3 font-medium text-success">{{ __('student-portal::messages.fees_page.submitted') }}</p>@endif
        <div class="space-y-3">
            @forelse ($payments as $payment)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-surface p-4 shadow-sm">
                    <div>
                        <p class="font-semibold">{{ $payment->amount->format() }} · {{ $payment->method->label() }}</p>
                        <p class="text-sm text-ink-muted">{{ bn_date($payment->paid_at ?? $payment->created_at) }}@if ($payment->transaction_id) · {{ $payment->transaction_id }}@endif</p>
                        @if ($payment->rejection_reason)<p class="text-sm text-danger">{{ $payment->rejection_reason }}</p>@endif
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full px-3 py-1 text-sm font-semibold {{ ['approved' => 'bg-success-bg text-success', 'pending' => 'bg-warn-bg text-warn'][$payment->status->value] ?? 'bg-danger-bg text-danger' }}">{{ $payment->status->label() }}</span>
                        @if ($payment->status->value === 'approved')<a href="{{ route('portal.fees.receipt', $payment) }}" target="_blank" class="rounded-md border border-line-strong px-3 py-1.5 text-sm font-semibold no-underline">🧾 {{ __('student-portal::messages.fees_page.receipt') }}</a>@endif
                    </div>
                </div>
            @empty
                <p class="text-ink-muted">{{ __('student-portal::messages.fees_page.no_payments') }}</p>
            @endforelse
        </div>
    </div>
@endsection
