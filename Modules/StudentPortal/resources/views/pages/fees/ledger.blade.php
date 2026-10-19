@extends('student-portal::layouts.portal')

@section('title', __('student-portal::messages.nav.fees'))

@section('content')
    @php
        $totalDebit = collect($entries)->sum(fn ($entry): int => $entry['debit']->poisha);
        $totalCredit = collect($entries)->sum(fn ($entry): int => $entry['credit']->poisha);
        $money = fn (int $poisha): string => \Modules\Fees\Support\Money::poisha($poisha)->format();
        $summary = [
            [__('student-portal::messages.fees_page.sum_invoiced'), $money($totalDebit), 'var(--surface)', 'var(--ink)'],
            [__('student-portal::messages.fees_page.sum_paid'), $money($totalCredit), 'var(--success-bg)', 'var(--success)'],
            [__('student-portal::messages.fees_page.due'), $due->format(), $due->isPositive() ? 'var(--danger-bg)' : 'var(--surface)', $due->isPositive() ? 'var(--danger)' : 'var(--ink)'],
            [__('student-portal::messages.fees_page.sum_advance'), $credit->format(), 'var(--info-bg)', 'var(--info)'],
        ];
    @endphp

    <div data-screen-label="Portal fees" style="display: grid; gap: 14px;">
        @include('student-portal::partials.fee-tabs', ['active' => 'ledger'])

        <div data-screen-label="Fee ledger" style="display: grid; gap: 12px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                @foreach ($summary as [$key, $value, $background, $color])
                    <div style="padding: 12px 14px; border-radius: 16px; background: {{ $background }}; display: grid; {{ $background === 'var(--surface)' ? 'border: 1px solid var(--line);' : '' }}"><span style="font-size: 13px; color: var(--ds-muted); font-weight: 600;">{{ $key }}</span><strong class="pp-head" style="font-size: 22px; line-height: 1.35; color: {{ $color }};">{{ $value }}</strong></div>
                @endforeach
            </div>
            <div class="pp-card" style="overflow-x: auto;">
                <table style="width: 100%; min-width: 600px; border-collapse: collapse; font-size: 15px;">
                    <thead><tr style="background: var(--surface-2); text-align: left;"><th style="padding: 12px 14px;">{{ __('student-portal::messages.fees_page.date') }}</th><th style="padding: 12px 8px;">{{ __('student-portal::messages.fees_page.item') }}</th><th style="padding: 12px 8px; text-align: right;">{{ __('student-portal::messages.fees_page.debit') }}</th><th style="padding: 12px 8px; text-align: right;">{{ __('student-portal::messages.fees_page.credit_col') }}</th><th style="padding: 12px 14px; text-align: right;">{{ __('student-portal::messages.fees_page.balance') }}</th></tr></thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr style="border-top: 1px solid var(--line);">
                                <td style="padding: 10px 14px; white-space: nowrap; font-size: 14px;">{{ \Modules\StudentPortal\Support\PortalFormat::dayMonth($entry['date']) }}</td>
                                <td style="padding: 10px 8px; line-height: 1.45;">{{ __('fees::reports.kinds.'.$entry['kind']) }} — {{ $entry['description'] }}<br><span class="pp-en" style="font-size: 12px; color: var(--ds-muted);">{{ $entry['reference'] }}</span></td>
                                <td style="padding: 10px 8px; text-align: right; white-space: nowrap; color: var(--danger);">{{ $entry['debit']->isPositive() ? $entry['debit']->format() : '—' }}</td>
                                <td style="padding: 10px 8px; text-align: right; white-space: nowrap; color: var(--success);">{{ $entry['credit']->isPositive() ? $entry['credit']->format() : '—' }}</td>
                                <td style="padding: 10px 14px; text-align: right; white-space: nowrap; font-weight: 700;">{{ $entry['balance']->poisha >= 0 ? $entry['balance']->format() : __('student-portal::messages.fees_page.advance_word').' '.\Modules\Fees\Support\Money::poisha(-$entry['balance']->poisha)->format() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="padding: 24px; text-align: center; color: var(--ds-muted);">{{ __('student-portal::messages.fees_page.no_payments') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <span style="font-size: 13px; color: var(--ds-muted);">{{ __('student-portal::messages.fees_page.ledger_note') }}</span>
        </div>
    </div>
@endsection
