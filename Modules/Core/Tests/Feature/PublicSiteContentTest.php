<?php

use Illuminate\Support\Facades\Storage;
use Modules\Core\Services\PublicSite;
use Modules\Core\Services\Settings;
use Modules\Core\Support\SafeHtml as CoreSafeHtml;
use Modules\Notices\Support\SafeHtml as NoticesSafeHtml;

it('falls back to the built-in menu when no nav_items are saved', function () {
    $site = app(PublicSite::class);

    expect(collect($site->nav())->pluck('route')->all())->toContain('home', 'website.about', 'admission.public')
        ->and($site->footer_nav())->toBe(array_slice($site->nav(), 1, 6));
});

it('builds the header and footer menus from nav_items', function () {
    app(Settings::class)->set('nav_items', [
        ['label_bn' => 'ভর্তি', 'label_en' => 'Admission', 'route' => 'admission.public', 'visible' => true, 'sort_order' => 2, 'location' => 'header'],
        ['label_bn' => 'হোম', 'label_en' => 'Home', 'route' => 'home', 'visible' => true, 'sort_order' => 1, 'location' => 'header'],
        ['label_bn' => 'লুকানো', 'route' => 'website.about', 'visible' => false, 'sort_order' => 3, 'location' => 'header'],
        ['label_bn' => 'ভুল', 'route' => 'no.such.route', 'visible' => true, 'sort_order' => 4, 'location' => 'header'],
        ['label_bn' => 'ফেসবুক', 'label_en' => 'Facebook', 'url' => 'https://facebook.com/x', 'visible' => true, 'sort_order' => 1, 'location' => 'footer'],
    ]);

    $site = app(PublicSite::class);

    expect(collect($site->nav())->pluck('label')->all())->toBe(['হোম', 'ভর্তি'])
        ->and($site->nav()[1]['url'])->toBe(route('admission.public'))
        ->and($site->footer_nav())->toBe([['label' => 'ফেসবুক', 'url' => 'https://facebook.com/x', 'route' => '']]);

    app()->setLocale('en');
    expect(collect($site->nav())->pluck('label')->all())->toBe(['Home', 'Admission']);
});

it('exposes the new identity details', function () {
    $identity = app(PublicSite::class)->identity();

    expect($identity['whatsapp'])->toBeNull()->and($identity['instagram'])->toBeNull()->and($identity['office_hours'])->toBe('')->and($identity['short_name'])->toBe($identity['name']);

    app(Settings::class)->setMany([
        'institute_short_name_bn' => 'আরআইসিএম', 'principal_title_bn' => 'অধ্যক্ষ', 'office_hours_bn' => 'সকাল ৮টা', 'office_hours_en' => '8 AM',
        'social_whatsapp' => '+880 1712-345678', 'social_instagram' => 'https://instagram.com/ricm',
    ]);
    $identity = app(PublicSite::class)->identity();

    expect($identity)->toMatchArray(['short_name' => 'আরআইসিএম', 'principal_title' => 'অধ্যক্ষ', 'office_hours' => 'সকাল ৮টা', 'whatsapp' => 'https://wa.me/8801712345678', 'instagram' => 'https://instagram.com/ricm']);

    app()->setLocale('en');
    expect(app(PublicSite::class)->identity()['office_hours'])->toBe('8 AM');
});

it('saves the new settings keys and validates them', function () {
    $admin = userWithRole('admin');
    $base = ['institute_name_bn' => 'মাদরাসা'];

    $this->actingAs($admin)->putJson(route('core.api.settings.update'), [...$base, 'map_lat' => 25.33, 'map_lng' => 89.54, 'social_whatsapp' => '8801712345678', 'office_hours_bn' => 'সকাল', 'institute_short_name_en' => 'RICM', 'principal_title_bn' => 'অধ্যক্ষ',
        'nav_items' => [['label_bn' => 'হোম', 'route' => 'home', 'visible' => true, 'sort_order' => 1, 'location' => 'header']]])
        ->assertOk()->assertJsonPath('data.map_lat', 25.33)->assertJsonPath('data.institute_short_name_en', 'RICM')->assertJsonPath('data.nav_items.0.route', 'home');

    $this->actingAs($admin)->putJson(route('core.api.settings.update'), [...$base, 'map_lat' => 123])->assertJsonValidationErrors('map_lat');
    $this->actingAs($admin)->putJson(route('core.api.settings.update'), [...$base, 'nav_items' => [['label_bn' => 'x', 'location' => 'sidebar']]])->assertJsonValidationErrors(['nav_items.0.location', 'nav_items.0.route']);
    $this->actingAs($admin)->putJson(route('core.api.settings.update'), [...$base, 'social_instagram' => 'not a url'])->assertJsonValidationErrors('social_instagram');
});

it('resizes uploaded settings images through the image uploader', function () {
    Storage::fake('public');
    $admin = userWithRole('admin');

    $this->actingAs($admin)->post(route('core.api.settings.upload', 'logo'), ['file' => tinyPng('logo.png')], ['Accept' => 'application/json'])->assertOk()->assertJsonStructure(['data' => ['logo_url']]);
    $first = setting('logo');
    Storage::disk('public')->assertExists($first);

    $this->actingAs($admin)->post(route('core.api.settings.upload', 'logo'), ['file' => tinyPng('logo2.png')], ['Accept' => 'application/json'])->assertOk();
    Storage::disk('public')->assertMissing($first);
});

it('keeps sanitising through the Notices alias of SafeHtml', function () {
    $dirty = '<p onclick="x()">খবর <b>ভালো</b></p><script>alert(1)</script><a href="javascript:alert(1)">খারাপ</a>';

    expect(NoticesSafeHtml::clean($dirty))->toBe(CoreSafeHtml::clean($dirty))->not->toContain('script')->not->toContain('onclick')->not->toContain('javascript:')->toContain('<b>ভালো</b>')
        ->and(NoticesSafeHtml::plain('<p>এক</p><ul><li>দুই</li></ul>'))->toBe("এক\n• দুই");
});

it('exposes the program line, localised with fallbacks and editable', function () {
    $site = app(PublicSite::class);

    expect($site->identity()['program'])->toBe('ফোনেটিক্স ও অ্যাবাকাস ব্রেইন ডেভেলপমেন্ট প্রোগ্রাম');

    app()->setLocale('en');
    expect($site->identity()['program'])->toBe('Phonetics & Abacus Brain Development Program');

    app(Settings::class)->setMany(['institute_program_bn' => 'নতুন লাইন', 'institute_program_en' => '']);
    expect($site->identity()['program'])->toBe('নতুন লাইন');

    app(Settings::class)->set('institute_program_en', 'New line');
    expect($site->identity()['program'])->toBe('New line');
});

it('saves and validates the program line setting', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->putJson(route('core.api.settings.update'), ['institute_name_bn' => 'মাদরাসা', 'institute_program_en' => 'Program'])
        ->assertOk()->assertJsonPath('data.institute_program_en', 'Program');
    $this->actingAs($admin)->putJson(route('core.api.settings.update'), ['institute_name_bn' => 'মাদরাসা', 'institute_program_bn' => str_repeat('অ', 201)])
        ->assertJsonValidationErrors('institute_program_bn');
});

it('shows only the logo in the mobile header and the name with the program line from the md breakpoint', function () {
    $identity = app(PublicSite::class)->identity();
    $html = $this->get(route('home'))->assertOk()->getContent();

    expect($html)->toContain('class="pub-brand-text"')->toContain('aria-label="'.e($identity['name']).'"')
        ->and(substr_count($html, $identity['program']))->toBeGreaterThanOrEqual(3)
        ->and($html)->toContain('pub-theme')->not->toContain("x-text=\"dark ? '☀' : '☾'\"");
});

it('shows the program line on the sign in pages', function () {
    $program = app(PublicSite::class)->identity()['program'];

    $this->get(route('login'))->assertOk()->assertSee($program);
    $this->get(route('student.login'))->assertOk()->assertSee($program);
});
