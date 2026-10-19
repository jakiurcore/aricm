<?php

namespace Modules\Fees\Database\Seeders;

use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Services\AcademicDirectory;
use Modules\Accounts\Models\User;
use Modules\Exams\Models\Exam;
use Modules\Fees\Enums\PaymentMethod;
use Modules\Fees\Models\ExamFeeAmount;
use Modules\Fees\Models\FeeHead;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Models\FeeStructure;
use Modules\Fees\Models\StudentConcession;
use Modules\Fees\Services\FeeSettings;
use Modules\Fees\Services\InvoiceGenerator;
use Modules\Fees\Services\LateFineApplier;
use Modules\Fees\Services\PaymentService;
use Modules\Fees\Support\Money;
use Modules\Students\Enums\ResidencyType;
use Modules\Students\Models\Student;

class DemoFeesSeeder extends Seeder
{
    public function run(AcademicDirectory $academic, InvoiceGenerator $generator, PaymentService $payments, FeeSettings $settings, LateFineApplier $fines): void
    {
        if (FeeStructure::query()->exists()) {
            return;
        }

        $session = $academic->currentSession();
        $tuition = FeeHead::query()->where('name_bn', 'মাসিক বেতন')->firstOrFail();
        $residential = FeeHead::query()->where('name_bn', 'আবাসিক ফি')->firstOrFail();
        $abacus = FeeHead::query()->where('name_bn', 'মেন্টাল অ্যাবাকাস ফি')->firstOrFail();

        $settings->update([
            'due_day' => 10, 'late_fine_amount' => Money::taka(50)->poisha, 'late_fine_after_days' => 0, 'late_fine_mode' => 'once',
            'manual_methods' => [
                ['method' => 'bkash', 'number' => '01714844346', 'account_type' => 'personal', 'instruction_bn' => 'বিকাশ অ্যাপ থেকে “সেন্ড মানি” করুন এবং ট্রানজেকশন আইডি লিখুন।', 'instruction_en' => 'Use “Send Money” in the bKash app and enter the transaction ID.'],
                ['method' => 'nagad', 'number' => '01761781338', 'account_type' => 'personal', 'instruction_bn' => 'নগদ অ্যাপ থেকে “সেন্ড মানি” করুন।', 'instruction_en' => 'Use “Send Money” in the Nagad app.'],
            ],
            'receipt_footer' => 'আর রহমান ইন্টারন্যাশনাল ক্যাডেট মাদরাসা — ধন্যবাদ',
        ]);

        // Monthly tuition grows with the class; residential students pay a boarding fee on top; everyone pays an abacus fee.
        foreach (SchoolClass::query()->orderBy('numeric_order')->get() as $index => $class) {
            FeeStructure::create(['session_id' => $session->id, 'class_id' => $class->id, 'fee_head_id' => $tuition->id, 'amount' => Money::taka(800 + $index * 70)]);
            FeeStructure::create(['session_id' => $session->id, 'class_id' => $class->id, 'fee_head_id' => $residential->id, 'residency_type' => ResidencyType::Residential, 'amount' => Money::taka(2000)]);
            FeeStructure::create(['session_id' => $session->id, 'class_id' => $class->id, 'fee_head_id' => $abacus->id, 'amount' => Money::taka(150)]);
        }

        $annual = Exam::query()->where('name_bn', 'like', 'বার্ষিক%')->first();

        foreach ($annual?->classes ?? [] as $class) {
            ExamFeeAmount::create(['exam_id' => $annual->id, 'class_id' => $class->id, 'amount' => Money::taka(200), 'due_date' => '2026-11-25']);
        }

        $students = Student::query()->orderBy('id')->get();

        foreach ([[1, 25, 'sibling', $tuition->id], [4, 50, 'orphan', null], [7, 100, 'merit', $tuition->id]] as [$position, $value, $reason, $head]) {
            $student = $students->get($position);
            StudentConcession::create([
                'student_id' => $student->id, 'fee_head_id' => $head, 'kind' => $reason === 'merit' ? 'fixed' : 'percent',
                'value' => $reason === 'merit' ? Money::taka($value)->poisha : $value, 'reason_type' => $reason, 'note' => 'ডেমো', 'valid_from_month' => '2026-01-01',
            ]);
        }

        $months = collect([2, 1, 0])->map(fn (int $back): CarbonInterface => now()->startOfMonth()->subMonths($back));
        foreach ($months as $month) {
            $generator->generateMonthly($month);
        }

        $accountant = User::query()->role('accountant')->first();

        // Mixed states: everyone clears the oldest month, every second student also the next, a few pay part of the latest.
        foreach ($students as $index => $student) {
            $open = FeeInvoice::query()->where('student_id', $student->id)->orderBy('billing_month')->get();

            if ($accountant && $open->count() >= 1) {
                $payments->collectAtCounter($student, $open[0]->due, $index % 3 === 0 ? PaymentMethod::Bkash : PaymentMethod::Cash, [$open[0]->id], $accountant, $index % 3 === 0 ? 'DEMO'.str_pad((string) $index, 6, '0', STR_PAD_LEFT) : null);

                if ($index % 2 === 0 && $open->count() >= 2) {
                    $payments->collectAtCounter($student, $open[1]->due, PaymentMethod::Cash, [$open[1]->id], $accountant);
                }

                if ($index % 5 === 0 && $open->count() >= 3) {
                    $payments->collectAtCounter($student, Money::taka(300), PaymentMethod::Cash, [$open[2]->id], $accountant, note: 'আংশিক');
                }
            }
        }

        // Three pending bKash payments for the verification queue.
        foreach ([3, 5, 9] as $slot => $index) {
            $student = $students->get($index);
            $invoice = FeeInvoice::query()->where('student_id', $student->id)->whereIn('status', ['unpaid', 'partial'])->orderBy('billing_month')->first();

            if ($invoice) {
                $payments->submitManual($student, [$invoice->id], PaymentMethod::Bkash, 'BK7Q'.str_pad((string) ($slot + 1), 6, '0', STR_PAD_LEFT), $student->guardian_phone);
            }
        }

        $fines->apply();
    }
}
