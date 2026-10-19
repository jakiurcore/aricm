<?php

use Illuminate\Testing\TestResponse;
use Laramod\Contracts\Module;
use Laramod\Facades\Modules;
use Modules\Exams\Models\Exam;
use Modules\Exams\Support\Eligibility;
use Modules\Results\Contracts\ProvidesResultEligibility;
use Modules\Results\Models\ExamResult;
use Modules\Results\Services\ResultDirectory;
use Modules\Results\Services\ResultProcessor;
use Modules\Results\Support\MarksheetHash;
use Modules\Students\Models\Student;

function publishedScenario(): array
{
    $p = placement();
    $setup = gradedExam($p);
    $student = enrolled($p, ['roll' => 7, 'date_of_birth' => '2015-03-07']);
    enterMarks($setup['exam'], $p['class']->id, $student, [$setup['first']->id => 90, $setup['second']->id => 80]);
    app(ResultProcessor::class)->process($setup['exam'], $p['class']->id);

    return [$p, $setup['exam'], $student];
}

function publish(Exam $exam): void
{
    $exam->forceFill(['result_published' => true, 'result_published_at' => now()])->save();
}

function search(object $test, Exam $exam, array $p, array $overrides = []): TestResponse
{
    return $test->post(route('results.public.search'), ['exam_id' => $exam->id, 'class_id' => $p['class']->id, 'identifier' => '7', 'date_of_birth' => '2015-03-07', ...$overrides]);
}

it('hides unpublished results from students and the public', function () {
    [$p, $exam, $student] = publishedScenario();

    expect(app(ResultDirectory::class)->publishedResult($exam, $student))->toBeNull()
        ->and(app(ResultDirectory::class)->publishedExamsFor($student))->toBeEmpty();

    search($this, $exam, $p)->assertOk()->assertSee(__('results::messages.public.none_published'));
});

it('shows a published result through the directory', function () {
    [, $exam, $student] = publishedScenario();
    publish($exam);

    expect(app(ResultDirectory::class)->publishedResult($exam, $student)->gpa)->toBe(5.0)
        ->and(app(ResultDirectory::class)->publishedExamsFor($student))->toHaveCount(1);
});

it('finds a published result by roll and date of birth', function () {
    [$p, $exam] = publishedScenario();
    publish($exam);

    search($this, $exam, $p)->assertOk()->assertSee('GPA')->assertSee(__('results::messages.passed'));
});

it('finds a published result by student id too', function () {
    [$p, $exam, $student] = publishedScenario();
    publish($exam);

    search($this, $exam, $p, ['identifier' => strtolower($student->student_id)])->assertOk()->assertSee('GPA');
});

it('gives the same answer for a wrong birth date, an unknown roll and an unpublished exam', function () {
    [$p, $exam] = publishedScenario();

    publish($exam);
    $wrongDob = search($this, $exam, $p, ['date_of_birth' => '2015-03-08']);
    $unknown = search($this, $exam, $p, ['identifier' => '99']);

    $wrongDob->assertSee(__('results::messages.public.not_found'));
    $unknown->assertSee(__('results::messages.public.not_found'));
});

it('does not find a student of another class', function () {
    [$p, $exam] = publishedScenario();
    publish($exam);
    $other = placement();

    search($this, $exam, ['class' => $other['class']])->assertSee(__('results::messages.public.not_found'));
});

it('throttles the public search', function () {
    [$p, $exam] = publishedScenario();
    publish($exam);

    foreach (range(1, 20) as $ignored) {
        search($this, $exam, $p, ['identifier' => '99']);
    }

    search($this, $exam, $p)->assertStatus(429);
});

it('puts no personal data in a URL', function () {
    [$p, $exam] = publishedScenario();
    publish($exam);

    expect(search($this, $exam, $p)->baseRequest->getUri())->not->toContain('2015');
});

function holdBack(bool $allow): Module
{
    return new class($allow) implements Module, ProvidesResultEligibility
    {
        public function __construct(private bool $allow) {}

        public function name(): string
        {
            return 'fake-result-eligibility';
        }

        public function path(): string
        {
            return __DIR__;
        }

        public function resultEligibility(Exam $exam, Student $student): Eligibility
        {
            return $this->allow ? Eligibility::allow() : Eligibility::deny('বকেয়া পরিশোধ করুন');
        }
    };
}

it('asks eligibility providers before showing a result', function () {
    [$p, $exam, $student] = publishedScenario();
    publish($exam);
    Modules::register(holdBack(false));

    expect(app(ResultDirectory::class)->eligibility($exam, $student)->reason)->toBe('বকেয়া পরিশোধ করুন');

    search($this, $exam, $p)->assertOk()->assertSee('বকেয়া পরিশোধ করুন')->assertDontSee('GPA');
});

it('signs the marksheet hash so it cannot be forged for another result', function () {
    [, $exam] = publishedScenario();
    $result = ExamResult::first();
    $hash = MarksheetHash::for($result);

    expect(MarksheetHash::resolve($hash)->id)->toBe($result->id)
        ->and(MarksheetHash::resolve($result->id.'-0000000000000000'))->toBeNull()
        ->and(MarksheetHash::resolve('abc'))->toBeNull();
});

it('verifies a marksheet only while it is published', function () {
    [, $exam] = publishedScenario();
    $hash = MarksheetHash::for(ExamResult::first());

    $this->get(route('core.verify', ['type' => 'marksheet', 'hash' => $hash]))->assertSee(__('core::messages.verify.not_found'));

    publish($exam);
    $this->get(route('core.verify', ['type' => 'marksheet', 'hash' => $hash]))->assertSee(__('core::messages.verify.valid'));
});

it('adds the latest GPA to the student summary only when published', function () {
    [, $exam, $student] = publishedScenario();
    $module = Modules::find('results');

    expect($module->studentSummary($student))->toBe([]);

    publish($exam);
    expect($module->studentSummary($student)[0]->value)->toContain('৫');
});
