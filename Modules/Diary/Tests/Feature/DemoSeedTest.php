<?php

use Illuminate\Support\Carbon;
use Modules\Academic\Database\Seeders\DemoAcademicSeeder;
use Modules\Academic\Models\Section;
use Modules\Diary\Database\Seeders\DemoDiarySeeder;
use Modules\Diary\Database\Seeders\QuickPhraseSeeder;
use Modules\Diary\Models\DiaryEntry;
use Modules\Diary\Models\QuickPhrase;
use Modules\Diary\Services\DiaryCompliance;
use Modules\Teachers\Database\Seeders\DemoTeacherSeeder;
use Modules\Teachers\Models\Teacher;
use Modules\Teachers\Models\TeacherAssignment;

it('seeds five teachers with logins, assignments and exactly one class teacher per section', function () {
    $this->seed(DemoAcademicSeeder::class);
    $this->seed(DemoTeacherSeeder::class);

    expect(Teacher::count())->toBe(5)
        ->and(Teacher::first()->user->can('diary.write'))->toBeTrue()
        ->and(TeacherAssignment::where('is_class_teacher', true)->count())->toBe(Section::count())
        ->and(TeacherAssignment::whereNotNull('subject_id')->count())->toBeGreaterThan(40);

    $this->post(route('login.store'), ['email' => 'teacher1@example.com', 'password' => 'password'])->assertRedirect('/teacher');
});

it('seeds two weeks of diary that the monitor can read, and stays idempotent', function () {
    Carbon::setTestNow('2026-10-22 10:00:00');
    $this->seed(DemoAcademicSeeder::class);
    $this->seed(DemoTeacherSeeder::class);
    $this->seed(DemoDiarySeeder::class);
    $count = DiaryEntry::count();

    $this->seed(DemoDiarySeeder::class);

    $dates = DiaryEntry::query()->orderBy('entry_date')->get()->map(fn ($entry) => $entry->entry_date->toDateString())->unique();
    expect($count)->toBeGreaterThan(100)->and(DiaryEntry::count())->toBe($count)
        ->and($dates->count())->toBeGreaterThanOrEqual(9)
        ->and($dates->contains('2026-10-23'))->toBeFalse();   // no entry on a Friday

    $today = app(DiaryCompliance::class)->forDate(Carbon::parse('2026-10-22'))['summary'];
    expect($today['expected'])->toBeGreaterThan(40)->and($today['missing'] + $today['draft'])->toBeGreaterThan(0)->and($today['published'])->toBeGreaterThan(0);
});

it('seeds the shared quick phrases once', function () {
    $this->seed(QuickPhraseSeeder::class);
    $count = QuickPhrase::count();
    $this->seed(QuickPhraseSeeder::class);

    expect($count)->toBeGreaterThan(10)->and(QuickPhrase::count())->toBe($count)->and(QuickPhrase::whereNotNull('teacher_id')->count())->toBe(0);
});
