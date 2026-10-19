<?php

use Illuminate\Support\Carbon;
use Modules\Diary\Services\DiaryCompliance;
use Modules\Diary\Services\DiaryService;
use Modules\Teachers\Models\Teacher;
use Modules\Teachers\Services\TeacherAssignments;

it('reports expected, published, draft and missing entries for a school day', function () {
    $s = diarySetup();
    $second = Teacher::factory()->withLogin()->create();
    $third = Teacher::factory()->withLogin()->create();
    $assign = app(TeacherAssignments::class);
    $assign->assignSubject($second, $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id, $s['subject']->id);
    $assign->assignSubject($third, $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id, $s['subject']->id);

    $first = app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s));
    app(DiaryService::class)->publish($s['teacher']->user, $first);
    app(DiaryService::class)->save($second->user, diaryPayload($s));

    $report = app(DiaryCompliance::class)->forDate(Carbon::parse('2026-10-22'));

    expect($report['is_school_day'])->toBeTrue()
        ->and($report['summary'])->toBe(['expected' => 3, 'published' => 1, 'draft' => 1, 'missing' => 1, 'percent' => 33])
        ->and(collect($report['rows'])->pluck('status')->sort()->values()->all())->toBe(['draft', 'missing', 'published'])
        ->and(app(DiaryCompliance::class)->missing(Carbon::parse('2026-10-22')))->toHaveCount(2);
});

it('expects nothing on a holiday', function () {
    $s = diarySetup();

    $report = app(DiaryCompliance::class)->forDate(Carbon::parse('2026-10-23'));   // Friday

    expect($report['is_school_day'])->toBeFalse()->and($report['rows'])->toBe([])->and($report['summary']['expected'])->toBe(0);
});

it('ignores inactive teachers and assignments of another session', function () {
    $s = diarySetup();
    $s['teacher']->update(['is_active' => false]);

    expect(app(DiaryCompliance::class)->forDate(Carbon::parse('2026-10-22'))['summary']['expected'])->toBe(0);
});

it('adds up each teacher over a range of school days', function () {
    $s = diarySetup();
    $entry = app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s));
    app(DiaryService::class)->publish($s['teacher']->user, $entry);

    // Wed 21, Thu 22 → two school days, one written.
    $rows = app(DiaryCompliance::class)->byTeacher(Carbon::parse('2026-10-21'), Carbon::parse('2026-10-22'));

    expect($rows)->toHaveCount(1)->and($rows[0])->toMatchArray(['expected' => 2, 'published' => 1, 'percent' => 50]);
});
