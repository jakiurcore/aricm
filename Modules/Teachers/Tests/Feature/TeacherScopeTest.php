<?php

use Illuminate\Validation\ValidationException;
use Modules\Academic\Models\AcademicSession;
use Modules\Academic\Models\Section;
use Modules\Academic\Models\Subject;
use Modules\Teachers\Models\Teacher;
use Modules\Teachers\Models\TeacherAssignment;
use Modules\Teachers\Services\TeacherAssignments;
use Modules\Teachers\Services\TeacherScope;

it('scopes a teacher to the sections and subjects assigned in the current session', function () {
    $s = teachingSetup();
    app(TeacherAssignments::class)->assignSubject($s['teacher'], $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id, $s['subject']->id);
    $user = $s['teacher']->user;
    $scope = app(TeacherScope::class);

    expect($scope->canTeach($user, $s['p']['class']->id, $s['p']['section']->id, $s['subject']->id))->toBeTrue()
        ->and($scope->canTeach($user, $s['p']['class']->id, $s['other']->id, $s['subject']->id))->toBeFalse()
        ->and($scope->canTeach($user, $s['p']['class']->id, $s['p']['section']->id))->toBeTrue()
        ->and($scope->canTeach($user, $s['p']['class']->id, $s['other']->id))->toBeFalse()
        ->and($scope->canSee($user, $s['p']['class']->id, $s['p']['section']->id))->toBeTrue()
        ->and($scope->canSee($user, $s['p']['class']->id, $s['other']->id))->toBeFalse()
        ->and($scope->subjectIds($user, $s['p']['class']->id))->toBe([$s['subject']->id])
        ->and($scope->sections($user))->toHaveCount(1);
});

it('has an empty scope for a user who is not a teacher and for an inactive teacher', function () {
    $s = teachingSetup();
    app(TeacherAssignments::class)->assignSubject($s['teacher'], $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id, $s['subject']->id);
    $admin = userWithRole('admin');
    $scope = app(TeacherScope::class);

    expect($scope->canTeach($admin, $s['p']['class']->id, $s['p']['section']->id, $s['subject']->id))->toBeFalse()
        ->and($scope->assignments(null))->toBeEmpty();

    $s['teacher']->update(['is_active' => false]);
    $scope->refresh();

    expect($scope->canTeach($s['teacher']->user, $s['p']['class']->id, $s['p']['section']->id, $s['subject']->id))->toBeFalse();
});

it('ignores assignments from another session', function () {
    $s = teachingSetup();
    $old = AcademicSession::factory()->create(['is_current' => false]);
    app(TeacherAssignments::class)->assignSubject($s['teacher'], $old->id, $s['p']['class']->id, $s['p']['section']->id, $s['subject']->id);

    expect(app(TeacherScope::class)->canTeach($s['teacher']->user, $s['p']['class']->id, $s['p']['section']->id, $s['subject']->id))->toBeFalse();
});

it('keeps exactly one class teacher per section', function () {
    $s = teachingSetup();
    $second = Teacher::factory()->withLogin()->create();
    $assign = app(TeacherAssignments::class);

    $assign->setClassTeacher($s['teacher'], $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id);
    $assign->setClassTeacher($second, $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id);

    expect(TeacherAssignment::where('is_class_teacher', true)->count())->toBe(1)
        ->and(app(TeacherScope::class)->isClassTeacher($second->user, $s['p']['class']->id, $s['p']['section']->id))->toBeTrue()
        ->and(app(TeacherScope::class)->isClassTeacher($s['teacher']->user, $s['p']['class']->id, $s['p']['section']->id))->toBeFalse();
});

it('rejects a section from another class and a subject the class does not teach', function () {
    $s = teachingSetup();
    $foreign = Section::factory()->create();
    $loose = Subject::factory()->create();
    $assign = app(TeacherAssignments::class);

    expect(fn () => $assign->assignSubject($s['teacher'], $s['p']['session']->id, $s['p']['class']->id, $foreign->id, $s['subject']->id))->toThrow(ValidationException::class)
        ->and(fn () => $assign->assignSubject($s['teacher'], $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id, $loose->id))->toThrow(ValidationException::class);
});

it('does not duplicate an assignment and can copy them to a new session', function () {
    $s = teachingSetup();
    $assign = app(TeacherAssignments::class);
    $assign->assignSubject($s['teacher'], $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id, $s['subject']->id);
    $assign->assignSubject($s['teacher'], $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id, $s['subject']->id);
    $assign->setClassTeacher($s['teacher'], $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id);

    expect(TeacherAssignment::count())->toBe(2);

    $next = AcademicSession::factory()->create(['is_current' => false]);

    expect($assign->copy($s['p']['session']->id, $next->id))->toBe(2)
        ->and($assign->copy($s['p']['session']->id, $next->id))->toBe(0)
        ->and(TeacherAssignment::where('session_id', $next->id)->count())->toBe(2);
});
