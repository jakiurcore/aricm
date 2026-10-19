<?php

use Illuminate\Routing\Router;
use Modules\Accounts\Http\Middleware\AuthenticateStudent;
use Modules\StudentPortal\Http\Controllers\DashboardController;
use Modules\StudentPortal\Http\Controllers\ExamController;
use Modules\StudentPortal\Http\Controllers\FeesController;
use Modules\StudentPortal\Http\Controllers\NoticeController;
use Modules\StudentPortal\Http\Controllers\ProfileController;
use Modules\StudentPortal\Http\Controllers\ResultController;

/** @var Router $router */
$router->middleware(AuthenticateStudent::class)->prefix('portal')->name('portal.')->group(function (Router $router): void {
    $router->get('/', DashboardController::class)->name('dashboard');
    $router->post('notifications/read', [DashboardController::class, 'readNotifications'])->name('notifications.read');

    $router->get('profile', [ProfileController::class, 'show'])->name('profile');
    $router->get('id-card', [ProfileController::class, 'idCard'])->name('idcard');

    $router->get('routine', [ExamController::class, 'routine'])->name('routine');
    $router->get('admit-card', [ExamController::class, 'admit'])->name('admit');
    $router->get('admit-card/{exam}/pdf', [ExamController::class, 'admitPdf'])->whereNumber('exam')->name('admit.pdf');

    $router->get('fees', [FeesController::class, 'index'])->name('fees');
    $router->get('fees/pay', [FeesController::class, 'pay'])->name('fees.pay');
    // Manual payment submissions are rate limited: six an hour per student.
    $router->post('fees/pay', [FeesController::class, 'payStore'])->middleware('throttle:6,60')->name('fees.pay.store');
    $router->get('fees/payments', [FeesController::class, 'payments'])->name('fees.payments');
    $router->get('fees/payments/{payment}/receipt', [FeesController::class, 'receipt'])->whereNumber('payment')->name('fees.receipt');
    $router->get('fees/ledger', [FeesController::class, 'ledger'])->name('fees.ledger');
    $router->get('fees/invoices/{invoice}', [FeesController::class, 'invoice'])->whereNumber('invoice')->name('fees.invoice');
    $router->get('fees/invoices/{invoice}/pdf', [FeesController::class, 'invoicePdf'])->whereNumber('invoice')->name('fees.invoice.pdf');

    $router->get('notices', [NoticeController::class, 'index'])->name('notices');
    $router->get('notices/{notice}', [NoticeController::class, 'show'])->whereNumber('notice')->name('notices.show');

    $router->get('results', [ResultController::class, 'index'])->name('results');
    $router->get('results/{exam}', [ResultController::class, 'show'])->whereNumber('exam')->name('results.show');
    $router->get('results/{exam}/pdf', [ResultController::class, 'marksheetPdf'])->whereNumber('exam')->name('results.pdf');
});
