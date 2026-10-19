<?php

use Modules\Academic\Database\Seeders\DemoAcademicSeeder;
use Modules\Academic\Models\AcademicSession;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Models\Section;
use Modules\Academic\Models\Subject;
use Modules\Academic\Services\AcademicDirectory;

beforeEach(function () {
    $this->admin = userWithRole('admin');
});

it('keeps the academic API away from teachers and guests', function () {
    $this->getJson(route('academic.api.classes.index'))->assertUnauthorized();
    $this->actingAs(userWithRole('teacher'))->getJson(route('academic.api.classes.index'))->assertForbidden();
});

it('lets an accountant read but not change classes', function () {
    $accountant = userWithRole('accountant');

    $this->actingAs($accountant)->getJson(route('academic.api.classes.index'))->assertOk();
    $this->actingAs($accountant)->postJson(route('academic.api.classes.store'), ['name_bn' => 'X', 'numeric_order' => 1])->assertForbidden();
});

it('keeps exactly one current session', function () {
    $first = $this->actingAs($this->admin)->postJson(route('academic.api.sessions.store'), ['name' => '2025'])->assertCreated()->json('data');
    expect($first['is_current'])->toBeTrue();

    $second = $this->actingAs($this->admin)->postJson(route('academic.api.sessions.store'), ['name' => '2026', 'is_current' => true])->json('data');

    expect(AcademicSession::where('is_current', true)->pluck('name')->all())->toBe(['2026'])
        ->and(app(AcademicDirectory::class)->currentSession()->id)->toBe($second['id']);

    $this->actingAs($this->admin)->deleteJson(route('academic.api.sessions.destroy', $second['id']))->assertUnprocessable();
});

it('validates sessions', function () {
    AcademicSession::create(['name' => '2026']);

    $this->actingAs($this->admin)->postJson(route('academic.api.sessions.store'), ['name' => '2026', 'start_date' => '2026-05-01', 'end_date' => '2026-01-01'])
        ->assertJsonValidationErrors(['name', 'end_date']);
});

it('creates classes with a division and sections unique within the class', function () {
    $class = $this->actingAs($this->admin)->postJson(route('academic.api.classes.store'), [
        'name_bn' => 'প্লে', 'name_en' => 'Play', 'numeric_order' => 1, 'division' => 'kids', 'is_active' => true,
    ])->assertCreated()->json('data');

    $this->actingAs($this->admin)->postJson(route('academic.api.classes.store'), ['name_bn' => 'X', 'numeric_order' => 2, 'division' => 'bogus'])
        ->assertJsonValidationErrors('division');

    $this->actingAs($this->admin)->postJson(route('academic.api.sections.store'), ['class_id' => $class['id'], 'name_bn' => 'ক'])->assertCreated();
    $this->actingAs($this->admin)->postJson(route('academic.api.sections.store'), ['class_id' => $class['id'], 'name_bn' => 'ক'])->assertJsonValidationErrors('name_bn');

    $other = SchoolClass::create(['name_bn' => 'অন্য', 'numeric_order' => 3]);
    $this->actingAs($this->admin)->postJson(route('academic.api.sections.store'), ['class_id' => $other->id, 'name_bn' => 'ক'])->assertCreated();
});

it('maps subjects to a class with order and optional flags', function () {
    $class = SchoolClass::create(['name_bn' => 'প্রথম', 'numeric_order' => 3]);
    $quran = Subject::create(['name_bn' => 'কুরআন']);
    $gk = Subject::create(['name_bn' => 'সাধারণ জ্ঞান']);

    $this->actingAs($this->admin)->putJson(route('academic.api.classes.subjects.update', $class), [
        'subjects' => [['subject_id' => $gk->id, 'is_optional' => true], ['subject_id' => $quran->id]],
    ])->assertOk()->assertJsonPath('data.0.subject_id', $gk->id)->assertJsonPath('data.0.is_optional', true);

    expect(app(AcademicDirectory::class)->subjectsOf($class->id)->pluck('id')->all())->toBe([$gk->id, $quran->id]);

    $this->actingAs($this->admin)->putJson(route('academic.api.classes.subjects.update', $class), ['subjects' => [['subject_id' => $quran->id], ['subject_id' => $quran->id]]])
        ->assertJsonValidationErrors('subjects.0.subject_id');
});

it('rejects a section that does not belong to the class', function () {
    $one = SchoolClass::create(['name_bn' => 'এক', 'numeric_order' => 1]);
    $two = SchoolClass::create(['name_bn' => 'দুই', 'numeric_order' => 2]);
    $section = Section::create(['class_id' => $one->id, 'name_bn' => 'ক']);

    $directory = app(AcademicDirectory::class);

    expect($directory->sectionBelongsToClass($section->id, $one->id))->toBeTrue()
        ->and($directory->sectionBelongsToClass($section->id, $two->id))->toBeFalse()
        ->and($directory->sectionBelongsToClass(null, $two->id))->toBeTrue();
});

it('exposes dropdown options for other screens', function () {
    $this->seed(DemoAcademicSeeder::class);

    $this->actingAs($this->admin)->getJson(route('academic.api.options'))
        ->assertOk()
        ->assertJsonCount(11, 'data.classes')
        ->assertJsonPath('data.sessions.0.name', '২০২৬');
});
