<?php

use Laramod\Contracts\Module;
use Laramod\Facades\Modules;
use Modules\Exams\Contracts\ProvidesAdmitCardEligibility;
use Modules\Exams\Models\Exam;
use Modules\Exams\Services\AdmitCardService;
use Modules\Exams\Support\Eligibility;
use Modules\Students\Models\Student;

function blockingProvider(bool $allow): Module
{
    return new class($allow) implements Module, ProvidesAdmitCardEligibility
    {
        public function __construct(private bool $allow) {}

        public function name(): string
        {
            return 'fake-eligibility-'.($this->allow ? 'yes' : 'no');
        }

        public function path(): string
        {
            return __DIR__;
        }

        public function admitCardEligibility(Exam $exam, Student $student): Eligibility
        {
            return $this->allow ? Eligibility::allow() : Eligibility::deny('বকেয়া আছে', 'student.login');
        }
    };
}

it('allows a card for an active student of a released exam with a routine', function () {
    $p = placement();

    expect(app(AdmitCardService::class)->eligibility(examFor($p), enrolled($p))->allowed)->toBeTrue();
});

it('denies when the card is not released, there is no routine, or the class is not in the exam', function () {
    $p = placement();
    $student = enrolled($p);
    $service = app(AdmitCardService::class);

    expect($service->eligibility(examFor($p, released: false), $student)->reason)->toBe(__('exams::messages.admit.not_released'))
        ->and($service->eligibility(examFor($p, withRoutine: false), $student)->reason)->toBe(__('exams::messages.admit.no_routine'));

    $other = placement();
    expect($service->eligibility(examFor($other), $student)->reason)->toBe(__('exams::messages.admit.not_in_exam'));
});

it('denies inactive students', function () {
    $p = placement();
    $student = enrolled($p);
    $student->update(['status' => 'inactive']);

    expect(app(AdmitCardService::class)->eligibility(examFor($p), $student)->reason)->toBe(__('exams::messages.admit.inactive'));
});

it('asks every provider and the first denial wins', function () {
    $p = placement();
    $student = enrolled($p);
    $exam = examFor($p);

    Modules::register(blockingProvider(true));
    expect(app(AdmitCardService::class)->eligibility($exam, $student)->allowed)->toBeTrue();

    Modules::register(blockingProvider(false));
    $decision = app(AdmitCardService::class)->eligibility($exam, $student);

    expect($decision->allowed)->toBeFalse()->and($decision->reason)->toBe('বকেয়া আছে');
    expect(app(AdmitCardService::class)->eligibility($exam, $student, checkProviders: false)->allowed)->toBeTrue();
});

it('releases and previews the roster through the API', function () {
    $p = placement();
    $student = enrolled($p, ['roll' => 1]);
    $exam = examFor($p, released: false);
    Modules::register(blockingProvider(false));
    $admin = userWithRole('admin');

    $this->actingAs($admin)->getJson(route('exams.api.admit.roster', ['exam' => $exam, 'class_id' => $p['class']->id]))
        ->assertOk()->assertJsonPath('data.0.code', $student->student_id)->assertJsonPath('data.0.eligibility.allowed', false);

    $this->actingAs($admin)->postJson(route('exams.api.admit.release', $exam), ['released' => true])->assertOk();
    expect($exam->fresh()->admit_card_released)->toBeTrue();

    $this->actingAs(userWithRole('accountant'))->postJson(route('exams.api.admit.release', $exam), ['released' => false])->assertForbidden();
});
