<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Models\Holiday;
use Modules\Diary\Enums\DiaryStatus;
use Modules\Diary\Enums\RemarkKind;
use Modules\Diary\Models\DiaryEntry;
use Modules\Diary\Services\DiaryService;
use Modules\Diary\Services\DiarySettings;
use Modules\Teachers\Models\Teacher;
use Modules\Teachers\Services\TeacherAssignments;
use Symfony\Component\HttpKernel\Exception\HttpException;

function diary(): DiaryService
{
    return app(DiaryService::class);
}

it('saves a draft and keeps saving it into the same entry (autosave)', function () {
    $s = diarySetup();

    $first = diary()->save($s['teacher']->user, diaryPayload($s, ['lesson' => 'প্রথম']));
    $second = diary()->save($s['teacher']->user, diaryPayload($s, ['lesson' => 'দ্বিতীয়']));

    expect($second->id)->toBe($first->id)
        ->and(DiaryEntry::count())->toBe(1)
        ->and($second->fresh()->lesson)->toBe('দ্বিতীয়')
        ->and($second->status)->toBe(DiaryStatus::Draft)
        ->and($second->session_id)->toBe($s['p']['session']->id);
});

it('only lets a teacher write for the sections and subjects assigned to them', function () {
    $s = diarySetup();

    expect(fn () => diary()->save($s['teacher']->user, diaryPayload($s, ['section_id' => $s['other']->id])))->toThrow(HttpException::class)
        ->and(fn () => diary()->save($s['teacher']->user, diaryPayload($s, ['subject_id' => null])))->toThrow(HttpException::class);

    $stranger = Teacher::factory()->withLogin()->create();
    expect(fn () => diary()->save($stranger->user, diaryPayload($s)))->toThrow(HttpException::class);
});

it('lets the class teacher write the general note of the day', function () {
    $s = diarySetup();
    $classTeacher = Teacher::factory()->withLogin()->create();
    app(TeacherAssignments::class)->setClassTeacher($classTeacher, $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id);

    $entry = diary()->save($classTeacher->user, diaryPayload($s, ['subject_id' => null, 'lesson' => null, 'homework' => null, 'note' => 'কাল ছবি তোলা হবে।']));

    expect($entry->subject_id)->toBeNull()->and($entry->note)->toBe('কাল ছবি তোলা হবে।');
});

it('sets homework due on the next school day, skipping Friday and holidays', function () {
    $s = diarySetup();

    expect(diary()->save($s['teacher']->user, diaryPayload($s))->homework_due_date->toDateString())->toBe('2026-10-24');   // Thursday → Saturday

    Holiday::create(['date' => '2026-10-24', 'title_bn' => 'ছুটি']);
    expect(diary()->save($s['teacher']->user, diaryPayload($s))->homework_due_date->toDateString())->toBe('2026-10-25');

    // A due date typed by the teacher is honoured when it is a school day and moved to the next one when it is not.
    expect(diary()->save($s['teacher']->user, diaryPayload($s, ['homework_due_date' => '2026-10-27']))->homework_due_date->toDateString())->toBe('2026-10-27')
        ->and(diary()->save($s['teacher']->user, diaryPayload($s, ['homework_due_date' => '2026-10-30']))->homework_due_date->toDateString())->toBe('2026-10-31');   // Friday → Saturday
});

it('has no due date when there is no homework', function () {
    $s = diarySetup();

    expect(diary()->save($s['teacher']->user, diaryPayload($s, ['homework' => '  ']))->homework_due_date)->toBeNull();
});

it('refuses future dates, dates too far back and holidays', function () {
    $s = diarySetup();

    expect(fn () => diary()->save($s['teacher']->user, diaryPayload($s, ['entry_date' => '2026-10-24'])))->toThrow(ValidationException::class)
        ->and(fn () => diary()->save($s['teacher']->user, diaryPayload($s, ['entry_date' => '2026-10-18'])))->toThrow(ValidationException::class)
        ->and(fn () => diary()->save($s['teacher']->user, diaryPayload($s, ['entry_date' => '2026-10-23'])))->toThrow(ValidationException::class);   // Friday

    // Two days back is still allowed (Tuesday).
    expect(diary()->save($s['teacher']->user, diaryPayload($s, ['entry_date' => '2026-10-20']))->entry_date->toDateString())->toBe('2026-10-20');

    Holiday::create(['date' => '2026-10-21', 'title_bn' => 'ছুটি']);
    expect(fn () => diary()->save($s['teacher']->user, diaryPayload($s, ['entry_date' => '2026-10-21'])))->toThrow(ValidationException::class);
});

it('will not publish an empty entry', function () {
    $s = diarySetup();
    $entry = diary()->save($s['teacher']->user, diaryPayload($s, ['lesson' => '', 'homework' => '']));

    expect(fn () => diary()->publish($s['teacher']->user, $entry))->toThrow(ValidationException::class);
});

it('lets the owner edit a published entry inside the edit window only, and logs every revision', function () {
    $s = diarySetup();
    $entry = diary()->save($s['teacher']->user, diaryPayload($s));
    diary()->publish($s['teacher']->user, $entry);

    $edited = diary()->save($s['teacher']->user, diaryPayload($s, ['lesson' => 'সংশোধিত পড়া']));
    expect($edited->last_edited_at)->not->toBeNull()
        ->and($entry->activitiesAsSubject()->count())->toBeGreaterThanOrEqual(2);

    Carbon::setTestNow('2026-10-24 10:00:00');   // 72 hours later, window is 48
    $s['teacher']->user->unsetRelation('roles');
    expect(diary()->canEdit($s['teacher']->user, $entry->fresh()))->toBeFalse();
});

it('lets a diary manager edit and delete anything', function () {
    $s = diarySetup();
    $entry = diary()->save($s['teacher']->user, diaryPayload($s));
    diary()->publish($s['teacher']->user, $entry);
    Carbon::setTestNow('2026-10-26 10:00:00');
    $admin = userWithRole('admin');

    expect(diary()->canEdit($admin, $entry->fresh()))->toBeTrue()
        ->and(diary()->canDelete($admin, $entry->fresh()))->toBeTrue()
        ->and(diary()->canDelete($s['teacher']->user, $entry->fresh()))->toBeFalse();
});

it('stores attachments, limits their number and removes the files with the entry', function () {
    Storage::fake('public');
    $s = diarySetup();
    $entry = diary()->save($s['teacher']->user, diaryPayload($s));

    $attachment = diary()->addAttachment($s['teacher']->user, $entry, UploadedFile::fake()->create('worksheet.pdf', 100, 'application/pdf'));
    Storage::disk('public')->assertExists($attachment->path);

    app(DiarySettings::class)->update(['max_attachments' => 1]);
    expect(fn () => diary()->addAttachment($s['teacher']->user, $entry, UploadedFile::fake()->create('two.pdf', 10, 'application/pdf')))->toThrow(ValidationException::class);

    diary()->delete($s['teacher']->user, $entry);
    Storage::disk('public')->assertMissing($attachment->path);
});

it('adds remarks only for students of the entry’s section, by the owner or the class teacher', function () {
    $s = diarySetup();
    $entry = diary()->save($s['teacher']->user, diaryPayload($s));
    $outsider = enrolled(['session' => $s['p']['session'], 'class' => $s['p']['class'], 'section' => $s['other']]);
    $classTeacher = Teacher::factory()->withLogin()->create();
    app(TeacherAssignments::class)->setClassTeacher($classTeacher, $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id);
    $stranger = Teacher::factory()->withLogin()->create();

    expect(diary()->addRemark($s['teacher']->user, $entry, $s['student'], RemarkKind::Praise, 'ভালো করেছে')->kind)->toBe(RemarkKind::Praise)
        ->and(diary()->addRemark($classTeacher->user, $entry, $s['student'], RemarkKind::Info, 'তথ্য')->teacher_id)->toBe($classTeacher->id)
        ->and(fn () => diary()->addRemark($s['teacher']->user, $entry, $outsider, RemarkKind::Info, 'x'))->toThrow(ValidationException::class)
        ->and(fn () => diary()->addRemark($stranger->user, $entry, $s['student'], RemarkKind::Info, 'x'))->toThrow(HttpException::class);
});
