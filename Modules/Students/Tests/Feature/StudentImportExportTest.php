<?php

use Illuminate\Http\UploadedFile;
use Modules\Students\Models\Student;
use Modules\Students\Services\StudentRegistrar;

function csvFile(array $rows): UploadedFile
{
    $header = 'name_bn,name_en,class,section,roll,father_name,mother_name,guardian_name,guardian_phone,guardian_relation,date_of_birth,gender,blood_group,religion,present_address,permanent_address,admission_date,residency_type';

    return UploadedFile::fake()->createWithContent('students.csv', "\xEF\xBB\xBF".$header."\n".implode("\n", $rows)."\n");
}

it('imports students from CSV resolving class and section by name', function () {
    $placement = placement();
    $class = $placement['class']->name_bn;
    $section = $placement['section']->name_bn;

    $row = fn (string $name, int $roll, string $dob = '14-05-2015') => "{$name},,{$class},{$section},{$roll},পিতা,মাতা,পিতা,01711111111,পিতা,{$dob},ছেলে,A+,ইসলাম,গাইবান্ধা,গাইবান্ধা,2026-01-10,";

    $response = $this->actingAs(userWithRole('admin'))->post(route('students.api.import'), ['file' => csvFile([$row('রহিম', 1), $row('করিম', 2, '2014-01-31')])], ['Accept' => 'application/json'])
        ->assertOk();

    $response->assertJsonPath('data.created', 2);
    expect(Student::count())->toBe(2)
        ->and(Student::orderBy('roll')->first()->date_of_birth->toDateString())->toBe('2015-05-14')
        ->and(Student::orderBy('roll')->first()->gender->value)->toBe('male');
});

it('reports row errors and writes nothing', function () {
    $placement = placement();
    $class = $placement['class']->name_bn;

    $rows = [
        "ভালো,,{$class},,1,পিতা,,,,,2015-05-14,male,,,,,2026-01-10,",
        'খারাপ,,অজানা শ্রেণি,,2,পিতা,,,,,2015-05-14,male,,,,,2026-01-10,',
        "তারিখ,,{$class},,3,পিতা,,,,,not-a-date,male,,,,,2026-01-10,",
    ];

    $response = $this->actingAs(userWithRole('admin'))->post(route('students.api.import'), ['file' => csvFile($rows)], ['Accept' => 'application/json'])->assertUnprocessable();

    expect(array_keys($response->json('data.errors')))->toBe([3, 4])
        ->and(Student::count())->toBe(0);
});

it('does a dry run without saving', function () {
    $placement = placement();

    $this->actingAs(userWithRole('admin'))->post(route('students.api.import'), [
        'dry_run' => true,
        'file' => csvFile(["রহিম,,{$placement['class']->name_bn},,1,পিতা,,,,,2015-05-14,male,,,,,2026-01-10,"]),
    ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.valid', 1)->assertJsonPath('data.created', 0);

    expect(Student::count())->toBe(0);
});

it('rejects a roll that is already taken', function () {
    $placement = placement();
    app(StudentRegistrar::class)->register(['session_id' => $placement['session']->id, 'class_id' => $placement['class']->id, 'section_id' => null, 'roll' => 1, 'name_bn' => 'আগে', 'father_name' => 'x', 'date_of_birth' => '2015-01-01', 'gender' => 'male', 'admission_date' => '2026-01-01', 'residency_type' => 'non_residential']);

    $this->actingAs(userWithRole('admin'))->post(route('students.api.import'), [
        'file' => csvFile(["পরে,,{$placement['class']->name_bn},,1,পিতা,,,,,2015-05-14,male,,,,,2026-01-10,"]),
    ], ['Accept' => 'application/json'])->assertUnprocessable();
});

it('exports a UTF-8 CSV with a BOM and the student id', function () {
    $placement = placement();
    app(StudentRegistrar::class)->register(['session_id' => $placement['session']->id, 'class_id' => $placement['class']->id, 'section_id' => null, 'roll' => 1, 'name_bn' => 'রহিম', 'father_name' => 'করিম', 'date_of_birth' => '2015-01-01', 'gender' => 'male', 'admission_date' => '2026-01-01', 'residency_type' => 'non_residential']);

    $content = $this->actingAs(userWithRole('admin'))->get(route('students.api.export'))->assertOk()->streamedContent();

    expect($content)->toStartWith("\xEF\xBB\xBF".'student_id,name_bn')->toContain('ARICM-2026-0001')->toContain('রহিম');
});

it('serves a CSV template to those who may import', function () {
    $this->actingAs(userWithRole('accountant'))->get(route('students.api.template'))->assertForbidden();
    $this->actingAs(userWithRole('admin'))->get(route('students.api.template'))->assertOk();
});
