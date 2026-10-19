<?php

namespace Modules\Fees\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Models\AcademicSession;
use Modules\Exams\Models\Exam;
use Modules\Fees\Enums\FeeHeadType;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Models\ExamFeeAmount;
use Modules\Fees\Models\FeeHead;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Models\FeeStructure;
use Modules\Fees\Models\StudentConcession;
use Modules\Fees\Support\ConcessionRule;
use Modules\Fees\Support\GenerationSummary;
use Modules\Fees\Support\Money;
use Modules\Students\Enums\StudentStatus;
use Modules\Students\Models\Student;

/**
 * Creates invoices. Monthly generation is idempotent (one invoice per student and month, enforced by a unique index
 * as well), so it can be run again, or scheduled, without ever billing twice.
 */
class InvoiceGenerator
{
    public function __construct(
        protected FeeCalculator $calculator,
        protected DocumentNumbers $numbers,
        protected FeeSettings $settings,
        protected PaymentAllocator $allocator,
    ) {}

    /**
     * One invoice per active student for the month, holding every monthly head that has a fee for the student's class.
     * Students admitted after the month are skipped. With $dryRun nothing is written, so it doubles as the preview.
     *
     * @param  list<int>|null  $classIds
     */
    public function generateMonthly(CarbonInterface $month, ?array $classIds = null, bool $dryRun = false, ?CarbonInterface $dueDate = null): GenerationSummary
    {
        $month = Carbon::instance($month)->startOfMonth();
        $summary = new GenerationSummary($dryRun);
        $heads = FeeHead::query()->where(['type' => FeeHeadType::Monthly->value, 'is_active' => true])->orderBy('sort_order')->orderBy('id')->get();

        Student::query()
            ->where('status', StudentStatus::Active->value)
            ->when($classIds, fn ($query) => $query->whereIn('class_id', $classIds))
            ->orderBy('id')
            ->chunkById(200, function (Collection $students) use ($month, $heads, $summary, $dryRun, $dueDate): void {
                $existing = FeeInvoice::query()->where('type', FeeHeadType::Monthly->value)->whereDate('billing_month', $month->toDateString())->whereIn('student_id', $students->pluck('id'))->pluck('student_id')->flip();
                $concessions = StudentConcession::query()->whereIn('student_id', $students->pluck('id'))->get()->groupBy('student_id');
                $books = [];

                foreach ($students as $student) {
                    if ($student->admission_date->copy()->startOfMonth()->gt($month)) {
                        $summary->skippedPreAdmission++;

                        continue;
                    }

                    if ($existing->has($student->id)) {
                        $summary->skippedExisting++;

                        continue;
                    }

                    $book = $books["{$student->session_id}|{$student->class_id}"] ??= FeeStructure::query()->where(['session_id' => $student->session_id, 'class_id' => $student->class_id])->get();
                    $lines = $this->lines($student, $heads, $month, $book, $concessions->get($student->id, collect()));

                    if ($lines === []) {
                        $summary->skippedNoFee++;

                        continue;
                    }

                    $summary->created++;
                    $summary->totalAmount = $summary->totalAmount->plus(Money::poisha(array_sum(array_map(fn (array $line): int => $line['net']->poisha, $lines))));

                    if (! $dryRun) {
                        $this->createInvoice($student, FeeHeadType::Monthly, $lines, $dueDate ?? $this->dueDateFor($month), month: $month);
                    }
                }
            });

        return $summary;
    }

    /**
     * Exam fee invoices for the students of every class that has an exam fee set. Students who already have one are
     * skipped.
     */
    public function generateExam(Exam $exam, bool $dryRun = false): GenerationSummary
    {
        $summary = new GenerationSummary($dryRun);
        $head = FeeHead::query()->where(['type' => FeeHeadType::Exam->value, 'is_active' => true])->orderBy('sort_order')->first();

        if (! $head) {
            throw ValidationException::withMessages(['exam' => __('fees::messages.no_exam_head')]);
        }

        $month = Carbon::instance($exam->start_date ?? now())->startOfMonth();

        foreach (ExamFeeAmount::query()->where('exam_id', $exam->id)->get() as $fee) {
            if (! $fee->amount->isPositive()) {
                continue;
            }

            $students = Student::query()->where(['class_id' => $fee->class_id, 'session_id' => $exam->session_id, 'status' => StudentStatus::Active->value])->get();
            $existing = FeeInvoice::query()->where(['type' => FeeHeadType::Exam->value, 'exam_id' => $exam->id])->whereIn('student_id', $students->pluck('id'))->pluck('student_id')->flip();
            $concessions = StudentConcession::query()->whereIn('student_id', $students->pluck('id'))->get()->groupBy('student_id');

            foreach ($students as $student) {
                if ($existing->has($student->id)) {
                    $summary->skippedExisting++;

                    continue;
                }

                $discount = $this->calculator->discount($fee->amount, $concessions->get($student->id, collect())->map(fn ($concession) => ConcessionRule::fromModel($concession)), $head->id, $month);
                $lines = [['head' => $head, 'description' => $exam->displayName(), 'gross' => $fee->amount, 'discount' => $discount, 'net' => $fee->amount->minus($discount)]];

                $summary->created++;
                $summary->totalAmount = $summary->totalAmount->plus($lines[0]['net']);

                if (! $dryRun) {
                    $this->createInvoice($student, FeeHeadType::Exam, $lines, $fee->due_date ?? $this->dueDateFor($month), exam: $exam);
                }
            }
        }

        return $summary;
    }

    /**
     * A one-off invoice (admission, books…). An item without an amount takes the structure amount for the student.
     *
     * @param  list<array{fee_head_id: int, amount?: Money|null, description?: string|null}>  $items
     */
    public function generateOneOff(Student $student, array $items, ?CarbonInterface $dueDate = null): FeeInvoice
    {
        $lines = [];
        $concessions = StudentConcession::query()->where('student_id', $student->id)->get();

        foreach ($items as $item) {
            $head = FeeHead::query()->findOrFail($item['fee_head_id']);
            $gross = $item['amount'] ?? $this->calculator->quote($student, $head->id, now(), null, $concessions)['gross'] ?? null;

            if ($gross === null || ! $gross->isPositive()) {
                throw ValidationException::withMessages(['items' => __('fees::messages.item_needs_amount', ['head' => $head->displayName()])]);
            }

            $discount = $this->calculator->discount($gross, $concessions->map(fn ($concession) => ConcessionRule::fromModel($concession)), $head->id, now());
            $lines[] = ['head' => $head, 'description' => $item['description'] ?? null, 'gross' => $gross, 'discount' => $discount, 'net' => $gross->minus($discount)];
        }

        if ($lines === []) {
            throw ValidationException::withMessages(['items' => __('fees::messages.no_items')]);
        }

        $invoice = $this->createInvoice($student, FeeHeadType::OneTime, $lines, $dueDate ?? now()->addDays(10));

        return $invoice;
    }

    /**
     * Make sure monthly invoices exist for the given future months (a guardian paying ahead). Limited to the student's
     * own session; months before admission or outside the session are refused. Existing invoices are returned as is.
     *
     * @param  list<CarbonInterface>  $months
     * @return Collection<int, FeeInvoice>
     */
    public function ensureAdvanceMonths(Student $student, array $months): Collection
    {
        $session = AcademicSession::query()->findOrFail($student->session_id);
        $invoices = collect();
        $heads = FeeHead::query()->where(['type' => FeeHeadType::Monthly->value, 'is_active' => true])->orderBy('sort_order')->get();
        $book = FeeStructure::query()->where(['session_id' => $student->session_id, 'class_id' => $student->class_id])->get();
        $concessions = StudentConcession::query()->where('student_id', $student->id)->get();

        foreach ($months as $month) {
            $month = Carbon::instance($month)->startOfMonth();

            $inSession = (! $session->start_date || $month->gte($session->start_date->copy()->startOfMonth())) && (! $session->end_date || $month->lte($session->end_date->copy()->startOfMonth()));

            if (! $inSession || $month->lt($student->admission_date->copy()->startOfMonth())) {
                throw ValidationException::withMessages(['months' => __('fees::messages.month_not_allowed', ['month' => bn_date($month)])]);
            }

            $invoice = FeeInvoice::query()->where(['student_id' => $student->id, 'type' => FeeHeadType::Monthly->value])->whereDate('billing_month', $month->toDateString())->first();

            if (! $invoice) {
                $lines = $this->lines($student, $heads, $month, $book, $concessions);
                $invoice = $lines === [] ? null : $this->createInvoice($student, FeeHeadType::Monthly, $lines, $this->dueDateFor($month), month: $month);
            }

            if ($invoice) {
                $invoices->push($invoice);
            }
        }

        return $invoices;
    }

    /**
     * What a monthly bill would come to for a student in a month (no invoice is created): used to show a payer the
     * price of paying ahead before they commit.
     */
    public function estimateMonth(Student $student, CarbonInterface $month): Money
    {
        $heads = FeeHead::query()->where(['type' => FeeHeadType::Monthly->value, 'is_active' => true])->get();
        $book = FeeStructure::query()->where(['session_id' => $student->session_id, 'class_id' => $student->class_id])->get();
        $lines = $this->lines($student, $heads, Carbon::instance($month)->startOfMonth(), $book, StudentConcession::query()->where('student_id', $student->id)->get());

        return Money::poisha(array_sum(array_map(fn (array $line): int => $line['net']->poisha, $lines)));
    }

    /**
     * @param  Collection<int, FeeHead>  $heads
     * @param  Collection<int, FeeStructure>  $book
     * @param  Collection<int, StudentConcession>  $concessions
     * @return list<array{head: FeeHead, description: string|null, gross: Money, discount: Money, net: Money}>
     */
    protected function lines(Student $student, Collection $heads, CarbonInterface $month, Collection $book, Collection $concessions): array
    {
        $lines = [];

        foreach ($heads as $head) {
            $quote = $this->calculator->quote($student, $head->id, $month, $book->where('fee_head_id', $head->id), $concessions);

            if ($quote !== null && $quote['gross']->isPositive()) {
                $lines[] = ['head' => $head, 'description' => null, 'gross' => $quote['gross'], 'discount' => $quote['discount'], 'net' => $quote['net']];
            }
        }

        return $lines;
    }

    /**
     * @param  list<array{head: FeeHead, description: string|null, gross: Money, discount: Money, net: Money}>  $lines
     */
    protected function createInvoice(Student $student, FeeHeadType $type, array $lines, CarbonInterface $dueDate, ?CarbonInterface $month = null, ?Exam $exam = null): FeeInvoice
    {
        return DB::transaction(function () use ($student, $type, $lines, $dueDate, $month, $exam): FeeInvoice {
            $subtotal = Money::poisha(array_sum(array_map(fn (array $line): int => $line['gross']->poisha, $lines)));
            $discount = Money::poisha(array_sum(array_map(fn (array $line): int => $line['discount']->poisha, $lines)));
            $total = $subtotal->minus($discount);

            $invoice = FeeInvoice::query()->create([
                'invoice_no' => $this->numbers->invoice(),
                'student_id' => $student->id, 'session_id' => $student->session_id, 'class_id' => $student->class_id,
                'billing_month' => $month?->toDateString(), 'exam_id' => $exam?->id, 'type' => $type,
                'subtotal' => $subtotal, 'discount' => $discount, 'late_fine' => Money::zero(), 'total' => $total, 'paid' => Money::zero(), 'due' => $total,
                'status' => $total->isZero() ? InvoiceStatus::Paid : InvoiceStatus::Unpaid,
                'due_date' => $dueDate->toDateString(), 'issued_at' => now(),
            ]);

            foreach ($lines as $line) {
                $invoice->items()->create(['fee_head_id' => $line['head']->id, 'description' => $line['description'], 'amount' => $line['gross'], 'discount' => $line['discount'], 'net' => $line['net']]);
            }

            // Money the student already holds as credit is used right away.
            $this->allocator->applyCredit($student);

            return $invoice->refresh();
        });
    }

    protected function dueDateFor(CarbonInterface $month): CarbonInterface
    {
        return Carbon::instance($month)->startOfMonth()->day(min($this->settings->dueDay(), $month->daysInMonth));
    }
}
