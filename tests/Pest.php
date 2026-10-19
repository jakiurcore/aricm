<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Academic\Models\AcademicSession;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Models\Section;
use Modules\Academic\Models\Subject;
use Modules\Accounts\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Accounts\Models\User;
use Modules\Exams\Models\Exam;
use Modules\Exams\Models\ExamRoutine;
use Modules\Exams\Models\ExamSubject;
use Modules\Fees\Enums\PaymentMethod;
use Modules\Fees\Models\FeeHead;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Models\FeeStructure;
use Modules\Fees\Models\Payment;
use Modules\Fees\Services\InvoiceGenerator;
use Modules\Fees\Services\PaymentService;
use Modules\Fees\Support\Money;
use Modules\Results\Database\Seeders\GradingScaleSeeder;
use Modules\Results\Services\MarksService;
use Modules\Students\Models\Student;
use Modules\Students\Services\StudentRegistrar;
use Modules\Teachers\Models\Teacher;
use Modules\Teachers\Services\TeacherAssignments;
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

/**
 * A monthly tuition head (৳1000) and meal head (৳300) with fee structures for the placement's class.
 *
 * @param  array{session: AcademicSession, class: SchoolClass, section: Section}  $p
 * @return array{tuition: FeeHead, meal: FeeHead}
 */
function feeSetup(array $p, int $tuition = 1000, int $meal = 300): array
{
    $heads = [
        'tuition' => FeeHead::factory()->monthly()->create(['name_bn' => 'মাসিক বেতন', 'sort_order' => 1]),
        'meal' => FeeHead::factory()->monthly()->create(['name_bn' => 'খাবার ফি', 'sort_order' => 2]),
    ];

    foreach ([['tuition', $tuition], ['meal', $meal]] as [$key, $amount]) {
        FeeStructure::create(['session_id' => $p['session']->id, 'class_id' => $p['class']->id, 'fee_head_id' => $heads[$key]->id, 'amount' => Money::taka($amount)]);
    }

    return $heads;
}

function taka(int|float|string $amount): Money
{
    return Money::taka($amount);
}

/**
 * Monthly invoices for a student for the given months (each ৳1300 with feeSetup defaults), oldest first.
 *
 * @param  list<string>  $months  'YYYY-MM-01'
 * @return Collection<int, FeeInvoice>
 */
function invoicesFor(Student $student, array $months): Collection
{
    foreach ($months as $month) {
        app(InvoiceGenerator::class)->generateMonthly(Carbon::parse($month), [$student->class_id]);
    }

    return FeeInvoice::query()->where('student_id', $student->id)->orderBy('billing_month')->get();
}

/**
 * A guardian's manual (mobile wallet or bank) payment for the given invoices, left pending.
 */
function submit(Student $student, array $invoiceIds, string $txn = 'ABC12345XY', PaymentMethod $method = PaymentMethod::Bkash, ?string $sender = '01711111111', $screenshot = null): Payment
{
    return app(PaymentService::class)->submitManual($student, $invoiceIds, $method, $txn, $sender, $screenshot);
}

/**
 * A class with two sections and a subject, plus a teacher (with login) for it.
 *
 * @return array{p: array, other: Section, subject: Subject, teacher: Teacher}
 */
function teachingSetup(): array
{
    $p = placement();
    $other = Section::factory()->create(['class_id' => $p['class']->id]);
    $subject = Subject::factory()->create();
    $p['class']->subjects()->attach($subject->id, ['sort_order' => 1]);

    return ['p' => $p, 'other' => $other, 'subject' => $subject, 'teacher' => Teacher::factory()->withLogin()->create()];
}

/**
 * It is Thursday 22 Oct 2026, a teacher with a login teaches the subject in the section, and a student is enrolled there.
 * Friday is the weekly holiday, so the next school day after today is Saturday.
 *
 * @return array{p: array, other: Section, subject: Subject, teacher: Teacher, student: Student}
 */
function diarySetup(): array
{
    Carbon::setTestNow('2026-10-22 10:00:00');

    $s = teachingSetup();
    app(TeacherAssignments::class)->assignSubject($s['teacher'], $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id, $s['subject']->id);
    $s['student'] = enrolled($s['p']);

    return $s;
}

/**
 * The payload a teacher posts to save a diary entry for the setup's section and subject.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function diaryPayload(array $s, array $overrides = []): array
{
    return ['class_id' => $s['p']['class']->id, 'section_id' => $s['p']['section']->id, 'subject_id' => $s['subject']->id, 'entry_date' => '2026-10-22', 'lesson' => 'আজ অধ্যায় ১ পড়ানো হয়েছে।', 'homework' => 'পৃষ্ঠা ১০ লিখে আনবে।', ...$overrides];
}
