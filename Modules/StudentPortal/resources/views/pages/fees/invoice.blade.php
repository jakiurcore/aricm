@extends('student-portal::layouts.portal')

@section('title', $invoice->invoice_no)

@section('content')
    <div class="space-y-4">
        <a href="{{ route('portal.fees') }}" class="text-sm">← {{ __('student-portal::messages.fees') }}</a>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="font-head text-2xl text-heading">{{ $invoice->invoice_no }}</h1>
            <span class="rounded-full bg-surface-2 px-3 py-1 text-sm font-semibold">{{ $invoice->status->label() }}</span>
        </div>
        <div class="overflow-hidden rounded-xl border border-line bg-surface">
            <table class="w-full text-sm">
                <thead class="bg-surface-2 text-left text-ink-muted"><tr><th class="p-3">{{ __('student-portal::messages.fees_page.item') }}</th><th class="p-3 text-right">{{ __('student-portal::messages.fees_page.amount') }}</th><th class="p-3 text-right">{{ __('student-portal::messages.fees_page.discount') }}</th><th class="p-3 text-right">{{ __('student-portal::messages.fees_page.net') }}</th></tr></thead>
                <tbody>
                    @foreach ($invoice->items as $item)<tr class="border-t border-line"><td class="p-3">{{ $item->head->displayName() }}</td><td class="p-3 text-right">{{ $item->amount->format() }}</td><td class="p-3 text-right">{{ $item->discount->format() }}</td><td class="p-3 text-right">{{ $item->net->format() }}</td></tr>@endforeach
                    @if ($invoice->late_fine->isPositive())<tr class="border-t border-line"><td class="p-3" colspan="3">{{ __('student-portal::messages.fees_page.fine') }}</td><td class="p-3 text-right">{{ $invoice->late_fine->format() }}</td></tr>@endif
                    <tr class="border-t border-line bg-green-50 font-semibold"><td class="p-3" colspan="3">{{ __('student-portal::messages.fees_page.total') }}</td><td class="p-3 text-right">{{ $invoice->total->format() }}</td></tr>
                    <tr class="border-t border-line"><td class="p-3" colspan="3">{{ __('student-portal::messages.fees_page.paid') }}</td><td class="p-3 text-right">{{ $invoice->paid->format() }}</td></tr>
                    <tr class="border-t border-line font-semibold text-danger"><td class="p-3" colspan="3">{{ __('student-portal::messages.fees_page.due') }}</td><td class="p-3 text-right">{{ $invoice->due->format() }}</td></tr>
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($invoice->isOpen())<a href="{{ route('portal.fees.pay', ['invoice' => $invoice->id]) }}" class="rounded-md bg-brand px-5 py-2.5 font-semibold text-white no-underline">💳 {{ __('student-portal::messages.fees_page.pay_now') }}</a>@endif
            <a href="{{ route('portal.fees.invoice.pdf', $invoice) }}" target="_blank" class="rounded-md border border-line-strong px-5 py-2.5 font-semibold no-underline">⬇ PDF</a>
        </div>
    </div>
@endsection
