<?php

namespace Modules\StudentPortal\Support;

use Modules\Core\Support\Bangla;
use Modules\Fees\Enums\FeeHeadType;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Models\FeeInvoice;

/**
 * How fee records look on the portal: one status vocabulary for months, bills and payments, wallet colours, bill titles.
 */
final class FeeDisplay
{
    /**
     * Status key => [glyph, CSS class from the portal layout, background, text colour].
     *
     * @var array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    private const array STATUSES = [
        'paid' => ['✓', 'pp-ok', 'var(--success-bg)', 'var(--success)'],
        'partial' => ['◐', 'pp-warn', 'var(--warn-bg)', 'var(--warn)'],
        'due' => ['!', 'pp-bad', 'var(--danger-bg)', 'var(--danger)'],
        'upcoming' => ['○', 'pp-mute', 'var(--surface-2)', 'var(--ds-muted)'],
        'none' => ['–', 'pp-mute', 'var(--surface-2)', 'var(--ds-muted)'],
        'waived' => ['✦', 'pp-info', 'var(--info-bg)', 'var(--info)'],
        'pending' => ['⏳', 'pp-warn', 'var(--warn-bg)', 'var(--warn)'],
        'approved' => ['✓', 'pp-ok', 'var(--success-bg)', 'var(--success)'],
        'rejected' => ['✕', 'pp-bad', 'var(--danger-bg)', 'var(--danger)'],
    ];

    /**
     * Method => [icon, colour].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const array WALLETS = [
        'cash' => ['৳', '#0B6B3A'],
        'bkash' => ['b', '#E2136E'],
        'nagad' => ['N', '#EC1C24'],
        'rocket' => ['R', '#8C3494'],
        'bank' => ['B', '#16307A'],
        'gateway' => ['⌁', '#16307A'],
    ];

    /**
     * @return array{glyph: string, label: string, class: string, bg: string, fg: string}
     */
    public static function status(string $key): array
    {
        $key = isset(self::STATUSES[$key]) ? $key : 'upcoming';

        return ['glyph' => self::STATUSES[$key][0], 'label' => __('student-portal::messages.fee_status.'.$key), 'class' => self::STATUSES[$key][1], 'bg' => self::STATUSES[$key][2], 'fg' => self::STATUSES[$key][3]];
    }

    /**
     * The status key of a bill; a bill with a payment still being checked reads "pending".
     */
    public static function invoiceStatus(FeeInvoice $invoice, bool $hasPendingPayment): string
    {
        if ($hasPendingPayment && $invoice->isOpen()) {
            return 'pending';
        }

        return match ($invoice->status) {
            InvoiceStatus::Paid => 'paid',
            InvoiceStatus::Partial => 'partial',
            InvoiceStatus::Waived => 'waived',
            InvoiceStatus::Cancelled => 'upcoming',
            default => 'due',
        };
    }

    /**
     * @return array{icon: string, color: string}
     */
    public static function wallet(string $method): array
    {
        [$icon, $color] = self::WALLETS[$method] ?? ['৳', '#16307A'];

        return ['icon' => $icon, 'color' => $color];
    }

    /**
     * "অক্টোবর ২০২৬ — মাসিক বেতন" for a monthly bill, the exam or fee name otherwise.
     */
    public static function invoiceTitle(FeeInvoice $invoice): string
    {
        if ($invoice->type === FeeHeadType::Monthly && $invoice->billing_month) {
            return Bangla::monthYear($invoice->billing_month).' — '.$invoice->type->label();
        }

        if ($invoice->exam) {
            return $invoice->exam->displayName();
        }

        $heads = $invoice->items->map(fn ($item): string => $item->head->displayName())->implode(', ');

        return $heads !== '' ? $heads : $invoice->type->label();
    }
}
