<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Modules\Website\Database\Seeders\WebsiteContentSeeder;
use Modules\Website\Models\WebsiteContent;
use Modules\Website\Models\WebsiteItem;
use Modules\Website\Models\WebsitePriceRow;
use Modules\Website\Models\WebsiteSection;
use Modules\Website\Services\ContentSchema;
use Modules\Website\Services\HomepageComposer;
use Modules\Website\Services\SiteContent;

it('falls back to the lang default when nothing is edited', function () {
    expect(site_content()->text('hero.tag1'))->toBe(__('website::messages.hero.tag1'))
        ->and(site_content()->text('admission.sent_body'))->toBe(__('admission::messages.sent_body'))
        ->and(site_content()->text('hero.badge_est', ['year' => '২০২৪']))->toBe('স্থাপিত ২০২৪')
        ->and(site_content()->image('hero_image'))->toBe(asset('images/site/hero.jpg'));
});

it('lets a database value override the default per locale and falls back to Bangla', function () {
    WebsiteContent::create(['key' => 'hero.tag1', 'value_bn' => 'নতুন ট্যাগ', 'value_en' => 'New tag']);
    WebsiteContent::create(['key' => 'hero.tag2', 'value_bn' => 'শুধু বাংলা']);
    WebsiteContent::create(['key' => 'hero.badge_est', 'value_bn' => 'প্রতিষ্ঠিত :year']);

    expect(site_content()->text('hero.tag1'))->toBe('নতুন ট্যাগ')
        ->and(site_content()->text('hero.badge_est', ['year' => '২০২৪']))->toBe('প্রতিষ্ঠিত ২০২৪');

    app()->setLocale('en');

    expect(site_content()->text('hero.tag1'))->toBe('New tag')
        ->and(site_content()->text('hero.tag2'))->toBe('শুধু বাংলা')
        ->and(site_content()->text('hero.apply'))->toBe('Apply for admission');
});

it('returns the default again once an override is removed', function () {
    $row = WebsiteContent::create(['key' => 'hero.tag1', 'value_bn' => 'বদলানো']);
    expect(site_content()->text('hero.tag1'))->toBe('বদলানো');

    $row->delete();

    expect(site_content()->text('hero.tag1'))->toBe(__('website::messages.hero.tag1'));
});

it('caches the content and busts it on every write', function () {
    site_content()->text('hero.tag1');
    expect(Cache::has('website.content'))->toBeTrue();

    WebsiteContent::create(['key' => 'hero.tag1', 'value_bn' => 'এক']);
    expect(Cache::has('website.content'))->toBeFalse()->and(site_content()->text('hero.tag1'))->toBe('এক');

    WebsiteItem::factory()->create(['collection' => 'why', 'title_bn' => 'নতুন পয়েন্ট']);
    expect(site_content()->items('why')->pluck('title_bn'))->toContain('নতুন পয়েন্ট');

    app(SiteContent::class)->flush();
    expect(Cache::has('website.content'))->toBeFalse();
});

it('declares a default for every schema key and a label in both languages', function () {
    foreach (ContentSchema::all() as $entry) {
        expect(ContentSchema::find($entry['key']))->toBe($entry);

        foreach (['bn', 'en'] as $locale) {
            expect(__($entry['label'], [], $locale))->not->toBe($entry['label']);

            if ($entry['type'] !== 'image') {
                expect(__($entry['default_lang'], [], $locale))->not->toBe($entry['default_lang'], $entry['key']);
            }
        }
    }

    expect(ContentSchema::find('nope'))->toBeNull();
});

it('seeds the default lists and prices from the lang files', function () {
    $items = site_content()->items('why');

    expect($items)->toHaveCount(6)->and($items->first()->title_bn)->toBe('হিফজুল কুরআন')->and($items->first()->title_en)->toBe('Hifz-ul-Quran')->and($items->first()->icon)->toBe('📖')
        ->and(site_content()->items('programs'))->toHaveCount(4)
        ->and(site_content()->items('phonetics'))->toHaveCount(4)
        ->and(site_content()->items('about_values'))->toHaveCount(3)
        ->and(site_content()->items('schedule'))->toHaveCount(6)
        ->and(site_content()->items('schedule')->first()->meta)->toBe(['time_bn' => 'ভোর ৫:০০', 'time_en' => '5:00 AM'])
        ->and(site_content()->items('academics_programs'))->toHaveCount(5)
        ->and(site_content()->items('admission_docs'))->toHaveCount(5)
        ->and(site_content()->priceColumns()->keys()->all())->toBe(['kids', 'junior'])
        ->and(site_content()->priceColumns()['kids']->meta)->toMatchArray(['age_bn' => '৫–১১', 'min' => 5, 'max' => 11]);

    $rows = site_content()->priceRows();
    expect($rows)->toHaveCount(4)->and($rows->first()->prices)->toBe(['kids' => 1500, 'junior' => 2000])->and($rows->first()->label_en)->toBe('Admission fee');
});

it('only seeds into empty tables so edits survive a re-run', function () {
    WebsiteItem::query()->where('collection', 'why')->first()->update(['title_bn' => 'সম্পাদিত']);
    WebsitePriceRow::query()->delete();

    (new WebsiteContentSeeder)->run();

    expect(WebsiteItem::query()->where('collection', 'why')->count())->toBe(6)->and(WebsiteItem::query()->where('title_bn', 'সম্পাদিত')->count())->toBe(1)->and(WebsitePriceRow::count())->toBe(4);

    (new WebsiteContentSeeder)->run();
    expect(WebsitePriceRow::count())->toBe(4);
});

it('lists only active items in sort order', function () {
    WebsiteItem::query()->where('collection', 'programs')->delete();
    WebsiteItem::factory()->create(['collection' => 'programs', 'title_bn' => 'দ্বিতীয়', 'sort_order' => 2]);
    WebsiteItem::factory()->create(['collection' => 'programs', 'title_bn' => 'প্রথম', 'sort_order' => 1]);
    WebsiteItem::factory()->create(['collection' => 'programs', 'title_bn' => 'লুকানো', 'sort_order' => 0, 'is_active' => false]);

    expect(site_content()->items('programs')->pluck('title_bn')->all())->toBe(['প্রথম', 'দ্বিতীয়']);
});

it('serves an uploaded image from the public disk', function () {
    Storage::fake('public');
    WebsiteContent::create(['key' => 'hero_image', 'image' => 'website/hero.png']);

    expect(site_content()->image('hero_image'))->toBe(Storage::disk('public')->url('website/hero.png'));
});

it('hides disabled home sections and applies the sort order override', function () {
    $composer = app(HomepageComposer::class);
    $views = fn () => $composer->sections()->pluck('view')->all();

    expect($views())->toContain('website::sections.why')->and($views()[0])->toBe('website::sections.hero');

    WebsiteSection::create(['key' => 'website::sections.why', 'is_enabled' => false]);
    WebsiteSection::create(['key' => 'website::sections.contact', 'is_enabled' => true, 'sort_order' => 1]);

    expect($views())->not->toContain('website::sections.why')->and($views()[1])->toBe('website::sections.contact');
    $this->get('/')->assertOk()->assertDontSee(__('website::messages.why.title'));
});

it('lists every home section in the catalog with a translated label', function () {
    $catalog = collect(app(HomepageComposer::class)->catalog());

    expect($catalog->pluck('key')->all())->toContain('website::sections.hero', 'admission::home.banner', 'notices::home.latest', 'gallery::home.preview', 'website::sections.contact');

    foreach ($catalog as $section) {
        expect(__($section['label']))->not->toBe($section['label'], $section['view']);
    }

    expect(HomepageComposer::slug('admission::home.banner'))->toBe('admission_banner');
});
