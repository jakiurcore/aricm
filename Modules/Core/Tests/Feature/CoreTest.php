<?php

use Modules\Core\Services\Settings;

it('stores settings and falls back to the config defaults', function () {
    $settings = app(Settings::class);

    expect($settings->get('institute_name_en'))->toBe('AR Rahman International Cadet Madrasa');

    $settings->set('institute_name_en', 'Custom');
    $settings->set('phones', ['017']);

    expect(app(Settings::class)->get('institute_name_en'))->toBe('Custom')
        ->and(setting('phones'))->toBe(['017']);
});

it('switches the locale through the cookie and session', function () {
    $this->get(route('login'))->assertSee('প্রবেশ করুন');

    $this->from(route('login'))->get(route('core.locale', 'en'))->assertRedirect(route('login'));

    $this->get(route('login'))->assertSee('Sign in')->assertDontSee('প্রবেশ করুন');
});

it('rejects an unknown locale', function () {
    $this->get(route('core.locale', 'fr'))->assertNotFound();
});

it('serves the admin translations with the fallback locale merged in', function () {
    $response = $this->getJson(route('core.api.translations'))->assertOk()->assertJsonPath('locale', 'bn');

    // Keys contain dots, so read the map directly instead of using assertJsonPath().
    expect($response->json('messages'))
        ->toHaveKey('core::admin.common.save', 'সংরক্ষণ করুন')
        ->toHaveKey('accounts::admin.users.title', 'ব্যবহারকারী');
});

it('shows an invalid verification page for an unknown document', function () {
    $this->get(route('core.verify', ['type' => 'nothing', 'hash' => 'abc']))
        ->assertOk()
        ->assertSee(__('core::messages.verify.not_found'));
});

it('only lets users with settings.manage read the settings API', function () {
    $this->getJson(route('core.api.settings.show'))->assertUnauthorized();

    $this->actingAs(userWithRole('teacher'))->getJson(route('core.api.settings.show'))->assertForbidden();

    $this->actingAs(userWithRole('admin'))
        ->getJson(route('core.api.settings.show'))
        ->assertOk()
        ->assertJsonPath('data.institute_name_bn', 'আর রহমান ইন্টারন্যাশনাল ক্যাডেট মাদরাসা');
});

it('updates settings with validation', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->putJson(route('core.api.settings.update'), ['institute_name_bn' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('institute_name_bn');

    $this->actingAs($admin)->putJson(route('core.api.settings.update'), [
        'institute_name_bn' => 'নতুন নাম',
        'phones' => ['01700000000'],
        'result_requires_fee_paid' => true,
    ])->assertOk();

    expect(setting('institute_name_bn'))->toBe('নতুন নাম')
        ->and(setting('result_requires_fee_paid'))->toBeTrue();
});
