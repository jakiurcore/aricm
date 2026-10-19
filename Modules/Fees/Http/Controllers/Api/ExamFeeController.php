<?php

namespace Modules\Fees\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Exams\Models\Exam;
use Modules\Fees\Http\Requests\ExamFeeRequest;
use Modules\Fees\Models\ExamFeeAmount;
use Modules\Fees\Support\Money;

class ExamFeeController
{
    public function show(Request $request, Exam $exam): JsonResponse
    {
        abort_unless($request->user()->can('fees.view'), 403);

        return response()->json(['data' => $this->rows($exam)]);
    }

    /**
     * Amount per class for this exam's fee. Only classes taking the exam are accepted; an empty amount removes the row.
     */
    public function update(ExamFeeRequest $request, Exam $exam): JsonResponse
    {
        $classIds = $exam->classes()->pluck('classes.id')->all();

        foreach ($request->validated('rows') as $row) {
            abort_unless(in_array((int) $row['class_id'], $classIds, true), 422, __('fees::messages.class_not_in_exam'));

            $amount = isset($row['amount']) && $row['amount'] !== '' ? Money::taka($row['amount']) : Money::zero();
            $key = ['exam_id' => $exam->id, 'class_id' => (int) $row['class_id']];

            if ($amount->isZero()) {
                ExamFeeAmount::query()->where($key)->delete();

                continue;
            }

            ExamFeeAmount::query()->updateOrCreate($key, ['amount' => $amount, 'due_date' => $row['due_date'] ?? null]);
        }

        return response()->json(['data' => $this->rows($exam)]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function rows(Exam $exam): array
    {
        $amounts = ExamFeeAmount::query()->where('exam_id', $exam->id)->get()->keyBy('class_id');

        return $exam->classes->map(fn ($class): array => [
            'class_id' => $class->id, 'class_name' => $class->displayName(),
            'amount' => $amounts->get($class->id)?->amount->poisha ?? 0, 'due_date' => $amounts->get($class->id)?->due_date?->toDateString(),
        ])->values()->all();
    }
}
