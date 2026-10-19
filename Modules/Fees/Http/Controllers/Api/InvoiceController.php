<?php

namespace Modules\Fees\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Modules\Exams\Models\Exam;
use Modules\Fees\Http\Requests\GenerateExamRequest;
use Modules\Fees\Http\Requests\GenerateMonthlyRequest;
use Modules\Fees\Http\Requests\OneOffInvoiceRequest;
use Modules\Fees\Http\Requests\WaiveRequest;
use Modules\Fees\Http\Resources\InvoiceResource;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Services\InvoiceGenerator;
use Modules\Fees\Services\PaymentService;
use Modules\Fees\Support\Money;
use Modules\Students\Models\Student;

class InvoiceController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->can('fees.view'), 403);

        $invoices = FeeInvoice::query()->with(['student', 'schoolClass', 'items.head'])
            ->when($request->integer('student_id'), fn ($query, int $id) => $query->where('student_id', $id))
            ->when($request->integer('class_id'), fn ($query, int $id) => $query->where('class_id', $id))
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('type')->toString(), fn ($query, string $type) => $query->where('type', $type))
            ->when($request->string('month')->toString(), fn ($query, string $month) => $query->whereDate('billing_month', Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString()))
            ->when($request->string('search')->toString(), fn ($query, string $search) => $query->where(fn ($inner) => $inner->where('invoice_no', 'like', "%{$search}%")->orWhereHas('student', fn ($student) => $student->where('name_bn', 'like', "%{$search}%")->orWhere('student_id', 'like', "%{$search}%"))))
            ->orderByDesc('id')->paginate($request->integer('per_page') > 0 ? min($request->integer('per_page'), 100) : 20);

        return InvoiceResource::collection($invoices);
    }

    public function show(Request $request, FeeInvoice $invoice): InvoiceResource
    {
        abort_unless($request->user()->can('fees.view'), 403);

        return new InvoiceResource($invoice->load(['student', 'schoolClass', 'items.head', 'exam']));
    }

    /**
     * Preview first (confirm = false writes nothing and returns the counts), then confirm.
     */
    public function generateMonthly(GenerateMonthlyRequest $request, InvoiceGenerator $generator): JsonResponse
    {
        $summary = $generator->generateMonthly(Carbon::createFromFormat('Y-m', $request->string('month')->toString())->startOfMonth(), $request->input('class_ids') ?: null, ! $request->boolean('confirm'), $request->filled('due_date') ? Carbon::parse($request->string('due_date')->toString()) : null);

        return response()->json(['data' => $summary->toArray()]);
    }

    public function generateExam(GenerateExamRequest $request, InvoiceGenerator $generator): JsonResponse
    {
        $summary = $generator->generateExam(Exam::query()->findOrFail($request->integer('exam_id')), ! $request->boolean('confirm'));

        return response()->json(['data' => $summary->toArray()]);
    }

    public function store(OneOffInvoiceRequest $request, InvoiceGenerator $generator): JsonResponse
    {
        $items = array_map(fn (array $item): array => [
            'fee_head_id' => (int) $item['fee_head_id'],
            'amount' => isset($item['amount']) && $item['amount'] !== '' ? Money::taka($item['amount']) : null,
            'description' => $item['description'] ?? null,
        ], $request->validated('items'));

        $invoice = $generator->generateOneOff(Student::query()->findOrFail($request->integer('student_id')), $items, $request->filled('due_date') ? Carbon::parse($request->string('due_date')->toString()) : null);

        return (new InvoiceResource($invoice->load(['student', 'schoolClass', 'items.head'])))->response()->setStatusCode(201);
    }

    public function waive(WaiveRequest $request, FeeInvoice $invoice, PaymentService $payments): InvoiceResource
    {
        return new InvoiceResource($payments->waive($invoice, $request->user(), $request->string('reason')->toString())->load(['student', 'schoolClass', 'items.head']));
    }
}
