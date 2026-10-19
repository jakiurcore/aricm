<?php

use Illuminate\Routing\Router;
use Modules\Results\Http\Controllers\PublicResultController;
use Modules\Results\Http\Controllers\TeacherMarksController;

/** @var Router $router */
$router->get('/results', [PublicResultController::class, 'index'])->name('results.public');
$router->post('/results', [PublicResultController::class, 'search'])->middleware('throttle:20,1')->name('results.public.search');

// Teacher marks entry (JSON). Needs marks.enter_assigned and the teacher_marks_entry_enabled setting; narrowed by TeacherScope.
$router->middleware('auth')->prefix('teacher/api/marks')->name('results.teacher.')->group(function (Router $router): void {
    $router->get('options', [TeacherMarksController::class, 'options'])->name('options');
    $router->get('{exam}/{class}/{section}', [TeacherMarksController::class, 'show'])->whereNumber(['exam', 'class', 'section'])->name('show');
    $router->put('{exam}/{class}/{section}', [TeacherMarksController::class, 'update'])->whereNumber(['exam', 'class', 'section'])->name('update');
});
