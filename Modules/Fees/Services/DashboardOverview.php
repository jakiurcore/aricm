<?php

namespace Modules\Fees\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Modules\Academic\Models\AcademicSession;
use Modules\Academic\Models\SchoolClass;
use Modules\Accounts\Models\User;
use Modules\Exams\Enums\ExamStatus;
use Modules\Exams\Models\Exam;
use Modules\Fees\Enums\FeeHeadType;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Enums\PaymentStatus;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Models\Payment;
use Modules\Students\Enums\StudentStatus;
use Modules\Students\Models\Student;
use Spatie\Activitylog\Models\Activity;

/**
 * Everything the admin dashboard shows, in one read-only call. Each block is built only when the viewer holds the
 * permission of the module it comes from, so a role that cannot see fees never receives a taka figure.
 */
class DashboardOverview
{
    /** Log subjects worth a line on the dashboard; the rest (invoice rows, fee structures) is bookkeeping noise. */
    private const array NOTABLE_SUBJECTS = ['Payment', 'Student', 'Exam', 'Notice', 'Inquiry', 'StudentConcession', 'Album'];

    /**
     * @return array<string, mixed>
     */
    public function for(User $user, ?CarbonInterface $now = null): array
    {
        $now = Carbon::instance($now ?? now());

        return [
            'students' => $user->can('students.view') ? $this->students() : null,
            'fees' => $user->can('fees.view') ? $this->fees($now) : null,
            'exam' => $user->can('exams.view') ? $this->exam() : null,
            'pending_verifications' => $user->can('fees.verify') || $user->can('fees.view') ? Payment::query()->where('status', PaymentStatus::Pending->value)->count() : null,
            'activity' => $this->activity(),
        ];
    }

    /**
     * @return array{total: int, session: string|null, by_class: list<array{id: int, name: string, count: int}>}
     */
    protected function students(): array
    {
        $counts = Student::query()->where('status', StudentStatus::Active->value)->selectRaw('class_id, count(*) as total')->groupBy('class_id')->pluck('total', 'class_id');

        $byClass = SchoolClass::query()->orderBy('numeric_order')->get()
            ->map(fn (SchoolClass $class): array => ['id' => $class->id, 'name' => $class->displayName(), 'count' => (int) ($counts[$class->id] ?? 0)])
            ->filter(fn (array $row): bool => $row['count'] > 0)->values();

        return [
            'total' => (int) $counts->sum(),
            'session' => AcademicSession::query()->where('is_current', true)->value('name'),
            'by_class' => $byClass->all(),
        ];
    }

    /**
     * Amounts are whole poisha.
     *
     * @return array<string, mixed>
     */
    protected function fees(CarbonInterface $now): array
    {
        $approved = PaymentStatus::Approved->value;
        $open = array_map(fn (InvoiceStatus $status): string => $status->value, InvoiceStatus::open());
        $session = AcademicSession::query()->where('is_current', true)->first();

        $today = Payment::query()->where('status', $approved)->whereBetween('paid_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()]);
        $pending = Payment::query()->where('status', PaymentStatus::Pending->value);
        $outstanding = FeeInvoice::query()->whereIn('status', $open);

        $dueByClass = FeeInvoice::query()->whereIn('status', $open)->selectRaw('class_id, sum(due) as owed')->groupBy('class_id')->orderByDesc('owed')->limit(4)->get();
        $classes = SchoolClass::query()->whereIn('id', $dueByClass->pluck('class_id'))->get()->keyBy('id');

        return [
            'month' => $now->format('Y-m'),
            'expected_this_month' => (int) FeeInvoice::query()->where('type', FeeHeadType::Monthly->value)->whereDate('billing_month', $now->copy()->startOfMonth()->toDateString())->where('status', '!=', InvoiceStatus::Cancelled->value)->sum('total'),
            'collected_this_month' => (int) Payment::query()->where('status', $approved)->whereBetween('paid_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])->sum('amount'),
            'collected_session' => (int) Payment::query()->where('status', $approved)->when($session, fn ($query) => $query->where('paid_at', '>=', $session->start_date->copy()->startOfDay()))->sum('amount'),
            'collected_today' => (int) (clone $today)->sum('amount'),
            'receipts_today' => (clone $today)->count(),
            'pending_amount' => (int) (clone $pending)->sum('amount'),
            'outstanding' => (int) (clone $outstanding)->sum('due'),
            'students_with_dues' => (clone $outstanding)->distinct()->count('student_id'),
            'top_dues' => $dueByClass->map(fn ($row): array => [
                'class' => $classes->get($row->class_id)?->displayName() ?? '—',
                'due' => (int) $row->owed,
            ])->all(),
        ];
    }

    /**
     * @return array{count: int, next: array{id: int, name: string, start_date: string|null}|null}
     */
    protected function exam(): array
    {
        $exams = Exam::query()->whereIn('status', [ExamStatus::Ongoing->value, ExamStatus::Draft->value])->orderBy('start_date')->get();
        $next = $exams->first();

        return [
            'count' => $exams->count(),
            'next' => $next ? ['id' => $next->id, 'name' => app()->getLocale() === 'en' && $next->name_en ? $next->name_en : $next->name_bn, 'start_date' => $next->start_date?->toDateString()] : null,
        ];
    }

    /**
     * @return list<array{text: string, at: string, tone: string}>
     */
    protected function activity(): array
    {
        $notable = collect(self::NOTABLE_SUBJECTS)->map(fn (string $name): string => "%\\{$name}")->all();

        return Activity::query()->with('causer')
            ->where(fn ($query) => $query->whereNull('subject_type')->orWhere(function ($inner) use ($notable): void {
                foreach ($notable as $pattern) {
                    $inner->orWhere('subject_type', 'like', $pattern);
                }
            }))
            ->latest('id')->limit(5)->get()
            ->map(fn (Activity $activity): array => [
                'text' => $this->describe($activity),
                'at' => $activity->created_at->toIso8601String(),
                'tone' => $this->tone($activity),
            ])->all();
    }

    protected function describe(Activity $activity): string
    {
        $subject = $activity->subject_type ? class_basename($activity->subject_type) : null;
        $label = $subject && trans()->has("fees::dashboard.subjects.{$subject}") ? __("fees::dashboard.subjects.{$subject}") : ($subject ?? __('fees::dashboard.subjects.system'));
        $event = (string) ($activity->event ?? $activity->description);
        $verb = trans()->has("fees::dashboard.events.{$event}") ? __("fees::dashboard.events.{$event}") : $event;
        $line = "{$label} {$verb}";

        return $activity->causer?->name ? "{$line} — {$activity->causer->name}" : $line;
    }

    protected function tone(Activity $activity): string
    {
        return match (true) {
            str_ends_with((string) $activity->subject_type, 'Payment') => 'green',
            str_ends_with((string) $activity->subject_type, 'Exam') => 'navy',
            str_ends_with((string) $activity->subject_type, 'Student') => 'yellow',
            default => 'red',
        };
    }
}
