<?php

use Illuminate\Routing\Router;
use Modules\Results\Http\Controllers\PublicResultController;

/** @var Router $router */
$router->get('/results', [PublicResultController::class, 'index'])->name('results.public');
$router->post('/results', [PublicResultController::class, 'search'])->middleware('throttle:20,1')->name('results.public.search');
