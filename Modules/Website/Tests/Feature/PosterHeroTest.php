<?php

use Modules\Core\Services\PublicSite;
use Modules\Core\Services\Settings;
use Modules\Website\Models\WebsiteItem;
use Modules\Website\Support\PosterHeroMigration;

function heroHtml(): string
{
    return test()->get('/')->assertOk()->getContent();
}

it('renders the poster hero with the institute name as the only H1', function () {
    $html = heroHtml();
    $name = app(PublicSite::class)->identity()['name'];

    expect(preg_match_all('/<h1[ >]/', $html))->toBe(1)
        ->and($html)->toMatch('/<h1[^>]*>\s*'.preg_quote(e($name), '/').'\s*<\/h1>/u');

    test()->get('/')->assertSee('শিখি ফোনেটিক্স ও অ্যাবাকাস, হই জিনিয়াস')->assertSee('pub-hero-ribbon', false)
        ->assertSee('লেংগা বাজার')->assertSee('ভর্তি চলছে', false)->assertSee('pub-hero-free', false)
        ->assertSee('প্রতিষ্ঠাতা ও পরিচালক:')->assertSee('মোঃ আতাউর রহমান')->assertSee('images/site/founder.svg', false)
        ->assertSee('tel:01761781338', false)->assertSee('pub-callbar', false)
        ->assertSee('স্মৃতিশক্তি বৃদ্ধি')->assertSee('মেধার বিকাশ ও আত্মবিশ্বাস বৃদ্ধি');
});

it('hides the admission banner and the free class badge when the admission banner is off', function () {
    app(Settings::class)->set('admission_banner_enabled', false);

    test()->get('/')->assertOk()->assertDontSee('pub-hero-banner', false)->assertDontSee('pub-hero-free', false)->assertSee('pub-hero-title', false);
});

it('hides the free class badge when neither the note nor the class start is set', function () {
    app(Settings::class)->setMany(['admission_free_class_note_bn' => '', 'admission_free_class_note_en' => '', 'admission_class_start' => '']);

    test()->get('/')->assertDontSee('pub-hero-free', false)->assertSee('pub-hero-banner', false);
});

it('shows the free class note and the class start date from the admission settings', function () {
    app(Settings::class)->setMany(['admission_free_class_note_bn' => 'বিশেষ ফ্রি ক্লাস', 'admission_class_start' => '2026-11-01']);

    test()->get('/')->assertSee('বিশেষ ফ্রি ক্লাস')->assertSee(bn_date('2026-11-01'));
});

it('lets the admin override the founder and the admission banner text', function () {
    $admin = userWithRole('admin');

    test()->actingAs($admin)->putJson(route('website.api.content.update'), ['values' => [
        'hero.founder_name' => ['bn' => 'অন্য নাম'], 'hero.admission_title' => ['bn' => 'এখনই ভর্তি'],
    ]])->assertOk();

    test()->get('/')->assertSee('অন্য নাম')->assertSee('এখনই ভর্তি')->assertDontSee('মোঃ আতাউর রহমান');
});

it('has no call buttons without a phone number', function () {
    app(Settings::class)->set('phones', []);

    test()->get('/')->assertOk()->assertDontSee('pub-callbar', false)->assertDontSee('pub-hero-callpill', false)->assertDontSee('pub-hero-callbtn', false);
});

it('renders the hero in English', function () {
    test()->from('/')->get(route('core.locale', 'en'));

    test()->get('/')->assertSee('Learn phonetics &amp; abacus, become a genius', false)->assertSee('Founder &amp; director:', false)
        ->assertSee('Better memory')->assertSee('Admission is open');
});

it('never mentions Brain Gym on any public page', function (string $route) {
    foreach (['bn', 'en'] as $locale) {
        test()->from('/')->get(route('core.locale', $locale));
        $html = test()->get(route($route))->assertOk()->getContent();

        expect($html)->not->toContain('ব্রেইন জিম')->and(strtolower($html))->not->toContain('brain gym');
    }
})->with(['home', 'website.about', 'website.academics', 'admission.public']);

it('moves the seeded programs to the poster wording only while they are untouched', function () {
    WebsiteItem::query()->where('collection', 'programs')->delete();

    foreach (PosterHeroMigration::OLD_PROGRAMS as $index => [$bn, $en]) {
        WebsiteItem::create(['collection' => 'programs', 'title_bn' => $bn, 'title_en' => $en, 'sort_order' => $index + 1]);
    }

    expect(PosterHeroMigration::rewritePrograms())->toBeTrue()
        ->and(site_content()->items('programs')->pluck('title_bn')->all())->toBe(trans('website::messages.program.benefits', [], 'bn'))
        ->and(site_content()->items('programs')->pluck('title_en')->all())->toBe(trans('website::messages.program.benefits', [], 'en'));
});

it('keeps client-edited programs as they are', function () {
    WebsiteItem::query()->where('collection', 'programs')->delete();

    foreach (PosterHeroMigration::OLD_PROGRAMS as $index => [$bn, $en]) {
        WebsiteItem::create(['collection' => 'programs', 'title_bn' => $index === 2 ? 'আমার লেখা' : $bn, 'title_en' => $en, 'sort_order' => $index + 1]);
    }

    expect(PosterHeroMigration::rewritePrograms())->toBeFalse()
        ->and(WebsiteItem::query()->where('collection', 'programs')->orderBy('sort_order')->get()->pluck('title_bn')->all()[2])->toBe('আমার লেখা');
});

it('converts emoji icons to icon keys and leaves keys and unknown values alone', function () {
    $emoji = WebsiteItem::create(['collection' => 'why', 'title_bn' => 'ক', 'icon' => '🧮', 'sort_order' => 1]);
    $key = WebsiteItem::create(['collection' => 'why', 'title_bn' => 'খ', 'icon' => 'book', 'sort_order' => 2]);
    $unknown = WebsiteItem::create(['collection' => 'about_values', 'title_bn' => 'গ', 'icon' => 'ZZ', 'sort_order' => 3]);
    $other = WebsiteItem::create(['collection' => 'academics_programs', 'title_bn' => 'ঘ', 'icon' => '🎖', 'sort_order' => 4]);

    expect(PosterHeroMigration::convertIcons())->toBe(2)
        ->and($emoji->fresh()->icon)->toBe('abacus')->and($key->fresh()->icon)->toBe('book')
        ->and($unknown->fresh()->icon)->toBe('ZZ')->and($other->fresh()->icon)->toBe('medal');
});
