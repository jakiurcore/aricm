<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Accounts\Models\User;
use Modules\Website\Models\WebsiteContent;
use Modules\Website\Models\WebsiteItem;
use Modules\Website\Models\WebsitePriceRow;
use Modules\Website\Models\WebsiteSection;
use Modules\Website\Services\HomepageComposer;

it('keeps teachers and guests out of the website admin API', function () {
    $this->getJson(route('website.api.content.show'))->assertUnauthorized();

    $teacher = userWithRole('teacher');
    $this->actingAs($teacher)->getJson(route('website.api.content.show'))->assertForbidden();
    $this->actingAs($teacher)->getJson(route('website.api.items.index', 'why'))->assertForbidden();
    $this->actingAs($teacher)->getJson(route('website.api.price-rows.index'))->assertForbidden();
    $this->actingAs($teacher)->getJson(route('website.api.sections.show'))->assertForbidden();
});

it('gives read-only users the content but no writes', function () {
    $viewer = User::factory()->create();
    userWithRole('admin');
    $viewer->givePermissionTo(['admin.access', 'website.view']);
    $viewer = $viewer->fresh();

    $this->actingAs($viewer)->getJson(route('website.api.content.show'))->assertOk();
    $this->actingAs($viewer)->putJson(route('website.api.content.update'), ['values' => []])->assertForbidden();
    $this->actingAs($viewer)->postJson(route('website.api.items.store', 'why'), ['title_bn' => 'x'])->assertForbidden();
    $this->actingAs($viewer)->deleteJson(route('website.api.items.destroy', ['why', 1]))->assertForbidden();
    $this->actingAs($viewer)->putJson(route('website.api.sections.update'), ['sections' => []])->assertForbidden();
});

it('returns the schema with stored values and defaults', function () {
    WebsiteContent::create(['key' => 'hero.tag1', 'value_bn' => 'নতুন']);
    $admin = userWithRole('admin');

    $entries = collect($this->actingAs($admin)->getJson(route('website.api.content.show'))->assertOk()->json('schema'))->keyBy('key');

    expect($entries['hero.tag1']['value_bn'])->toBe('নতুন')->and($entries['hero.tag1']['default_bn'])->toBe(__('website::messages.hero.tag1'))
        ->and($entries['hero.tag1']['default_en'])->toBe(__('website::messages.hero.tag1', [], 'en'))->and($entries['hero.tag1']['page'])->toBe('home')
        ->and($entries['hero_image']['type'])->toBe('image')->and($entries['hero_image']['image_url'])->toBe(asset('images/site/hero.jpg'));
});

it('saves text overrides per locale, clears blanks and rejects unknown keys', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->putJson(route('website.api.content.update'), ['values' => ['hero.tag1' => ['bn' => 'নতুন', 'en' => 'New']]])->assertOk();
    expect(site_content()->text('hero.tag1'))->toBe('নতুন');

    $this->actingAs($admin)->putJson(route('website.api.content.update'), ['values' => ['hero.tag1' => ['bn' => '', 'en' => '']]])->assertOk();
    expect(WebsiteContent::where('key', 'hero.tag1')->exists())->toBeFalse()->and(site_content()->text('hero.tag1'))->toBe(__('website::messages.hero.tag1'));

    $this->actingAs($admin)->putJson(route('website.api.content.update'), ['values' => ['made.up' => ['bn' => 'x']]])->assertJsonValidationErrors('values');
    $this->actingAs($admin)->putJson(route('website.api.content.update'), ['values' => ['hero_image' => ['bn' => 'x']]])->assertJsonValidationErrors('values');
});

it('uploads, replaces and resets a content image', function () {
    Storage::fake('public');
    $admin = userWithRole('admin');

    $this->actingAs($admin)->post(route('website.api.content.image.store', 'hero_image'), ['image' => tinyPng('one.png')], ['Accept' => 'application/json'])->assertOk();
    $first = WebsiteContent::where('key', 'hero_image')->value('image');
    Storage::disk('public')->assertExists($first);
    expect(site_content()->image('hero_image'))->toBe(Storage::disk('public')->url($first));

    $this->actingAs($admin)->post(route('website.api.content.image.store', 'hero_image'), ['image' => tinyPng('two.png')], ['Accept' => 'application/json'])->assertOk();
    $second = WebsiteContent::where('key', 'hero_image')->value('image');
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);

    $this->actingAs($admin)->deleteJson(route('website.api.content.image.destroy', 'hero_image'))->assertOk();
    Storage::disk('public')->assertMissing($second);
    expect(site_content()->image('hero_image'))->toBe(asset('images/site/hero.jpg'));
});

it('validates content images and only accepts image keys', function () {
    Storage::fake('public');
    $admin = userWithRole('admin');

    $this->actingAs($admin)->post(route('website.api.content.image.store', 'hero.tag1'), ['image' => tinyPng()], ['Accept' => 'application/json'])->assertNotFound();
    $this->actingAs($admin)->post(route('website.api.content.image.store', 'hero_image'), ['image' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php')], ['Accept' => 'application/json'])->assertJsonValidationErrors('image');
    $this->actingAs($admin)->deleteJson(route('website.api.content.image.destroy', 'hero.tag1'))->assertNotFound();
});

it('creates, updates, reorders and deletes list items', function () {
    Storage::fake('public');
    $admin = userWithRole('admin');
    $before = WebsiteItem::where('collection', 'why')->count();

    $id = $this->actingAs($admin)->postJson(route('website.api.items.store', 'why'), ['title_bn' => 'নতুন', 'title_en' => 'New', 'icon' => '⭐', 'body_bn' => '<b>বিস্তারিত</b><script>x</script>'])->assertCreated()->json('data.id');
    $item = WebsiteItem::find($id);
    expect(WebsiteItem::where('collection', 'why')->count())->toBe($before + 1)->and($item->sort_order)->toBe($before + 1)->and($item->body_bn)->toBe('<b>বিস্তারিত</b>')->and(site_content()->items('why')->last()->title_bn)->toBe('নতুন');

    $this->actingAs($admin)->put(route('website.api.items.update', ['why', $id]), ['title_bn' => 'সংশোধিত', 'is_active' => false, 'image' => tinyPng(), '_method' => 'PUT'], ['Accept' => 'application/json'])->assertOk();
    $item->refresh();
    expect($item->title_bn)->toBe('সংশোধিত')->and($item->is_active)->toBeFalse();
    Storage::disk('public')->assertExists($item->image);
    expect(site_content()->items('why')->pluck('id'))->not->toContain($id);

    $ids = WebsiteItem::where('collection', 'why')->orderBy('sort_order')->pluck('id')->reverse()->values()->all();
    $this->actingAs($admin)->postJson(route('website.api.items.reorder', 'why'), ['ids' => $ids])->assertOk();
    expect(WebsiteItem::where('collection', 'why')->orderBy('sort_order')->pluck('id')->all())->toBe($ids);

    $this->actingAs($admin)->deleteJson(route('website.api.items.destroy', ['why', $id]))->assertNoContent();
    expect(WebsiteItem::find($id))->toBeNull();
    Storage::disk('public')->assertMissing($item->image);
});

it('validates items and keeps them inside a whitelisted collection', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->postJson(route('website.api.items.store', 'why'), ['title_bn' => ''])->assertJsonValidationErrors('title_bn');
    $this->actingAs($admin)->getJson(route('website.api.items.index', 'users'))->assertNotFound();

    $other = WebsiteItem::where('collection', 'programs')->first();
    $this->actingAs($admin)->putJson(route('website.api.items.update', ['why', $other->id]), ['title_bn' => 'x'])->assertNotFound();

    $this->actingAs($admin)->postJson(route('website.api.items.store', 'divisions'), ['title_bn' => 'x'])->assertJsonValidationErrors('meta');
    $this->actingAs($admin)->postJson(route('website.api.items.store', 'divisions'), ['title_bn' => 'x', 'meta' => ['key' => 'kids']])->assertJsonValidationErrors('meta.key');
    $this->actingAs($admin)->postJson(route('website.api.items.store', 'divisions'), ['title_bn' => 'হিফজ', 'meta' => ['key' => 'hifz', 'min' => '8', 'max' => '16']])->assertCreated();

    expect(site_content()->priceColumns()->keys()->all())->toBe(['kids', 'junior', 'hifz'])->and(site_content()->priceColumns()['hifz']->meta['min'])->toBe(8);
});

it('manages price rows and only accepts whole non-negative prices for existing divisions', function () {
    $admin = userWithRole('admin');

    $list = $this->actingAs($admin)->getJson(route('website.api.price-rows.index'))->assertOk();
    expect($list->json('data'))->toHaveCount(4)->and(collect($list->json('columns'))->pluck('meta.key')->all())->toBe(['kids', 'junior']);

    $id = $this->actingAs($admin)->postJson(route('website.api.price-rows.store'), ['label_bn' => 'ইউনিফর্ম', 'label_en' => 'Uniform', 'prices' => ['kids' => 900, 'junior' => '1100']])->assertCreated()->json('data.id');
    expect(WebsitePriceRow::find($id)->prices)->toBe(['kids' => 900, 'junior' => 1100])->and(site_content()->priceRows()->last()->label_en)->toBe('Uniform');

    $this->actingAs($admin)->postJson(route('website.api.price-rows.store'), ['label_bn' => 'x', 'prices' => ['kids' => -1]])->assertJsonValidationErrors('prices.kids');
    $this->actingAs($admin)->postJson(route('website.api.price-rows.store'), ['label_bn' => 'x', 'prices' => ['kids' => 'abc']])->assertJsonValidationErrors('prices.kids');
    $this->actingAs($admin)->postJson(route('website.api.price-rows.store'), ['label_bn' => 'x', 'prices' => ['ghost' => 5]])->assertJsonValidationErrors('prices');
    $this->actingAs($admin)->postJson(route('website.api.price-rows.store'), ['label_bn' => ''])->assertJsonValidationErrors('label_bn');

    $this->actingAs($admin)->putJson(route('website.api.price-rows.update', $id), ['label_bn' => 'ইউনিফর্ম', 'prices' => ['kids' => 950]])->assertOk();
    expect(WebsitePriceRow::find($id)->prices)->toBe(['kids' => 950]);

    $ids = WebsitePriceRow::orderBy('sort_order')->pluck('id')->reverse()->values()->all();
    $this->actingAs($admin)->postJson(route('website.api.price-rows.reorder'), ['ids' => $ids])->assertOk();
    expect(site_content()->priceRows()->pluck('id')->all())->toBe($ids);

    $this->actingAs($admin)->deleteJson(route('website.api.price-rows.destroy', $id))->assertNoContent();
    expect(WebsitePriceRow::find($id))->toBeNull();
});

it('lists the sections with their state and saves toggles and order', function () {
    $admin = userWithRole('admin');

    $sections = collect($this->actingAs($admin)->getJson(route('website.api.sections.show'))->assertOk()->json('data'))->keyBy('key');
    expect($sections['website::sections.hero'])->toMatchArray(['is_enabled' => true, 'sort_order' => null, 'default_order' => 0, 'label' => 'হিরো (প্রথম অংশ)']);

    $this->actingAs($admin)->putJson(route('website.api.sections.update'), ['sections' => [
        ['key' => 'website::sections.why', 'is_enabled' => false, 'sort_order' => null],
        ['key' => 'website::sections.contact', 'is_enabled' => true, 'sort_order' => 1],
    ]])->assertOk();

    expect(WebsiteSection::where('key', 'website::sections.why')->value('is_enabled'))->toBeFalse()
        ->and(app(HomepageComposer::class)->sections()->pluck('view')->all())->not->toContain('website::sections.why');

    $this->actingAs($admin)->putJson(route('website.api.sections.update'), ['sections' => [['key' => 'bogus::view', 'is_enabled' => true]]])->assertJsonValidationErrors('sections.0.key');
});
