<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Modules\Core\Services\Settings;
use Modules\Website\Models\WebsiteContent;
use Modules\Website\Models\WebsiteItem;
use Modules\Website\Models\WebsitePriceRow;

function publicGet(string $path, string $locale = 'bn'): TestResponse
{
    return test()->withCookie('locale', $locale)->get($path);
}

it('shows an edited text on the home page in both locales', function () {
    WebsiteContent::create(['key' => 'hero.tag1', 'value_bn' => 'নতুন বাংলা ট্যাগ', 'value_en' => 'Fresh English tag']);

    publicGet('/')->assertOk()->assertSee('নতুন বাংলা ট্যাগ');
    publicGet('/', 'en')->assertOk()->assertSee('Fresh English tag')->assertDontSee('নতুন বাংলা ট্যাগ');
});

it('shows edited list items and drops a hidden one', function () {
    $item = WebsiteItem::query()->where('collection', 'why')->orderBy('sort_order')->first();
    $item->update(['title_bn' => 'সম্পাদিত কারণ', 'title_en' => 'Edited reason', 'icon' => '★']);

    publicGet('/')->assertSee('সম্পাদিত কারণ')->assertSee('★');
    publicGet('/', 'en')->assertSee('Edited reason');

    $item->update(['is_active' => false]);

    publicGet('/')->assertDontSee('সম্পাদিত কারণ');
});

it('renders nothing for an empty collection instead of failing', function () {
    WebsiteItem::query()->whereIn('collection', ['programs', 'phonetics', 'divisions', 'why', 'about_values', 'schedule', 'academics_programs', 'admission_docs'])->delete();
    WebsitePriceRow::query()->delete();

    publicGet('/')->assertOk()->assertDontSee('id="abacus"', false);
    foreach (['/about', '/academics', '/admission', '/contact'] as $path) {
        publicGet($path)->assertOk();
    }
});

it('shows edited academics divisions, schedule and about values', function () {
    WebsiteItem::query()->where('collection', 'schedule')->orderBy('sort_order')->first()->update(['title_bn' => 'বিশেষ কার্যক্রম', 'meta' => ['time_bn' => '৯:৩০']]);
    WebsiteItem::query()->where('collection', 'about_values')->orderBy('sort_order')->first()->update(['title_bn' => 'নতুন মূল্যবোধ']);
    WebsiteItem::query()->where('collection', 'divisions')->orderBy('sort_order')->first()->update(['title_bn' => 'নতুন বিভাগ']);

    publicGet('/academics')->assertSee('বিশেষ কার্যক্রম')->assertSee('৯:৩০');
    publicGet('/')->assertSee('নতুন বিভাগ');
    publicGet('/about')->assertSee('নতুন মূল্যবোধ');
});

it('renders the admission fee table and documents from the content layer', function () {
    WebsitePriceRow::query()->orderBy('sort_order')->first()->update(['label_bn' => 'নতুন ফি', 'label_en' => 'New fee', 'prices' => ['kids' => 1234, 'junior' => 5678]]);
    WebsiteItem::query()->where('collection', 'admission_docs')->orderBy('sort_order')->first()->update(['title_bn' => 'নতুন কাগজ', 'title_en' => 'New document']);
    WebsiteContent::create(['key' => 'admission.fees_title', 'value_bn' => 'আমাদের ফি', 'value_en' => 'Our fees']);

    publicGet('/admission')->assertOk()->assertSee('নতুন ফি')->assertSee('৳ ১২৩৪', false)->assertSee('নতুন কাগজ')->assertSee('আমাদের ফি');
    publicGet('/admission', 'en')->assertSee('New fee')->assertSee('New document')->assertSee('Our fees');
});

it('uses an uploaded image for the hero, campus and principal photo', function () {
    Storage::fake('public');
    foreach (['hero_image' => '/', 'campus_image' => '/about', 'principal_photo' => '/'] as $key => $path) {
        WebsiteContent::create(['key' => $key, 'image' => "website/{$key}.png"]);
        publicGet($path)->assertSee("website/{$key}.png", false);
    }
});

it('shows the same editable contact texts on the page and the home section', function () {
    WebsiteContent::create(['key' => 'contact.title', 'value_bn' => 'আমাদের ঠিকানা']);
    app(Settings::class)->set('office_hours_bn', 'রোজ ৯টা–৫টা');

    publicGet('/contact')->assertSee('আমাদের ঠিকানা')->assertSee('রোজ ৯টা–৫টা')->assertSee('<title>আমাদের ঠিকানা', false);
    publicGet('/')->assertSee('আমাদের ঠিকানা')->assertSee('রোজ ৯টা–৫টা');
});

it('hides the invented principal name but shows a real one', function () {
    publicGet('/')->assertDontSee('অধ্যক্ষের নাম');

    app(Settings::class)->set('principal_name_bn', 'মাওলানা করিম');
    app(Settings::class)->set('principal_title_bn', 'প্রধান শিক্ষক');

    publicGet('/')->assertSee('মাওলানা করিম')->assertSee('প্রধান শিক্ষক');
});

it('builds the header and footer from nav_items and hides social links without a URL', function () {
    publicGet('/')->assertDontSee('href="#"', false)->assertDontSee('wa.me', false)->assertDontSee('aria-label="Instagram"', false);

    app(Settings::class)->set('nav_items', [
        ['label_bn' => 'শুধু হেডার', 'url' => 'https://example.com/h', 'visible' => true, 'sort_order' => 1, 'location' => 'header'],
        ['label_bn' => 'শুধু ফুটার', 'url' => 'https://example.com/f', 'visible' => true, 'sort_order' => 1, 'location' => 'footer'],
    ]);
    app(Settings::class)->setMany(['social_whatsapp' => '8801712345678', 'social_instagram' => 'https://instagram.com/x', 'social_facebook' => 'https://facebook.com/x', 'email' => 'hi@example.com', 'office_hours_bn' => 'সকাল ৮টা']);

    $html = publicGet('/')->assertOk()->assertSee('https://wa.me/8801712345678', false)->assertSee('aria-label="Instagram"', false)->assertSee('mailto:hi@example.com', false)->getContent();

    expect($html)->toContain('https://example.com/h')->and($html)->toContain('https://example.com/f')
        ->and(substr_count($html, 'https://example.com/h'))->toBeGreaterThan(0)
        ->and($html)->not->toContain('href="#"');
});
