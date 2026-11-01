<?php

use Illuminate\Support\Facades\Hash;
use Modules\Core\Services\PublicSite;
use Modules\Core\Services\Settings;
use Modules\Diary\Models\DiaryAcknowledgement;
use Modules\Diary\Models\DiaryEntry;
use Modules\Diary\Models\DiaryStudentRemark;
use Modules\Diary\Services\DiaryService;
use Modules\Exams\Models\ExamSubject;
use Modules\TeacherPanel\Services\ClassDayReport;
use Modules\TeacherPanel\Services\TeacherMenu;
use Modules\TeacherPanel\Support\PanelFormat;
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

    $today = now()->toDateString();

    $this->actingAs($s['teacher']->user)->get(route('teacher.diary', ['date' => $today]))->assertOk()
        ->assertSee($s['subject']->name_bn)->assertSee(__('teacher-panel::messages.status.missing'));

    app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s));
    $this->actingAs($s['teacher']->user)->get(route('teacher.diary', ['date' => $today]))->assertSee(__('teacher-panel::messages.status.draft'));
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

it('opens the first section when the editor is visited without one', function () {
    $s = diarySetup();

    $this->actingAs($s['teacher']->user)->get(route('teacher.diary.write'))
        ->assertRedirect(route('teacher.diary.write', ['class_id' => $s['p']['class']->id, 'section_id' => $s['p']['section']->id, 'subject_id' => $s['subject']->id]));
});

it('counts the guardians who saw an entry on the home cards', function () {
    $s = diarySetup();
    $entry = app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s));
    app(DiaryService::class)->publish($s['teacher']->user, $entry);
    DiaryAcknowledgement::create(['diary_entry_id' => $entry->id, 'student_id' => $s['student']->id, 'seen_at' => now()]);

    $this->actingAs($s['teacher']->user)->get(route('teacher.home'))->assertOk()
        ->assertSee(__('teacher-panel::messages.home.published_meta', ['time' => PanelFormat::time($entry->fresh()->published_at), 'seen' => bn_digits(1), 'total' => bn_digits(1)]));
});

it('filters the history by day and keeps other teachers’ entries out', function () {
    $s = diarySetup();
    app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s, ['lesson' => 'আমার পাঠ আজ']));
    $other = Teacher::factory()->withLogin()->create();
    app(TeacherAssignments::class)->assignSubject($other, $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id, $s['subject']->id);
    app(DiaryService::class)->save($other->user, diaryPayload($s, ['lesson' => 'অন্যের পাঠ']));

    $this->actingAs($s['teacher']->user)->get(route('teacher.diary', ['date' => '2026-10-22']))->assertSee('আমার পাঠ আজ')->assertDontSee('অন্যের পাঠ');
    $this->actingAs($s['teacher']->user)->get(route('teacher.diary', ['date' => '2026-10-21']))->assertDontSee('আমার পাঠ আজ');
});

it('returns the latest earlier published entry to copy from', function () {
    $s = diarySetup();
    $query = ['class_id' => $s['p']['class']->id, 'section_id' => $s['p']['section']->id, 'subject_id' => $s['subject']->id, 'date' => '2026-10-22'];

    $this->actingAs($s['teacher']->user)->getJson(route('teacher.diary.previous', $query))->assertOk()->assertJsonPath('data', null);

    $entry = app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s, ['entry_date' => '2026-10-21', 'lesson' => 'গতকালের পাঠ']));
    $this->actingAs($s['teacher']->user)->getJson(route('teacher.diary.previous', $query))->assertJsonPath('data', null);

    app(DiaryService::class)->publish($s['teacher']->user, $entry);
    $this->actingAs($s['teacher']->user)->getJson(route('teacher.diary.previous', $query))->assertJsonPath('data.lesson', 'গতকালের পাঠ')->assertJsonPath('data.date', '2026-10-21');
});

it('adds remarks for several students at once, opening today’s entry when there is none', function () {
    $s = diarySetup();
    $second = enrolled($s['p']);
    $payload = ['section_id' => $s['p']['section']->id, 'student_ids' => [$s['student']->id, $second->id], 'kind' => 'no_homework'];

    $this->actingAs($s['teacher']->user)->postJson(route('teacher.remarks.store'), $payload)->assertCreated()->assertJsonCount(2, 'data');

    expect(DiaryStudentRemark::count())->toBe(2)->and(DiaryEntry::count())->toBe(1)->and(DiaryEntry::first()->subject_id)->toBe($s['subject']->id);
    $this->actingAs($s['teacher']->user)->get(route('teacher.remarks'))->assertOk()->assertSee(__('diary::messages.remark_kinds.no_homework'));
});

it('refuses remarks outside the teacher’s sections or for an unknown kind', function () {
    $s = diarySetup();
    $payload = ['section_id' => $s['p']['section']->id, 'student_ids' => [$s['student']->id], 'kind' => 'praise'];

    $this->actingAs($s['teacher']->user)->postJson(route('teacher.remarks.store'), [...$payload, 'section_id' => $s['other']->id])->assertForbidden();
    $this->actingAs($s['teacher']->user)->postJson(route('teacher.remarks.store'), [...$payload, 'kind' => 'nope'])->assertJsonValidationErrors('kind');
    $this->actingAs(userWithRole('accountant'))->postJson(route('teacher.remarks.store'), $payload)->assertForbidden();
});

it('reports the class day and texts the teachers and guardians who are behind', function () {
    $s = diarySetup();
    config(['modules.core.sms.driver' => 'log']);
    $s['student']->update(['guardian_phone' => '01711111111']);
    $classTeacher = Teacher::factory()->withLogin()->create(['phone' => '01722222222']);
    app(TeacherAssignments::class)->setClassTeacher($classTeacher, $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id);
    $entry = app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s));
    app(DiaryService::class)->publish($s['teacher']->user, $entry);

    $report = app(ClassDayReport::class)->build($s['p']['class']->id, $s['p']['section']->id, now());
    expect($report['written'])->toBe(1)->and($report['seen'])->toBe(0)->and($report['notSeen']->pluck('id')->all())->toBe([$s['student']->id]);

    $this->actingAs($classTeacher->user)->postJson(route('teacher.class-day.sms', ['section_id' => $s['p']['section']->id]))->assertOk()->assertJsonPath('sent', 1);
    $this->actingAs($classTeacher->user)->postJson(route('teacher.class-day.remind', ['section_id' => $s['p']['section']->id]))->assertOk()->assertJsonPath('sent', 0);
    $this->actingAs($s['teacher']->user)->postJson(route('teacher.class-day.sms'))->assertForbidden();
});

it('shows the profile and changes the password only with the current one', function () {
    $s = diarySetup();
    $user = $s['teacher']->user;

    $this->actingAs($user)->get(route('teacher.profile'))->assertOk()->assertSee($user->email)->assertSee($s['subject']->name_bn);

    $this->actingAs($user)->put(route('teacher.password.update'), ['current_password' => 'wrong', 'password' => 'newsecret', 'password_confirmation' => 'newsecret'])->assertSessionHasErrors('current_password');
    $this->actingAs($user)->put(route('teacher.password.update'), ['current_password' => 'password', 'password' => 'newsecret', 'password_confirmation' => 'newsecret'])->assertRedirect(route('teacher.profile'));

    expect(Hash::check('newsecret', $user->fresh()->password))->toBeTrue();
});

it('shows the program line under the school name in the panel layout', function () {
    $s = diarySetup();
    $program = app(PublicSite::class)->identity()['program'];

    $this->actingAs($s['teacher']->user)->get(route('teacher.home'))->assertOk()->assertSee($program)->assertSee('data-icon="pencil"', false);
});
