@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.fees_page.ledger'))

@section('content')
    <div class="space-y-4">
        <a href="{{ route('portal.fees') }}" class="text-sm">← {{ __('student-portal::messages.fees') }}</a>
        <h1 class="font-head text-2xl text-heading">{{ __('student-portal::messages.fees_page.ledger') }}</h1>
        <div class="overflow-x-auto rounded-xl border border-line bg-surface">
            <table class="w-full text-sm">
                <thead class="bg-surface-2 text-left text-ink-muted"><tr><th class="p-3">{{ __('student-portal::messages.fees_page.date') }}</th><th class="p-3">{{ __('student-portal::messages.fees_page.item') }}</th><th class="p-3 text-right">{{ __('student-portal::messages.fees_page.debit') }}</th><th class="p-3 text-right">{{ __('student-portal::messages.fees_page.credit_col') }}</th><th class="p-3 text-right">{{ __('student-portal::messages.fees_page.balance') }}</th></tr></thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr class="border-t border-line"><td class="p-3 whitespace-nowrap">{{ bn_date($entry['date']) }}</td><td class="p-3"><span class="block font-medium">{{ __('fees::reports.kinds.'.$entry['kind']) }} {{ $entry['reference'] }}</span><span class="text-xs text-ink-muted">{{ $entry['description'] }}</span></td><td class="p-3 text-right">{{ $entry['debit']->isPositive() ? $entry['debit']->format() : '' }}</td><td class="p-3 text-right text-success">{{ $entry['credit']->isPositive() ? $entry['credit']->format() : '' }}</td><td class="p-3 text-right font-semibold">{{ $entry['balance']->format() }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center text-ink-muted">{{ __('student-portal::messages.fees_page.no_payments') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
