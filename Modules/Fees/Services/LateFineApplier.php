<?php

namespace Modules\Fees\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Models\FeeInvoice;

/**
 * Adds the late fine to overdue open invoices. Idempotent: the fine an invoice should carry is worked out from how late
 * it is and only ever raised to that, so running it twice a day changes nothing. Paid, waived and cancelled invoices
 * are never touched.
 */
class LateFineApplier
{
    public function __construct(protected FeeSettings $settings) {}

    /**
     * @return int Number of invoices whose fine changed.
     */
    public function apply(?CarbonInterface $today = null): int
    {
        $fine = $this->settings->lateFine();

        if (! $fine->isPositive()) {
            return 0;
        }

        $today = Carbon::instance($today ?? now())->startOfDay();
        $grace = $this->settings->lateFineAfterDays();
        $perMonth = $this->settings->lateFineMode() === 'per_month';
        $changed = 0;

        FeeInvoice::query()
            ->whereIn('status', array_map(fn (InvoiceStatus $status): string => $status->value, InvoiceStatus::open()))
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today->copy()->subDays($grace)->toDateString())
            ->orderBy('id')
            ->chunkById(200, function ($invoices) use ($fine, $today, $grace, $perMonth, &$changed): void {
                foreach ($invoices as $invoice) {
                    $daysLate = (int) $invoice->due_date->copy()->addDays($grace)->diffInDays($today);
                    $periods = $perMonth ? 1 + intdiv(max(0, $daysLate - 1), 30) : 1;
                    $target = $fine->times($periods);

                    if ($invoice->late_fine->poisha >= $target->poisha) {
                        continue;
                    }

                    DB::transaction(function () use ($invoice, $target): void {
                        $locked = FeeInvoice::query()->whereKey($invoice->id)->lockForUpdate()->first();

                        if ($locked->isOpen() && $locked->late_fine->poisha < $target->poisha) {
                            $locked->forceFill(['late_fine' => $target])->save();
                            $locked->recalculate();
                        }
                    });

                    $changed++;
                }
            });

        return $changed;
    }
}
