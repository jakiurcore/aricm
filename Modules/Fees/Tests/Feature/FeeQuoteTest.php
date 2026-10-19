<?php

use Illuminate\Support\Carbon;
use Modules\Fees\Models\FeeStructure;
use Modules\Fees\Models\StudentConcession;
use Modules\Fees\Services\FeeCalculator;
use Modules\Students\Enums\ResidencyType;

it('quotes a student from the database with residency and concessions', function () {
    $p = placement();
    $heads = feeSetup($p);
    FeeStructure::create(['session_id' => $p['session']->id, 'class_id' => $p['class']->id, 'fee_head_id' => $heads['tuition']->id, 'residency_type' => ResidencyType::Residential, 'amount' => taka(2000)]);
    $student = enrolled($p, ['residency_type' => 'residential']);
    StudentConcession::create(['student_id' => $student->id, 'fee_head_id' => $heads['tuition']->id, 'kind' => 'percent', 'value' => 50, 'reason_type' => 'orphan', 'valid_from_month' => '2026-01-01']);

    $quote = (new FeeCalculator)->quote($student, $heads['tuition']->id, Carbon::parse('2026-03-01'));

    expect($quote['gross']->poisha)->toBe(200000)->and($quote['discount']->poisha)->toBe(100000)->and($quote['net']->poisha)->toBe(100000)
        ->and((new FeeCalculator)->quote($student, 9999, Carbon::parse('2026-03-01')))->toBeNull();
});
