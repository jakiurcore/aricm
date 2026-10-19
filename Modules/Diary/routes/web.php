<?php

use Illuminate\Routing\Router;
use Modules\Diary\Http\Controllers\TeacherDiaryController;

/** @var Router $router */
// JSON endpoints for the teacher panel's diary pages. Writing needs the diary.write permission (the teacher role has it).
$router->middleware(['auth', 'can:diary.write'])->prefix('teacher/diary')->name('diary.teacher.')->group(function (Router $router): void {
    $router->get('entry', [TeacherDiaryController::class, 'show'])->name('show');
    $router->put('entry', [TeacherDiaryController::class, 'save'])->name('save');
    $router->post('entries/{entry}/publish', [TeacherDiaryController::class, 'publish'])->whereNumber('entry')->name('publish');
    $router->delete('entries/{entry}', [TeacherDiaryController::class, 'destroy'])->whereNumber('entry')->name('destroy');
    $router->post('entries/{entry}/attachments', [TeacherDiaryController::class, 'attach'])->whereNumber('entry')->name('attach');
    $router->delete('attachments/{attachment}', [TeacherDiaryController::class, 'detach'])->whereNumber('attachment')->name('detach');
    $router->post('entries/{entry}/remarks', [TeacherDiaryController::class, 'remark'])->whereNumber('entry')->name('remark');
    $router->delete('remarks/{remark}', [TeacherDiaryController::class, 'unremark'])->whereNumber('remark')->name('unremark');
    $router->get('phrases', [TeacherDiaryController::class, 'phrases'])->name('phrases');
    $router->post('phrases', [TeacherDiaryController::class, 'addPhrase'])->name('phrases.add');
    $router->delete('phrases/{phrase}', [TeacherDiaryController::class, 'removePhrase'])->whereNumber('phrase')->name('phrases.remove');
});
