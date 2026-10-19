<?php

namespace Modules\Fees\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Fees\Enums\FeeHeadType;
use Modules\Fees\Http\Requests\CopyStructureRequest;
use Modules\Fees\Http\Requests\StructureRequest;
use Modules\Fees\Models\FeeHead;
use Modules\Fees\Models\FeeStructure;
use Modules\Fees\Support\Money;

class FeeStructureController
{
    /**
     * The editable matrix for one session: rows keyed by class, head and (optional) residency.
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('fees.view'), 403);

        $sessionId = $request->validate(['session_id' => ['required', 'integer']])['session_id'];

        return response()->json(['data' => FeeStructure::query()->where('session_id', $sessionId)->get()->map(fn (FeeStructure $row): array => [
            'class_id' => $row->class_id, 'fee_head_id' => $row->fee_head_id, 'residency_type' => $row->residency_type?->value, 'amount' => $row->amount->poisha,
        ])->values()]);
    }

    /**
     * Upsert cells. An empty or zero amount removes the row. Exam heads are priced per exam, not here.
     */
    public function update(StructureRequest $request): JsonResponse
    {
        $sessionId = $request->integer('session_id');
        $examHeads = FeeHead::query()->where('type', FeeHeadType::Exam->value)->pluck('id')->all();

        DB::transaction(function () use ($request, $sessionId, $examHeads): void {
            foreach ($request->validated('rows') as $row) {
                abort_if(in_array($row['fee_head_id'], $examHeads, true), 422, __('fees::messages.exam_head_priced_per_exam'));

                $key = ['session_id' => $sessionId, 'class_id' => $row['class_id'], 'fee_head_id' => $row['fee_head_id'], 'scope' => $row['residency_type'] ?? 'all'];
                $existing = FeeStructure::query()->where($key)->first();
                $amount = isset($row['amount']) && $row['amount'] !== '' ? Money::taka($row['amount']) : Money::zero();

                if ($amount->isZero()) {
                    $existing?->delete();

                    continue;
                }

                $existing ? $existing->update(['amount' => $amount]) : FeeStructure::create(['session_id' => $sessionId, 'class_id' => $row['class_id'], 'fee_head_id' => $row['fee_head_id'], 'residency_type' => $row['residency_type'] ?? null, 'amount' => $amount]);
            }
        });

        return $this->index($request);
    }

    /**
     * Copy every row of one session to another, leaving rows the target already has untouched.
     */
    public function copy(CopyStructureRequest $request): JsonResponse
    {
        $copied = 0;

        DB::transaction(function () use ($request, &$copied): void {
            foreach (FeeStructure::query()->where('session_id', $request->integer('from_session_id'))->get() as $row) {
                $created = FeeStructure::query()->firstOrCreate(
                    ['session_id' => $request->integer('to_session_id'), 'class_id' => $row->class_id, 'fee_head_id' => $row->fee_head_id, 'scope' => $row->scope],
                    ['residency_type' => $row->residency_type, 'amount' => $row->amount],
                );
                $copied += $created->wasRecentlyCreated ? 1 : 0;
            }
        });

        return response()->json(['data' => ['copied' => $copied]]);
    }
}
