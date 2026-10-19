<?php

use Illuminate\Routing\Router;
use Modules\Admission\Http\Controllers\PublicAdmissionController;

/** @var Router $router */
$router->get('/admission', [PublicAdmissionController::class, 'show'])->name('admission.public');
// Five inquiries per ten minutes from one address.
$router->post('/admission/inquiries', [PublicAdmissionController::class, 'store'])->middleware('throttle:5,10')->name('admission.inquiry.store');
