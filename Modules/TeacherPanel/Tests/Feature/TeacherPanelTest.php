<?php

use Modules\Core\Services\Settings;
use Modules\Diary\Services\DiaryService;
use Modules\Exams\Models\ExamSubject;
use Modules\TeacherPanel\Services\TeacherMenu;
use Modules\Teachers\Models\Teacher;
use Modules\Teachers\Services\TeacherAssignments;
use Modules\Teachers\Services\TeacherScope;

it('sends a guest to sign in and keeps non-teachers out', function () {
    $this->get(route('teacher.home'))->assertRedirect(route('login'));

    $this->actingAs(userWithRole('accountant'))->get(route('teacher.home'))->assertForbidden();
});

it('keeps a teacher whose record is inactive out', function () {
    $s = diarySetup();
    $s['teacher']->update(['is_active' => false]);

    $this->actingAs($s['teacher']->user)->get(route('teacher.home'))->assertForbidden();
});

it('sends a teacher to the panel after signing in', function () {
    $s = diarySetup();

    $this->post(route('login.store'), ['email' => $s['teacher']->user->email, 'password' => 'password'])->assertRedirect('/teacher');
});

it('shows the teacher’s classes and subjects on the home page', function () {
    $s = diarySetup();

    $this->actingAs($s['teacher']->user)->get(route('teacher.home'))->assertOk()
        ->assertSee($s['teacher']->name_bn)->assertSee($s['subject']->name_bn)->assertSee($s['p']['class']->name_bn);
});

it('lists the day’s diary rows with their status', function () {
    $s = diarySetup();

    $this->actingAs($s['teacher']->user)->get(route('teacher.diary'))->assertOk()
        ->assertSee($s['subject']->name_bn)->assertSee(__('teacher-panel::messages.status.missing'));

    app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s));
    $this->actingAs($s['teacher']->user)->get(route('teacher.diary'))->assertSee(__('teacher-panel::messages.status.draft'));
});

it('says the madrasa is closed on a holiday', function () {
    $s = diarySetup();

    $this->actingAs($s['teacher']->user)->get(route('teacher.diary', ['date' => '2026-10-16']))->assertOk()->assertSee(__('teacher-panel::messages.diary.holiday'));
});

it('opens the editor only for an assigned section and subject', function () {
    $s = diarySetup();
    $query = ['class_id' => $s['p']['class']->id, 'section_id' => $s['p']['section']->id, 'subject_id' => $s['subject']->id];

    $this->actingAs($s['teacher']->user)->get(route('teacher.diary.write', $query))->assertOk()->assertSee('diaryEditor', false)->assertSee($s['subject']->name_bn);
    $this->actingAs($s['teacher']->user)->get(route('teacher.diary.write', [...$query, 'section_id' => $s['other']->id]))->assertForbidden();
    $this->actingAs($s['teacher']->user)->get(route('teacher.diary.write', ['class_id' => $s['p']['class']->id, 'section_id' => $s['p']['section']->id]))->assertForbidden();
});

it('lets the class teacher open the general note editor', function () {
    $s = diarySetup();
    $classTeacher = Teacher::factory()->withLogin()->create();
    app(TeacherAssignments::class)->setClassTeacher($classTeacher, $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id);

    $this->actingAs($classTeacher->user)->get(route('teacher.diary.write', ['class_id' => $s['p']['class']->id, 'section_id' => $s['p']['section']->id]))->assertOk()->assertSee(__('teacher-panel::messages.diary.general'));
});

it('shows the class day only to a class teacher, with every subject’s diary', function () {
    $s = diarySetup();
    $classTeacher = Teacher::factory()->withLogin()->create();
    app(TeacherAssignments::class)->setClassTeacher($classTeacher, $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id);
    $entry = app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s, ['lesson' => 'বিশেষ পাঠ আজকের']));
    app(DiaryService::class)->publish($s['teacher']->user, $entry);

    $this->actingAs($s['teacher']->user)->get(route('teacher.class-day'))->assertForbidden();
    $this->actingAs($classTeacher->user)->get(route('teacher.class-day'))->assertOk()->assertSee('বিশেষ পাঠ আজকের')->assertSee($s['subject']->name_bn);
});

it('builds the navigation from the modules’ teacher menus', function () {
    $s = diarySetup();
    $classTeacher = Teacher::factory()->withLogin()->create();
    app(TeacherAssignments::class)->setClassTeacher($classTeacher, $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id);

    $menu = app(TeacherMenu::class);
    $routes = fn ($user) => array_map(fn ($item) => $item->route, $menu->items($user));

    expect($routes($s['teacher']->user))->toBe(['teacher.diary'])
        ->and($routes($classTeacher->user))->toBe(['teacher.diary', 'teacher.class-day']);

    app(Settings::class)->set('teacher_marks_entry_enabled', true);
    gradedExam($s['p']);
    app(TeacherAssignments::class)->assignSubject($s['teacher'], $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id, ExamSubject::first()->subject_id);
    app(TeacherScope::class)->refresh();

    expect($routes($s['teacher']->user))->toBe(['teacher.diary', 'teacher.marks']);
});

it('explains that marks entry is off, then lists the exam once it is on', function () {
    $s = diarySetup();
    $g = gradedExam($s['p']);
    app(TeacherAssignments::class)->assignSubject($s['teacher'], $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id, $g['first']->subject_id);

    $this->actingAs($s['teacher']->user)->get(route('teacher.marks'))->assertOk()->assertSee(__('teacher-panel::messages.marks.off'));

    app(Settings::class)->set('teacher_marks_entry_enabled', true);

    $this->actingAs($s['teacher']->user)->get(route('teacher.marks'))->assertOk()->assertSee($g['exam']->name_bn)->assertSee(__('teacher-panel::messages.marks.enter'));
});

it('opens the marks grid page only for the teacher’s own section', function () {
    $s = diarySetup();
    $g = gradedExam($s['p']);
    app(TeacherAssignments::class)->assignSubject($s['teacher'], $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id, $g['first']->subject_id);
    app(Settings::class)->set('teacher_marks_entry_enabled', true);
    $url = fn ($section) => route('teacher.marks.entry', ['exam' => $g['exam']->id, 'class' => $s['p']['class']->id, 'section' => $section->id]);

    $this->actingAs($s['teacher']->user)->get($url($s['p']['section']))->assertOk()->assertSee('marksEntry', false);
    $this->actingAs($s['teacher']->user)->get($url($s['other']))->assertForbidden();
});
