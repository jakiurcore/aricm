<?php

use Illuminate\Routing\Router;
use Modules\TeacherPanel\Http\Controllers\DiaryController;
use Modules\TeacherPanel\Http\Controllers\HomeController;
use Modules\TeacherPanel\Http\Controllers\MarksController;
use Modules\TeacherPanel\Http\Middleware\EnsureTeacher;

/** @var Router $router */
$router->middleware(['auth', EnsureTeacher::class])->prefix('teacher')->group(function (Router $router): void {
    $router->get('/', HomeController::class)->name('teacher.home');

    $router->get('diary', [DiaryController::class, 'index'])->name('teacher.diary');
    $router->get('diary/write', [DiaryController::class, 'write'])->name('teacher.diary.write');
    $router->get('class-day', [DiaryController::class, 'classDay'])->name('teacher.class-day');

    $router->get('marks', [MarksController::class, 'index'])->name('teacher.marks');
    $router->get('marks/{exam}/{class}/{section}', [MarksController::class, 'entry'])->whereNumber(['exam', 'class', 'section'])->name('teacher.marks.entry');
});
