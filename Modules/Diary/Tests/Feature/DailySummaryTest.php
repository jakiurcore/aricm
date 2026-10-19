<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Modules\Diary\Jobs\SendDailyDiarySummary;
use Modules\Diary\Services\DailyDiarySummary;
use Modules\Diary\Services\DiaryService;
use Modules\Diary\Services\DiarySettings;
use Modules\Teachers\Models\Teacher;
use Modules\Teachers\Services\TeacherAssignments;

it('sends nothing while SMS is off, which is the default', function () {
    $s = diarySetup();
    $entry = app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s));
    app(DiaryService::class)->publish($s['teacher']->user, $entry);

    expect(app(DailyDiarySummary::class)->send(Carbon::parse('2026-10-22')))->toBe(['guardians' => 0, 'teachers' => 0]);
});

it('messages the guardian of each student in a section that has published entries', function () {
    Log::spy();
    $s = diarySetup();
    $s['student']->update(['guardian_phone' => '01712345678']);
    $withoutPhone = enrolled($s['p'], ['name_bn' => 'করিম']);
    $withoutPhone->update(['guardian_phone' => null]);
    app(DiarySettings::class)->update(['sms_enabled' => true]);
    $entry = app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s));
    app(DiaryService::class)->publish($s['teacher']->user, $entry);

    $sent = app(DailyDiarySummary::class)->send(Carbon::parse('2026-10-22'));

    expect($sent['guardians'])->toBe(1);
});

it('does not send on a holiday or for a draft', function () {
    $s = diarySetup();
    $s['student']->update(['guardian_phone' => '01712345678']);
    app(DiarySettings::class)->update(['sms_enabled' => true]);
    app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s));

    expect(app(DailyDiarySummary::class)->send(Carbon::parse('2026-10-22'))['guardians'])->toBe(0)
        ->and(app(DailyDiarySummary::class)->send(Carbon::parse('2026-10-23'))['guardians'])->toBe(0);
});

it('reminds teachers who have not finished, one message each', function () {
    $s = diarySetup();
    $s['teacher']->update(['phone' => '01811111111']);
    $second = Teacher::factory()->withLogin()->create(['phone' => '01822222222']);
    app(TeacherAssignments::class)->assignSubject($second, $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id, $s['subject']->id);
    app(DiarySettings::class)->update(['sms_teacher_reminder' => true]);
    $entry = app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s));
    app(DiaryService::class)->publish($s['teacher']->user, $entry);

    expect(app(DailyDiarySummary::class)->send(Carbon::parse('2026-10-22'))['teachers'])->toBe(1);
});

it('queues the summary from the artisan command', function () {
    Queue::fake();

    $this->artisan('diary:send-daily-summary', ['--date' => '2026-10-22'])->assertSuccessful();

    Queue::assertPushed(SendDailyDiarySummary::class, fn ($job) => $job->date === '2026-10-22');
});

it('is scheduled for 6 PM every day', function () {
    $events = collect(app(Schedule::class)->events())->filter(fn ($event) => str_contains($event->command, 'diary:send-daily-summary'));

    expect($events)->toHaveCount(1)->and($events->first()->expression)->toBe('0 18 * * *');
});
