<?php

use Illuminate\Routing\Router;
use Modules\TeacherPanel\Http\Controllers\DiaryController;
use Modules\TeacherPanel\Http\Controllers\HomeController;
use Modules\TeacherPanel\Http\Controllers\MarksController;
use Modules\TeacherPanel\Http\Controllers\ProfileController;
use Modules\TeacherPanel\Http\Controllers\RemarksController;
use Modules\TeacherPanel\Http\Middleware\EnsureTeacher;

/** @var Router $router */
$router->middleware(['auth', EnsureTeacher::class])->prefix('teacher')->group(function (Router $router): void {
    $router->get('/', HomeController::class)->name('teacher.home');

    $router->get('diary', [DiaryController::class, 'index'])->name('teacher.diary');
    $router->get('diary/write', [DiaryController::class, 'write'])->name('teacher.diary.write');
    $router->get('diary/previous', [DiaryController::class, 'previous'])->name('teacher.diary.previous');
    $router->get('class-day', [DiaryController::class, 'classDay'])->name('teacher.class-day');
    $router->post('class-day/remind', [DiaryController::class, 'remind'])->middleware('throttle:6,1')->name('teacher.class-day.remind');
    $router->post('class-day/sms', [DiaryController::class, 'notifyGuardians'])->middleware('throttle:6,1')->name('teacher.class-day.sms');

    $router->get('remarks', [RemarksController::class, 'index'])->name('teacher.remarks');
    $router->post('remarks', [RemarksController::class, 'store'])->name('teacher.remarks.store');

    $router->get('profile', [ProfileController::class, 'show'])->name('teacher.profile');
    $router->get('password', [ProfileController::class, 'editPassword'])->name('teacher.password');
    $router->put('password', [ProfileController::class, 'updatePassword'])->name('teacher.password.update');

    $router->get('marks', [MarksController::class, 'index'])->name('teacher.marks');
    $router->get('marks/{exam}/{class}/{section}', [MarksController::class, 'entry'])->whereNumber(['exam', 'class', 'section'])->name('teacher.marks.entry');
});
