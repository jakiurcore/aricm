<?php

use Illuminate\Support\Carbon;
use Modules\Diary\Enums\RemarkKind;
use Modules\Diary\Services\DiaryFeed;
use Modules\Diary\Services\DiaryService;

/**
 * A published entry for the setup's student's section today.
 */
function publishedEntry(array $s, array $overrides = [])
{
    $entry = app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s, $overrides));

    return app(DiaryService::class)->publish($s['teacher']->user, $entry);
}

it('shows a student only published entries of their own section', function () {
    $s = diarySetup();
    $feed = app(DiaryFeed::class);

    app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s));
    expect($feed->forDay($s['student'], Carbon::parse('2026-10-22')))->toHaveCount(0);

    publishedEntry($s);
    expect($feed->forDay($s['student'], Carbon::parse('2026-10-22')))->toHaveCount(1)
        ->and($feed->forDay($s['student'], Carbon::parse('2026-10-21')))->toHaveCount(0);

    $other = enrolled(['session' => $s['p']['session'], 'class' => $s['p']['class'], 'section' => $s['other']]);
    expect($feed->forDay($other, Carbon::parse('2026-10-22')))->toHaveCount(0);
});

it('shows a student only their own remarks', function () {
    $s = diarySetup();
    $classmate = enrolled($s['p'], ['name_bn' => 'করিম']);
    $entry = publishedEntry($s);
    app(DiaryService::class)->addRemark($s['teacher']->user, $entry, $s['student'], RemarkKind::Praise, 'ভালো');
    app(DiaryService::class)->addRemark($s['teacher']->user, $entry, $classmate, RemarkKind::Concern, 'মনোযোগ নেই');

    $mine = app(DiaryFeed::class)->forDay($s['student'], Carbon::parse('2026-10-22'))->first()->remarks;
    $theirs = app(DiaryFeed::class)->forDay($classmate, Carbon::parse('2026-10-22'))->first()->remarks;

    expect($mine)->toHaveCount(1)->and($mine->first()->text)->toBe('ভালো')
        ->and($theirs->first()->text)->toBe('মনোযোগ নেই');
});

it('counts unseen entries and clears them once seen', function () {
    $s = diarySetup();
    $feed = app(DiaryFeed::class);
    publishedEntry($s);

    expect($feed->unseenCount($s['student']))->toBe(1);

    $feed->markSeen($s['student'], $feed->forDay($s['student'], Carbon::parse('2026-10-22')));

    expect($feed->unseenCount($s['student']))->toBe(0);
});

it('records an acknowledgement once and only for entries the student may see', function () {
    $s = diarySetup();
    $feed = app(DiaryFeed::class);
    $entry = publishedEntry($s);
    $other = enrolled(['session' => $s['p']['session'], 'class' => $s['p']['class'], 'section' => $s['other']]);

    expect($feed->acknowledge($other, $entry))->toBeFalse()
        ->and($feed->acknowledge($s['student'], $entry))->toBeTrue()
        ->and($feed->acknowledge($s['student'], $entry))->toBeTrue()
        ->and($entry->acknowledgements()->count())->toBe(1)
        ->and($entry->acknowledgements()->first()->acknowledged_at)->not->toBeNull()
        ->and($feed->unseenCount($s['student']))->toBe(0);
});

it('builds a Saturday-to-Friday week strip that marks Friday as a holiday', function () {
    $s = diarySetup();
    publishedEntry($s);

    $week = app(DiaryFeed::class)->week($s['student'], Carbon::parse('2026-10-22'));

    expect($week)->toHaveCount(7)
        ->and($week[0]['date']->toDateString())->toBe('2026-10-17')
        ->and($week[6]['date']->toDateString())->toBe('2026-10-23')
        ->and($week[6]['is_school_day'])->toBeFalse()
        ->and($week[5]['entries'])->toBe(1)
        ->and($week[5]['unseen'])->toBe(1);
});

it('acknowledges a whole day in one go', function () {
    $s = diarySetup();
    publishedEntry($s);

    expect(app(DiaryFeed::class)->acknowledgeDay($s['student'], Carbon::parse('2026-10-22')))->toBe(1);
});
