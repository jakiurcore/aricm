<?php

use Illuminate\Routing\Router;
use Modules\Accounts\Http\Middleware\EnsureAdminAccess;
use Modules\Results\Http\Controllers\Api\GradingScaleController;
use Modules\Results\Http\Controllers\Api\MarksController;
use Modules\Results\Http\Controllers\Api\ProcessingController;

/** @var Router $router */
$router->middleware(['auth', EnsureAdminAccess::class])->prefix('results')->name('results.api.')->group(function (Router $router): void {
    $router->get('overview', [ProcessingController::class, 'overview'])->name('overview');

    $router->get('exams/{exam}/classes/{class}/marks', [MarksController::class, 'show'])->name('marks.show');
    $router->put('exams/{exam}/classes/{class}/marks', [MarksController::class, 'update'])->name('marks.update');

    $router->post('exams/{exam}/process', [ProcessingController::class, 'process'])->name('process');
    $router->get('exams/{exam}/classes/{class}/preview', [ProcessingController::class, 'preview'])->name('preview');
    $router->post('exams/{exam}/publish', [ProcessingController::class, 'publish'])->name('publish');
    $router->post('exams/{exam}/unpublish', [ProcessingController::class, 'unpublish'])->name('unpublish');

    $router->get('grading-scales', [GradingScaleController::class, 'index'])->name('scales.index');
    $router->post('grading-scales', [GradingScaleController::class, 'store'])->name('scales.store');
    $router->put('grading-scales/{scale}', [GradingScaleController::class, 'update'])->name('scales.update');
    $router->delete('grading-scales/{scale}', [GradingScaleController::class, 'destroy'])->name('scales.destroy');
});
