<?php

namespace Modules\Fees\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Core\Support\MenuItem;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Models\FeeInvoice;
use Modules\Students\Models\Student;
use Modules\Students\Support\StudentSummaryItem;

/**
 * What Fees adds to the student portal: the "বেতন ও ফি" menu entry and the due cards.
 */
class FeeSummary
{
    public function __construct(protected StudentFeeDesk $desk) {}

    public function menuItem(Authenticatable $student): MenuItem
    {
        return new MenuItem(__('fees::messages.menu'), 'portal.fees', 'wallet', 20, true, fn (Authenticatable $user): int => FeeInvoice::query()->where('student_id', $user->getAuthIdentifier())->whereIn('status', array_map(fn (InvoiceStatus $status): string => $status->value, InvoiceStatus::open()))->count());
    }

    /**
     * @return list<StudentSummaryItem>
     */
    public function items(Student $student): array
    {
        $due = $this->desk->totalDue($student);
        $current = collect($this->desk->monthGrid($student))->first(fn (array $month): bool => $month['month']->isSameMonth(now()));

        $items = [new StudentSummaryItem(__('fees::messages.summary.total_due'), $due->format(), $due->isPositive() ? 'warn' : 'success', 'portal.fees', 10)];

        if ($current) {
            $items[] = new StudentSummaryItem(__('fees::messages.summary.this_month'), __('fees::messages.summary.status.'.$current['status']), in_array($current['status'], ['paid', 'waived'], true) ? 'success' : ($current['status'] === 'overdue' ? 'danger' : 'neutral'), 'portal.fees', 11);
        }

        return $items;
    }
}
