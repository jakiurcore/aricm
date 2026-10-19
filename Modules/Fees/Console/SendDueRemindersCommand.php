<?php

namespace Modules\Fees\Console;

use Illuminate\Console\Command;
use Modules\Core\Services\Sms\SmsManager;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Services\FeeSettings;
use Modules\Fees\Support\Money;
use Modules\Students\Models\Student;

class SendDueRemindersCommand extends Command
{
    protected $signature = 'fees:due-reminders';

    protected $description = 'SMS the guardian of every student with overdue fees. Off unless SMS notifications are switched on.';

    public function handle(FeeSettings $settings, SmsManager $sms): int
    {
        if (! $settings->smsEnabled()) {
            $this->components->info('SMS notifications are off.');

            return self::SUCCESS;
        }

        $sent = 0;

        FeeInvoice::query()
            ->selectRaw('student_id, sum(due) as due')
            ->whereIn('status', array_map(fn (InvoiceStatus $status): string => $status->value, InvoiceStatus::open()))
            ->whereDate('due_date', '<', now()->toDateString())
            ->groupBy('student_id')->get()
            ->each(function ($row) use ($sms, &$sent): void {
                $student = Student::query()->find($row->student_id);

                if ($student && $sms->send($student->guardian_phone, __('fees::messages.reminder', ['student' => $student->displayName(), 'amount' => Money::poisha((int) $row->due)->format(), 'institute' => setting('institute_name_'.app()->getLocale(), '')]))) {
                    $sent++;
                }
            });

        $this->components->info("{$sent} reminder(s) sent.");

        return self::SUCCESS;
    }
}
