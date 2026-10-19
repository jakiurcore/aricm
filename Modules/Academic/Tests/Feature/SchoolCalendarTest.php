<?php

use Illuminate\Support\Carbon;
use Modules\Academic\Models\Holiday;
use Modules\Academic\Services\SchoolCalendar;
use Modules\Core\Services\Settings;

function calendar(): SchoolCalendar
{
    return app(SchoolCalendar::class);
}

it('treats Friday as the default weekly holiday', function () {
    expect(calendar()->weeklyHolidays())->toBe(['friday'])
        ->and(calendar()->isSchoolDay(Carbon::parse('2026-10-22')))->toBeTrue()    // Thursday
        ->and(calendar()->isSchoolDay(Carbon::parse('2026-10-23')))->toBeFalse()   // Friday
        ->and(calendar()->isSchoolDay(Carbon::parse('2026-10-24')))->toBeTrue();   // Saturday
});

it('skips Friday when finding the next school day', function () {
    expect(calendar()->nextSchoolDay(Carbon::parse('2026-10-22'))->toDateString())->toBe('2026-10-24')   // Thursday → Saturday
        ->and(calendar()->nextSchoolDay(Carbon::parse('2026-10-21'))->toDateString())->toBe('2026-10-22');
});

it('skips holiday calendar dates as well as Fridays', function () {
    Holiday::create(['date' => '2026-10-24', 'title_bn' => 'বিশেষ ছুটি']);
    Holiday::create(['date' => '2026-10-25', 'title_bn' => 'ছুটি']);

    expect(calendar()->nextSchoolDay(Carbon::parse('2026-10-22'))->toDateString())->toBe('2026-10-26');   // Fri, Sat, Sun skipped
});

it('moves back to the previous school day too', function () {
    expect(calendar()->previousSchoolDay(Carbon::parse('2026-10-24'))->toDateString())->toBe('2026-10-22');   // Saturday → Thursday
});

it('follows a changed weekly holiday setting', function () {
    app(Settings::class)->set('academic_weekly_holidays', ['friday', 'saturday']);

    expect(calendar()->isSchoolDay(Carbon::parse('2026-10-24')))->toBeFalse()
        ->and(calendar()->nextSchoolDay(Carbon::parse('2026-10-22'))->toDateString())->toBe('2026-10-25');
});

it('does not loop forever when every day is a holiday', function () {
    app(Settings::class)->set('academic_weekly_holidays', SchoolCalendar::WEEKDAYS);

    expect(calendar()->nextSchoolDay(Carbon::parse('2026-10-22')))->toBeInstanceOf(Carbon::class);
});

it('manages holidays and weekly days through the API', function () {
    $admin = userWithRole('admin');

    $id = $this->actingAs($admin)->postJson(route('academic.api.holidays.store'), ['date' => '2026-10-28', 'title_bn' => 'দুর্গাপূজা'])->assertCreated()->json('data.id');
    $this->actingAs($admin)->postJson(route('academic.api.holidays.store'), ['date' => '2026-10-28', 'title_bn' => 'আবার'])->assertJsonValidationErrors('date');
    $this->actingAs($admin)->getJson(route('academic.api.holidays.index', ['year' => 2026]))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('weekly.0', 'friday');

    $this->actingAs($admin)->putJson(route('academic.api.holidays.weekly'), ['days' => ['friday', 'saturday']])->assertOk();
    expect(calendar()->weeklyHolidays())->toBe(['friday', 'saturday']);
    $this->actingAs($admin)->putJson(route('academic.api.holidays.weekly'), ['days' => ['funday']])->assertJsonValidationErrors('days.0');

    $this->actingAs($admin)->deleteJson(route('academic.api.holidays.destroy', $id))->assertNoContent();
    expect(Holiday::count())->toBe(0);
});

it('lets an accountant read the calendar but not change it', function () {
    $accountant = userWithRole('accountant');

    $this->actingAs($accountant)->getJson(route('academic.api.holidays.index'))->assertOk();
    $this->actingAs($accountant)->postJson(route('academic.api.holidays.store'), ['date' => '2026-10-28', 'title_bn' => 'x'])->assertForbidden();
});
