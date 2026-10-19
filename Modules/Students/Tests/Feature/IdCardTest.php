<?php

use Modules\Core\Support\SignedHash;
use Modules\Students\Models\Student;
use Modules\Students\Services\IdCardPdf;
use Modules\Students\Services\IdCardVerification;

it('renders ID cards as a PDF for a class', function () {
    $p = placement();
    enrolled($p, ['roll' => 1]);
    enrolled($p, ['roll' => 2]);

    $response = $this->actingAs(userWithRole('admin'))->get(route('students.api.id-cards', ['class_id' => $p['class']->id]))->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('application/pdf')->and($response->getContent())->toStartWith('%PDF');
});

it('needs a class or student ids, and the id_card permission', function () {
    $p = placement();
    enrolled($p);

    $this->actingAs(userWithRole('admin'))->getJson(route('students.api.id-cards'))->assertUnprocessable();
    $this->actingAs(userWithRole('accountant'))->get(route('students.api.id-cards', ['class_id' => $p['class']->id]))->assertForbidden();
});

it('renders a single card and makes a sheet of nine per page', function () {
    $p = placement();
    $students = collect(range(1, 10))->map(fn (int $roll) => enrolled($p, ['roll' => $roll]));

    $this->actingAs(userWithRole('admin'))->get(route('students.api.id-card', $students[0]))->assertOk();

    $pdf = app(IdCardPdf::class)->render(Student::query()->orderBy('roll')->get());

    // 10 students = 2 sheets, each a front and a back page.
    expect(preg_match_all('/\/Type\s*\/Page[^s]/', $pdf))->toBe(4);
});

it('verifies an ID card through its signed hash and refuses forged or inactive ones', function () {
    $student = enrolled(placement());
    $hash = SignedHash::make('student-id', $student->id);

    expect((new IdCardVerification)->verify($hash)->details)->toHaveKey(__('students::pdf.verify.student_id'))
        ->and((new IdCardVerification)->verify($student->id.'-forged0000000000'))->toBeNull()
        ->and((new IdCardVerification)->verify(SignedHash::make('receipt', $student->id)))->toBeNull();

    $this->get(route('core.verify', ['type' => 'student-id', 'hash' => $hash]))->assertSee($student->student_id);

    $student->update(['status' => 'inactive']);
    expect((new IdCardVerification)->verify($hash))->toBeNull();
});
