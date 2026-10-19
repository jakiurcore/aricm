<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Services\Sms\SmsManager;
use Modules\Diary\Models\DiaryEntry;
use Modules\Diary\Models\QuickPhrase;
use Modules\Diary\Services\DiaryService;
use Modules\Diary\Services\DiarySettings;
use Modules\Teachers\Models\Teacher;

it('autosaves through the teacher endpoint and loads the entry back', function () {
    $s = diarySetup();

    $id = $this->actingAs($s['teacher']->user)->putJson(route('diary.teacher.save'), diaryPayload($s))->assertOk()->assertJsonPath('data.status', 'draft')->json('data.id');
    $this->actingAs($s['teacher']->user)->putJson(route('diary.teacher.save'), diaryPayload($s, ['lesson' => 'নতুন']))->assertOk()->assertJsonPath('data.id', $id);

    $this->actingAs($s['teacher']->user)->getJson(route('diary.teacher.show', ['class_id' => $s['p']['class']->id, 'section_id' => $s['p']['section']->id, 'subject_id' => $s['subject']->id, 'date' => '2026-10-22']))
        ->assertOk()->assertJsonPath('data.id', $id)->assertJsonPath('data.lesson', 'নতুন')->assertJsonPath('data.homework_due_date', '2026-10-24');

    expect(DiaryEntry::count())->toBe(1);
});

it('saves and publishes in one request', function () {
    $s = diarySetup();

    $this->actingAs($s['teacher']->user)->putJson(route('diary.teacher.save'), [...diaryPayload($s), 'publish' => true])->assertOk()->assertJsonPath('data.status', 'published');
});

it('refuses a teacher writing outside their assignment and a guest or student user', function () {
    $s = diarySetup();

    $this->putJson(route('diary.teacher.save'), diaryPayload($s))->assertUnauthorized();
    $this->actingAs($s['teacher']->user)->putJson(route('diary.teacher.save'), diaryPayload($s, ['section_id' => $s['other']->id]))->assertForbidden();
    $this->actingAs(userWithRole('accountant'))->putJson(route('diary.teacher.save'), diaryPayload($s))->assertForbidden();
});

it('validates the diary payload', function () {
    $s = diarySetup();

    $this->actingAs($s['teacher']->user)->putJson(route('diary.teacher.save'), diaryPayload($s, ['entry_date' => '2026-10-23']))->assertUnprocessable()->assertJsonValidationErrors('entry_date');
    $this->actingAs($s['teacher']->user)->putJson(route('diary.teacher.save'), diaryPayload($s, ['entry_date' => 'soon']))->assertJsonValidationErrors('entry_date');
});

it('keeps one teacher out of another teacher’s entry', function () {
    $s = diarySetup();
    $entry = app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s));
    $stranger = Teacher::factory()->withLogin()->create();

    $this->actingAs($stranger->user)->postJson(route('diary.teacher.publish', $entry))->assertForbidden();
    $this->actingAs($stranger->user)->deleteJson(route('diary.teacher.destroy', $entry))->assertForbidden();
    $this->actingAs($stranger->user)->postJson(route('diary.teacher.remark', $entry), ['student_id' => $s['student']->id, 'kind' => 'info', 'text' => 'x'])->assertForbidden();
});

it('uploads and removes an attachment', function () {
    Storage::fake('public');
    $s = diarySetup();
    $entry = app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s));

    $id = $this->actingAs($s['teacher']->user)->postJson(route('diary.teacher.attach', $entry), ['file' => UploadedFile::fake()->create('sheet.pdf', 50, 'application/pdf')])->assertCreated()->json('data.id');
    $this->actingAs($s['teacher']->user)->postJson(route('diary.teacher.attach', $entry), ['file' => UploadedFile::fake()->create('virus.exe', 50)])->assertJsonValidationErrors('file');
    $this->actingAs($s['teacher']->user)->deleteJson(route('diary.teacher.detach', $id))->assertNoContent();

    expect($entry->attachments()->count())->toBe(0);
});

it('adds and removes a student remark', function () {
    $s = diarySetup();
    $entry = app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s));

    $id = $this->actingAs($s['teacher']->user)->postJson(route('diary.teacher.remark', $entry), ['student_id' => $s['student']->id, 'kind' => 'praise', 'text' => 'চমৎকার'])->assertCreated()->json('data.id');
    $this->actingAs($s['teacher']->user)->postJson(route('diary.teacher.remark', $entry), ['student_id' => $s['student']->id, 'kind' => 'nope', 'text' => 'x'])->assertJsonValidationErrors('kind');
    $this->actingAs($s['teacher']->user)->deleteJson(route('diary.teacher.unremark', $id))->assertNoContent();
});

it('serves shared and own quick phrases, and lets a teacher delete only their own', function () {
    $s = diarySetup();
    $shared = QuickPhrase::create(['field' => 'lesson', 'text_bn' => 'শেয়ার্ড', 'teacher_id' => null]);

    $own = $this->actingAs($s['teacher']->user)->postJson(route('diary.teacher.phrases.add'), ['field' => 'homework', 'text_bn' => 'আমার বাক্য'])->assertCreated()->json('data.id');
    $this->actingAs($s['teacher']->user)->getJson(route('diary.teacher.phrases'))->assertOk()->assertJsonPath('data.lesson.0.text', 'শেয়ার্ড')->assertJsonPath('data.homework.0.own', true);

    $other = Teacher::factory()->withLogin()->create();
    $this->actingAs($other->user)->getJson(route('diary.teacher.phrases'))->assertJsonMissingPath('data.homework');
    $this->actingAs($other->user)->deleteJson(route('diary.teacher.phrases.remove', $own))->assertForbidden();
    $this->actingAs($s['teacher']->user)->deleteJson(route('diary.teacher.phrases.remove', $shared))->assertForbidden();
    $this->actingAs($s['teacher']->user)->deleteJson(route('diary.teacher.phrases.remove', $own))->assertNoContent();
});

it('lets the office watch compliance, entries and revisions but not teachers', function () {
    $s = diarySetup();
    $entry = app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s));
    $admin = userWithRole('admin');

    $this->actingAs($admin)->getJson(route('diary.api.compliance', ['date' => '2026-10-22']))->assertOk()->assertJsonPath('data.summary.expected', 1)->assertJsonPath('data.summary.draft', 1);
    $this->actingAs($admin)->getJson(route('diary.api.entries.index', ['date' => '2026-10-22']))->assertOk()->assertJsonCount(1, 'data');
    $this->actingAs($admin)->getJson(route('diary.api.entries.revisions', $entry))->assertOk()->assertJsonCount(1, 'data');
    $this->actingAs($admin)->getJson(route('diary.api.compliance.teachers'))->assertOk();

    $this->actingAs($s['teacher']->user)->getJson(route('diary.api.compliance'))->assertForbidden();
    $this->actingAs(userWithRole('accountant'))->getJson(route('diary.api.entries.index'))->assertForbidden();
});

it('lets only a diary manager change settings, delete entries and edit shared phrases', function () {
    $s = diarySetup();
    $entry = app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s));
    $admin = userWithRole('admin');
    $values = ['enabled' => true, 'edit_window_hours' => 24, 'backdate_days' => 1, 'max_attachments' => 3, 'sms_enabled' => true, 'sms_teacher_reminder' => false];

    $this->actingAs($admin)->putJson(route('diary.api.settings.update'), $values)->assertOk()->assertJsonPath('data.edit_window_hours', 24)->assertJsonPath('data.sms_enabled', true);
    $this->actingAs($admin)->putJson(route('diary.api.settings.update'), [...$values, 'edit_window_hours' => -1])->assertJsonValidationErrors('edit_window_hours');
    $this->actingAs($s['teacher']->user)->putJson(route('diary.api.settings.update'), $values)->assertForbidden();

    $id = $this->actingAs($admin)->postJson(route('diary.api.phrases.store'), ['field' => 'note', 'text_bn' => 'সবার জন্য'])->assertCreated()->json('data.id');
    $this->actingAs($admin)->putJson(route('diary.api.phrases.update', $id), ['field' => 'note', 'text_bn' => 'বদলানো'])->assertOk();
    $this->actingAs($admin)->deleteJson(route('diary.api.phrases.destroy', $id))->assertNoContent();

    $this->actingAs($admin)->deleteJson(route('diary.api.entries.destroy', $entry))->assertNoContent();
});

it('keeps tomorrow’s reading and accepts a remark without text', function () {
    $s = diarySetup();

    $id = $this->actingAs($s['teacher']->user)->putJson(route('diary.teacher.save'), diaryPayload($s, ['next_lesson' => 'পরের পাঠ পড়বে']))->assertOk()->assertJsonPath('data.next_lesson', 'পরের পাঠ পড়বে')->json('data.id');
    $this->actingAs($s['teacher']->user)->postJson(route('diary.teacher.remark', $id), ['student_id' => $s['student']->id, 'kind' => 'no_homework'])->assertCreated();
});

it('shows one entry in full and lets a diary manager correct it', function () {
    $s = diarySetup();
    $entry = app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s));
    app(DiaryService::class)->publish($s['teacher']->user, $entry);
    $admin = userWithRole('admin');

    $this->actingAs($admin)->getJson(route('diary.api.entries.show', $entry))->assertOk()->assertJsonPath('data.lesson', 'আজ অধ্যায় ১ পড়ানো হয়েছে।')->assertJsonPath('data.total', 1)->assertJsonPath('data.edited', false);
    $this->actingAs($admin)->putJson(route('diary.api.entries.update', $entry), ['lesson' => 'সংশোধিত পাঠ', 'homework' => ''])->assertOk()->assertJsonPath('data.lesson', 'সংশোধিত পাঠ')->assertJsonPath('data.homework', null)->assertJsonPath('data.edited', true);

    $this->actingAs($s['teacher']->user)->putJson(route('diary.api.entries.update', $entry), ['lesson' => 'x'])->assertForbidden();
    $this->actingAs(userWithRole('accountant'))->getJson(route('diary.api.entries.show', $entry))->assertForbidden();
});

it('reminds the teachers who still owe an entry', function () {
    $s = diarySetup();
    $sent = [];
    $this->mock(SmsManager::class, function ($mock) use (&$sent): void {
        $mock->shouldReceive('send')->andReturnUsing(function (string $to, string $message) use (&$sent): bool {
            $sent[] = $to;

            return true;
        });
    });
    $admin = userWithRole('admin');

    $this->actingAs($admin)->postJson(route('diary.api.remind'), ['date' => '2026-10-22'])->assertOk()->assertJsonPath('data.sent', 1);
    expect($sent)->toBe([$s['teacher']->phone]);

    // Friday is the weekly holiday, so nobody owes anything.
    $this->actingAs($admin)->postJson(route('diary.api.remind'), ['date' => '2026-10-23'])->assertOk()->assertJsonPath('data.sent', 0);
    $this->actingAs($s['teacher']->user)->postJson(route('diary.api.remind'))->assertForbidden();
});

it('ends the edit window the way the setting says', function () {
    $s = diarySetup();
    $entry = app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s));
    app(DiaryService::class)->publish($s['teacher']->user, $entry);
    $settings = app(DiarySettings::class);
    $service = app(DiaryService::class);

    $settings->update(['edit_window' => 'same']);
    expect($service->canEdit($s['teacher']->user, $entry->refresh()))->toBeTrue();
    Carbon::setTestNow('2026-10-23 00:30:00');
    expect($service->canEdit($s['teacher']->user, $entry))->toBeFalse();

    $settings->update(['edit_window' => 'next9']);
    Carbon::setTestNow('2026-10-23 08:59:00');
    expect($service->canEdit($s['teacher']->user, $entry))->toBeTrue();
    Carbon::setTestNow('2026-10-23 09:01:00');
    expect($service->canEdit($s['teacher']->user, $entry))->toBeFalse();

    $settings->update(['edit_window' => 'none']);
    expect($service->canEdit($s['teacher']->user, $entry))->toBeTrue();
});

it('saves the diary settings of the admin page', function () {
    $admin = userWithRole('admin');
    $values = ['enabled' => true, 'edit_window_hours' => 48, 'backdate_days' => 2, 'max_attachments' => 5, 'sms_enabled' => false, 'sms_teacher_reminder' => false];

    $this->actingAs($admin)->putJson(route('diary.api.settings.update'), [...$values, 'edit_window' => '2h', 'student_sees_future' => true, 'max_attachment_mb' => 8])->assertOk()
        ->assertJsonPath('data.edit_window', '2h')->assertJsonPath('data.student_sees_future', true)->assertJsonPath('data.max_attachment_mb', 8);
    $this->actingAs($admin)->putJson(route('diary.api.settings.update'), [...$values, 'edit_window' => 'whenever'])->assertJsonValidationErrors('edit_window');
    $this->actingAs($admin)->putJson(route('diary.api.settings.update'), [...$values, 'max_attachment_mb' => 99])->assertJsonValidationErrors('max_attachment_mb');
});
