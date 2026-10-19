<?php

use Illuminate\Routing\Router;
use Modules\Accounts\Http\Middleware\EnsureAdminAccess;
use Modules\Fees\Http\Controllers\Api\CollectionController;
use Modules\Fees\Http\Controllers\Api\ConcessionController;
use Modules\Fees\Http\Controllers\Api\ExamFeeController;
use Modules\Fees\Http\Controllers\Api\FeeHeadController;
use Modules\Fees\Http\Controllers\Api\FeeStructureController;
use Modules\Fees\Http\Controllers\Api\InvoiceController;
use Modules\Fees\Http\Controllers\Api\OverviewController;
use Modules\Fees\Http\Controllers\Api\PaymentController;
use Modules\Fees\Http\Controllers\Api\ReportController;
use Modules\Fees\Http\Controllers\Api\SettingsController;

/** @var Router $router */
$router->middleware(['auth', EnsureAdminAccess::class])->prefix('fees')->name('fees.api.')->group(function (Router $router): void {
    $router->get('dashboard', [SettingsController::class, 'dashboard'])->name('dashboard');
    $router->get('overview', OverviewController::class)->name('overview');
    $router->get('settings', [SettingsController::class, 'show'])->name('settings.show');
    $router->put('settings', [SettingsController::class, 'update'])->name('settings.update');

    $router->apiResource('heads', FeeHeadController::class)->except('show');

    $router->get('structures', [FeeStructureController::class, 'index'])->name('structures.index');
    $router->put('structures', [FeeStructureController::class, 'update'])->name('structures.update');
    $router->post('structures/copy', [FeeStructureController::class, 'copy'])->name('structures.copy');

    $router->get('exams/{exam}/fees', [ExamFeeController::class, 'show'])->name('exam-fees.show');
    $router->put('exams/{exam}/fees', [ExamFeeController::class, 'update'])->name('exam-fees.update');

    $router->apiResource('concessions', ConcessionController::class)->except('show');

    $router->get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    $router->post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    $router->post('invoices/generate/monthly', [InvoiceController::class, 'generateMonthly'])->name('invoices.generate.monthly');
    $router->post('invoices/generate/exam', [InvoiceController::class, 'generateExam'])->name('invoices.generate.exam');
    $router->get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    $router->post('invoices/{invoice}/waive', [InvoiceController::class, 'waive'])->name('invoices.waive');

    $router->get('collection/search', [CollectionController::class, 'search'])->name('collection.search');
    $router->get('collection/students/{student}', [CollectionController::class, 'student'])->name('collection.student');
    $router->get('collection/students/{student}/due-notice', [CollectionController::class, 'dueNotice'])->name('collection.due-notice');
    $router->post('collection/pay', [CollectionController::class, 'pay'])->name('collection.pay');

    $router->get('payments', [PaymentController::class, 'index'])->name('payments.index');
    $router->get('payments/pending-count', [PaymentController::class, 'pendingCount'])->name('payments.pending-count');
    $router->post('payments/{payment}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
    $router->post('payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
    $router->get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');

    $router->get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    $router->get('reports/{report}/export', [ReportController::class, 'export'])->name('reports.export');
});
