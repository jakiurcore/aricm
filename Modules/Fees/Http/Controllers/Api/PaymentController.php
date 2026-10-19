<?php

namespace Modules\Fees\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Modules\Core\Http\Controllers\Concerns\ServesPdf;
use Modules\Fees\Http\Requests\RejectPaymentRequest;
use Modules\Fees\Http\Resources\PaymentResource;
use Modules\Fees\Models\Payment;
use Modules\Fees\Services\PaymentService;
use Modules\Fees\Services\ReceiptService;

class PaymentController
{
    use ServesPdf;

    /**
     * The verification queue when status=pending; also the payment history.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->can('fees.view'), 403);

        $payments = Payment::query()->with(['student.schoolClass', 'receiver', 'verifier'])
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('method')->toString(), fn ($query, string $method) => $query->where('method', $method))
            ->when($request->integer('student_id'), fn ($query, int $id) => $query->where('student_id', $id))
            ->when($request->string('search')->toString(), fn ($query, string $search) => $query->where(fn ($inner) => $inner->where('transaction_id', 'like', "%{$search}%")->orWhere('receipt_no', 'like', "%{$search}%")->orWhereHas('student', fn ($student) => $student->where('name_bn', 'like', "%{$search}%")->orWhere('student_id', 'like', "%{$search}%"))))
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")->orderByDesc('id')->paginate(20);

        return PaymentResource::collection($payments);
    }

    public function approve(Request $request, Payment $payment, PaymentService $service): PaymentResource
    {
        abort_unless($request->user()->can('fees.verify'), 403);

        return new PaymentResource($service->approve($payment, $request->user())->load('student.schoolClass'));
    }

    public function reject(RejectPaymentRequest $request, Payment $payment, PaymentService $service): PaymentResource
    {
        return new PaymentResource($service->reject($payment, $request->user(), $request->string('reason')->toString())->load('student.schoolClass'));
    }

    public function receipt(Request $request, Payment $payment, ReceiptService $receipts): Response
    {
        abort_unless($request->user()->can('fees.view'), 403);

        $format = $request->string('format')->toString() === ReceiptService::THERMAL ? ReceiptService::THERMAL : ReceiptService::A5;

        return $this->pdf($receipts->pdf($payment, $format), "receipt-{$payment->receipt_no}.pdf");
    }

    public function pendingCount(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('fees.verify'), 403);

        return response()->json(['data' => ['pending' => Payment::query()->where('status', 'pending')->count()]]);
    }
}
