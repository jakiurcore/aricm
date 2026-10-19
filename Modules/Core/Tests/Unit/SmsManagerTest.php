<?php

use Illuminate\Support\Facades\Log;
use Modules\Core\Services\Sms\SmsManager;

it('normalises Bangladeshi mobile numbers', function (string $input, ?string $expected) {
    expect(SmsManager::normalize($input))->toBe($expected);
})->with([
    ['01712345678', '8801712345678'],
    ['+8801712345678', '8801712345678'],
    ['8801712345678', '8801712345678'],
    ['০১৭১২৩৪৫৬৭৮', '8801712345678'],
    ['017-1234-5678', '8801712345678'],
    ['0212345678', null],
    ['', null],
]);

it('logs messages with the default driver and refuses invalid numbers', function () {
    Log::spy();

    expect(app(SmsManager::class)->send('01712345678', 'হ্যালো'))->toBeTrue()
        ->and(app(SmsManager::class)->send('123', 'x'))->toBeFalse();

    Log::shouldHaveReceived('info')->once();
});

it('does not pretend to send with an unconfigured bulk provider', function () {
    config(['modules.core.sms.driver' => 'bulk', 'modules.core.sms.bulk.url' => null]);
    Log::spy();

    expect(app(SmsManager::class)->send('01712345678', 'x'))->toBeFalse();
    Log::shouldHaveReceived('warning')->once();
});
