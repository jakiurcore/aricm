<?php

namespace Modules\Fees\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Modules\Fees\Enums\ConcessionKind;
use Modules\Fees\Models\FeeStructure;
use Modules\Fees\Models\StudentConcession;
use Modules\Fees\Support\ConcessionRule;
use Modules\Fees\Support\Money;
use Modules\Students\Enums\ResidencyType;
use Modules\Students\Models\Student;

/**
 * What a student owes for a fee head in a month: the structure amount (a residency-specific row wins over the generic
 * one) minus the concessions valid that month. `discount()` and `structureAmount()` are pure; `quote()` reads the
 * database.
 */
class FeeCalculator
{
    /**
     * The discount on a gross amount. Every applicable concession is worked out on the gross and they add up, never
     * beyond the gross itself. Percent concessions round down to a whole poisha.
     *
     * @param  iterable<ConcessionRule>  $rules
     */
    public function discount(Money $gross, iterable $rules, int $headId, CarbonInterface $month): Money
    {
        $total = Money::zero();

        foreach ($rules as $rule) {
            if (! $rule->appliesTo($headId, $month)) {
                continue;
            }

            $total = $total->plus(match ($rule->kind) {
                ConcessionKind::Percent => $gross->percent(min(100, $rule->value)),
                ConcessionKind::Fixed => Money::poisha($rule->value)->min($gross),
            });
        }

        return $total->min($gross);
    }

    /**
     * @param  iterable<FeeStructure>  $structures  Rows already limited to one session and class.
     */
    public function structureAmount(iterable $structures, int $headId, ?ResidencyType $residency): ?Money
    {
        $generic = null;

        foreach ($structures as $structure) {
            if ($structure->fee_head_id !== $headId) {
                continue;
            }

            if ($residency !== null && $structure->residency_type === $residency) {
                return $structure->amount;
            }

            if ($structure->residency_type === null) {
                $generic = $structure->amount;
            }
        }

        return $generic;
    }

    /**
     * @param  Collection<int, FeeStructure>|null  $structures  Preloaded rows for the student's session/class, to avoid a query per student.
     * @param  Collection<int, StudentConcession>|null  $concessions  Preloaded concessions of this student.
     * @return array{gross: Money, discount: Money, net: Money}|null Null when no fee is set for the head.
     */
    public function quote(Student $student, int $headId, CarbonInterface $month, ?Collection $structures = null, ?Collection $concessions = null): ?array
    {
        $structures ??= FeeStructure::query()->where(['session_id' => $student->session_id, 'class_id' => $student->class_id, 'fee_head_id' => $headId])->get();
        $gross = $this->structureAmount($structures, $headId, $student->residency_type);

        if ($gross === null) {
            return null;
        }

        $concessions ??= StudentConcession::query()->where('student_id', $student->id)->get();
        $discount = $this->discount($gross, $concessions->map(fn (StudentConcession $concession): ConcessionRule => ConcessionRule::fromModel($concession)), $headId, $month);

        return ['gross' => $gross, 'discount' => $discount, 'net' => $gross->minus($discount)];
    }
}
