<?php

namespace Modules\Fees\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Modules\Core\Http\Controllers\Concerns\ServesPdf;
use Modules\Fees\Services\FeeReports;
use Modules\Fees\Services\ReportExporter;
use Modules\Fees\Support\ReportTable;
use Modules\Students\Models\Student;

class ReportController
{
    use ServesPdf;

    public const array REPORTS = ['cash-book', 'monthly', 'head-wise', 'dues', 'collectors', 'ledger'];

    public function show(Request $request, string $report, FeeReports $reports): Response|JsonResponse
    {
        return response()->json(['data' => $this->table($request, $report, $reports)->toArray()]);
    }

    public function export(Request $request, string $report, FeeReports $reports, ReportExporter $exporter): Response
    {
        $table = $this->table($request, $report, $reports);
        $format = $request->string('format')->toString();

        if ($format === 'xlsx') {
            return response($exporter->xlsx($table), 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="'.$report.'.xlsx"']);
        }

        return $this->pdf($exporter->pdf($table), $report.'.pdf');
    }

    protected function table(Request $request, string $report, FeeReports $reports): ReportTable
    {
        abort_unless($request->user()->can('fees.reports') && in_array($report, self::REPORTS, true), $request->user()->can('fees.reports') ? 404 : 403);

        $range = fn (): array => [Carbon::parse($request->input('from', now()->startOfMonth()->toDateString())), Carbon::parse($request->input('to', now()->toDateString()))];

        return match ($report) {
            'cash-book' => $reports->cashBook(...$range()),
            'monthly' => $reports->monthlyCollection(Carbon::createFromFormat('Y-m', $request->input('month', now()->format('Y-m')))->startOfMonth()),
            'head-wise' => $reports->headWise(...$range()),
            'dues' => $reports->dues($request->integer('class_id') ?: null, $request->filled('up_to') ? Carbon::parse($request->string('up_to')->toString()) : null),
            'collectors' => $reports->collectors(...$range()),
            'ledger' => $reports->studentLedger(Student::query()->findOrFail($request->integer('student_id'))),
        };
    }
}
