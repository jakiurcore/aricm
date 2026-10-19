<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\Academic\Models\AcademicSession;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Models\Section;
use Modules\Academic\Models\Subject;
use Modules\Accounts\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Accounts\Models\User;
use Modules\Exams\Models\Exam;
use Modules\Exams\Models\ExamRoutine;
use Modules\Exams\Models\ExamSubject;
use Modules\Results\Database\Seeders\GradingScaleSeeder;
use Modules\Results\Services\MarksService;
use Modules\Students\Models\Student;
use Modules\Students\Services\StudentRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', '../Modules/*/Tests/Feature');

pest()->extend(TestCase::class)
    ->in('../Modules/*/Tests/Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Create a user holding the given role, with every module's permissions seeded first.
 */
function userWithRole(string $role): User
{
    app(RolesAndPermissionsSeeder::class)->run();

    return User::factory()->create()->assignRole($role);
}

/**
 * A current session plus a class (with one section) for tests that need a student placement.
 *
 * @return array{session: AcademicSession, class: SchoolClass, section: Section}
 */
function placement(): array
{
    $session = AcademicSession::factory()->current()->create();
    $class = SchoolClass::factory()->create();
    $section = Section::factory()->create(['class_id' => $class->id]);

    return compact('session', 'class', 'section');
}

/**
 * A valid one-pixel PNG, so image validation passes without GD.
 */
function tinyPng(string $name = 'photo.png'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
}

/**
 * An exam for the placement's class with one configured subject and a routine row.
 *
 * @param  array{session: AcademicSession, class: SchoolClass, section: Section}  $placement
 */
function examFor(array $placement, bool $released = true, bool $withRoutine = true): Exam
{
    $exam = Exam::factory()->create(['session_id' => $placement['session']->id, 'admit_card_released' => $released]);
    $exam->classes()->attach($placement['class']->id);

    $subject = Subject::factory()->create();
    $placement['class']->subjects()->attach($subject->id, ['sort_order' => 1]);
    ExamSubject::create(['exam_id' => $exam->id, 'class_id' => $placement['class']->id, 'subject_id' => $subject->id, 'full_marks' => 100, 'pass_marks' => 33, 'sort_order' => 1]);

    if ($withRoutine) {
        ExamRoutine::create(['exam_id' => $exam->id, 'class_id' => $placement['class']->id, 'subject_id' => $subject->id, 'exam_date' => '2026-06-02', 'start_time' => '10:00', 'end_time' => '12:00']);
    }

    return $exam;
}

/**
 * Register a student in the placement's class and section.
 *
 * @param  array{session: AcademicSession, class: SchoolClass, section: Section}  $p
 * @param  array<string, mixed>  $overrides
 */
function enrolled(array $p, array $overrides = []): Student
{
    return app(StudentRegistrar::class)->register([
        'session_id' => $p['session']->id, 'class_id' => $p['class']->id, 'section_id' => $p['section']->id,
        'name_bn' => 'রহিম', 'father_name' => 'করিম', 'date_of_birth' => '2015-03-07', 'gender' => 'male',
        'admission_date' => '2026-01-10', 'residency_type' => 'non_residential', ...$overrides,
    ]);
}

/**
 * An exam with two subjects (100 marks, pass 33) for the placement's class, plus the default grading scale.
 * Returns the exam and its two exam subjects.
 *
 * @param  array{session: AcademicSession, class: SchoolClass, section: Section}  $p
 * @return array{exam: Exam, first: ExamSubject, second: ExamSubject}
 */
function gradedExam(array $p): array
{
    (new GradingScaleSeeder)->run();

    $exam = Exam::factory()->create(['session_id' => $p['session']->id]);
    $exam->classes()->attach($p['class']->id);

    $make = function (int $order) use ($exam, $p): ExamSubject {
        $subject = Subject::factory()->create();
        $p['class']->subjects()->attach($subject->id, ['sort_order' => $order]);

        return ExamSubject::create(['exam_id' => $exam->id, 'class_id' => $p['class']->id, 'subject_id' => $subject->id, 'full_marks' => 100, 'pass_marks' => 33, 'sort_order' => $order]);
    };

    return ['exam' => $exam, 'first' => $make(1), 'second' => $make(2)];
}

/**
 * Enter marks for a student: [exam subject id => obtained, or 'absent'].
 *
 * @param  array<int, float|int|string>  $marks
 */
function enterMarks(Exam $exam, int $classId, Student $student, array $marks): void
{
    $entries = [];
    foreach ($marks as $subjectId => $value) {
        $entries[] = ['student_id' => $student->id, 'exam_subject_id' => $subjectId, 'obtained' => $value === 'absent' ? null : $value, 'is_absent' => $value === 'absent'];
    }

    app(MarksService::class)->save($exam, $classId, $entries, null);
}
