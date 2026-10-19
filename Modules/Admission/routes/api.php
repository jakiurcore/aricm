<?php

use Illuminate\Routing\Router;
use Modules\Admission\Http\Controllers\Api\InquiryController;

/** @var Router $router */
$router->middleware(['auth', 'can:admin.access'])->prefix('admission')->name('admission.api.')->group(function (Router $router): void {
    $router->get('inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
    $router->put('inquiries/{inquiry}', [InquiryController::class, 'update'])->name('inquiries.update');
    $router->delete('inquiries/{inquiry}', [InquiryController::class, 'destroy'])->name('inquiries.destroy');
    $router->get('settings', [InquiryController::class, 'settings'])->name('settings.show');
    $router->put('settings', [InquiryController::class, 'updateSettings'])->name('settings.update');
});
