<?php

namespace Modules\Fees\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Fees\Enums\ConcessionKind;
use Modules\Fees\Http\Requests\ConcessionRequest;
use Modules\Fees\Models\StudentConcession;
use Modules\Fees\Support\Money;

class ConcessionController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('fees.view'), 403);

        $concessions = StudentConcession::query()->with(['student', 'head'])
            ->when($request->integer('student_id'), fn ($query, int $id) => $query->where('student_id', $id))
            ->when($request->string('search')->toString(), fn ($query, string $search) => $query->whereHas('student', fn ($inner) => $inner->where('name_bn', 'like', "%{$search}%")->orWhere('student_id', 'like', "%{$search}%")))
            ->orderByDesc('id')->limit(200)->get();

        return response()->json(['data' => $concessions->map(fn (StudentConcession $concession): array => $this->present($concession))->values()]);
    }

    public function store(ConcessionRequest $request): JsonResponse
    {
        $concession = StudentConcession::create($this->attributes($request) + ['approved_by' => $request->user()->id]);

        return response()->json(['data' => $this->present($concession->load(['student', 'head']))], 201);
    }

    public function update(ConcessionRequest $request, StudentConcession $concession): JsonResponse
    {
        $concession->update($this->attributes($request) + ['approved_by' => $request->user()->id]);

        return response()->json(['data' => $this->present($concession->load(['student', 'head']))]);
    }

    public function destroy(Request $request, StudentConcession $concession): JsonResponse
    {
        abort_unless($request->user()->can('fees.concession'), 403);

        $concession->delete();

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    protected function attributes(ConcessionRequest $request): array
    {
        $kind = ConcessionKind::from($request->string('kind')->toString());

        return [
            'student_id' => $request->integer('student_id'),
            'fee_head_id' => $request->integer('fee_head_id') ?: null,
            'kind' => $kind,
            'value' => $kind === ConcessionKind::Percent ? (int) round((float) $request->input('value')) : Money::taka($request->input('value'))->poisha,
            'reason_type' => $request->input('reason_type'),
            'note' => $request->input('note'),
            'valid_from_month' => Carbon::createFromFormat('Y-m', $request->string('valid_from_month')->toString())->startOfMonth(),
            'valid_to_month' => $request->filled('valid_to_month') ? Carbon::createFromFormat('Y-m', $request->string('valid_to_month')->toString())->startOfMonth() : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function present(StudentConcession $concession): array
    {
        return [
            'id' => $concession->id,
            'student' => ['id' => $concession->student->id, 'student_id' => $concession->student->student_id, 'name' => $concession->student->name_bn],
            'fee_head_id' => $concession->fee_head_id, 'head' => $concession->head?->displayName(),
            'kind' => $concession->kind->value, 'value' => $concession->value, 'reason_type' => $concession->reason_type->value, 'note' => $concession->note,
            'valid_from_month' => $concession->valid_from_month->format('Y-m'), 'valid_to_month' => $concession->valid_to_month?->format('Y-m'),
        ];
    }
}
