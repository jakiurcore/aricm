<?php

namespace Modules\Fees\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Core\Http\Controllers\Concerns\ServesPdf;
use Modules\Fees\Enums\PaymentMethod;
use Modules\Fees\Http\Requests\CollectRequest;
use Modules\Fees\Http\Resources\InvoiceResource;
use Modules\Fees\Http\Resources\PaymentResource;
use Modules\Fees\Services\InvoicePdf;
use Modules\Fees\Services\PaymentService;
use Modules\Fees\Services\StudentFeeDesk;
use Modules\Fees\Support\Money;
use Modules\Students\Enums\StudentStatus;
use Modules\Students\Models\Student;
use Modules\Students\Services\StudentDirectory;

/**
 * The collection desk: find a student, see what they owe, record the money.
 */
class CollectionController
{
    use ServesPdf;

    public function search(Request $request, StudentDirectory $students, StudentFeeDesk $desk): JsonResponse
    {
        abort_unless($request->user()->can('fees.collect'), 403);

        $term = trim($request->string('q')->toString());
        abort_if(mb_strlen($term) < 2, 422, __('fees::messages.search_too_short'));

        $found = $students->query(['search' => $term, 'status' => StudentStatus::Active->value])->with('schoolClass')->orderBy('name_bn')->limit(10)->get();

        return response()->json(['data' => $found->map(fn (Student $student): array => [
            'id' => $student->id, 'student_id' => $student->student_id, 'name' => $student->name_bn, 'class' => $student->schoolClass?->displayName(), 'roll' => $student->roll,
            'guardian_phone' => $student->guardian_phone, 'photo_url' => $student->photo_url, 'due' => $desk->totalDue($student)->poisha,
        ])->values()]);
    }

    /**
     * The printed "fees overdue" letter for one student, listing every open invoice.
     */
    public function dueNotice(Request $request, Student $student, InvoicePdf $pdf): Response
    {
        abort_unless($request->user()->can('fees.collect'), 403);

        return $this->pdf($pdf->dueNotice($student), "due-notice-{$student->student_id}.pdf");
    }

    public function student(Request $request, Student $student, StudentFeeDesk $desk): JsonResponse
    {
        abort_unless($request->user()->can('fees.collect'), 403);

        return response()->json(['data' => [
            'student' => ['id' => $student->id, 'student_id' => $student->student_id, 'name' => $student->name_bn, 'class' => $student->schoolClass?->displayName(), 'roll' => $student->roll, 'guardian_phone' => $student->guardian_phone, 'photo_url' => $student->photo_url],
            'invoices' => InvoiceResource::collection($desk->openInvoices($student))->resolve(),
            'credit' => $desk->credit($student)->poisha,
            'total_due' => $desk->totalDue($student)->poisha,
            'recent_payments' => PaymentResource::collection($desk->payments($student)->take(5))->resolve(),
        ]]);
    }

    public function pay(CollectRequest $request, PaymentService $payments): JsonResponse
    {
        $student = Student::query()->findOrFail($request->integer('student_id'));

        $payment = $payments->collectAtCounter(
            $student, Money::taka($request->input('amount')), PaymentMethod::from($request->string('method')->toString()),
            array_map('intval', $request->input('invoice_ids', [])), $request->user(), $request->input('transaction_id'), $request->input('note'),
        );

        return response()->json(['data' => [...(new PaymentResource($payment->load('student')))->resolve(), 'receipt_url' => route('fees.api.payments.receipt', $payment)]], 201);
    }
}
