<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Accounts\Models\User;
use Modules\Gallery\Models\Album;
use Modules\Gallery\Models\Photo;
use Modules\Gallery\Services\GalleryService;

it('uploads several photos at once in order', function () {
    Storage::fake('public');
    $album = Album::factory()->create();

    $this->actingAs(userWithRole('admin'))->post(route('gallery.api.photos.upload', $album), ['photos' => [tinyPng('a.png'), tinyPng('b.png'), tinyPng('c.png')]], ['Accept' => 'application/json'])
        ->assertCreated()->assertJsonCount(3, 'data');

    $photos = $album->photos;
    expect($photos)->toHaveCount(3)->and($photos->pluck('sort_order')->all())->toBe([1, 2, 3]);
    Storage::disk('public')->assertExists($photos[0]->image);
});

it('rejects non-images and too many files', function () {
    $album = Album::factory()->create();
    $admin = userWithRole('admin');

    $this->actingAs($admin)->post(route('gallery.api.photos.upload', $album), ['photos' => [UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]], ['Accept' => 'application/json'])->assertJsonValidationErrors('photos.0');
    $this->actingAs($admin)->post(route('gallery.api.photos.upload', $album), ['photos' => []], ['Accept' => 'application/json'])->assertJsonValidationErrors('photos');
});

it('saves a drag-and-drop order and ignores photos of other albums', function () {
    Storage::fake('public');
    $album = Album::factory()->create();
    $other = Album::factory()->create();
    $service = app(GalleryService::class);
    [$a, $b, $c] = $service->addPhotos($album, [tinyPng('a.png'), tinyPng('b.png'), tinyPng('c.png')])->all();
    $foreign = $service->addPhotos($other, [tinyPng('z.png')])->first();

    $this->actingAs(userWithRole('admin'))->putJson(route('gallery.api.photos.order', $album), ['ids' => [$c->id, $a->id, $foreign->id, $b->id]])->assertOk();

    expect($album->photos()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id])->and($foreign->fresh()->sort_order)->toBe(1);
});

it('sets a cover and clears it when that photo is deleted along with its files', function () {
    Storage::fake('public');
    $album = Album::factory()->create();
    $photo = app(GalleryService::class)->addPhotos($album, [tinyPng('a.png')])->first();
    $admin = userWithRole('admin');

    $this->actingAs($admin)->putJson(route('gallery.api.albums.cover', [$album, $photo]))->assertOk()->assertJsonPath('data.cover_photo_id', $photo->id);

    $path = $photo->image;
    $this->actingAs($admin)->deleteJson(route('gallery.api.photos.destroy', $photo))->assertNoContent();

    Storage::disk('public')->assertMissing($path);
    expect($album->fresh()->cover_photo_id)->toBeNull()->and(Photo::count())->toBe(0);
});

it('refuses a cover photo from another album', function () {
    Storage::fake('public');
    $album = Album::factory()->create();
    $other = Album::factory()->create();
    $foreign = app(GalleryService::class)->addPhotos($other, [tinyPng('z.png')])->first();

    $this->actingAs(userWithRole('admin'))->putJson(route('gallery.api.albums.cover', [$album, $foreign]))->assertNotFound();
});

it('deletes an album with its photos and files', function () {
    Storage::fake('public');
    $album = Album::factory()->create();
    $photo = app(GalleryService::class)->addPhotos($album, [tinyPng('a.png')])->first();

    $this->actingAs(userWithRole('admin'))->deleteJson(route('gallery.api.albums.destroy', $album))->assertNoContent();

    Storage::disk('public')->assertMissing($photo->image);
    expect(Album::count())->toBe(0)->and(Photo::count())->toBe(0);
});

it('shows only published albums that have photos on the public gallery', function () {
    Storage::fake('public');
    $service = app(GalleryService::class);
    $live = Album::factory()->create(['title_bn' => 'দৃশ্যমান অ্যালবাম']);
    $hidden = Album::factory()->create(['title_bn' => 'লুকানো অ্যালবাম', 'is_published' => false]);
    $empty = Album::factory()->create(['title_bn' => 'খালি অ্যালবাম']);
    $service->addPhotos($live, [tinyPng('a.png')]);
    $service->addPhotos($hidden, [tinyPng('b.png')]);

    $this->get(route('gallery.public.index'))->assertOk()->assertSee('দৃশ্যমান অ্যালবাম')->assertDontSee('লুকানো অ্যালবাম')->assertDontSee('খালি অ্যালবাম');
    $this->get(route('gallery.public.show', $live))->assertOk()->assertSee('দৃশ্যমান অ্যালবাম');
    $this->get(route('gallery.public.show', $hidden))->assertNotFound();
});

it('keeps non-admins away and read-only users from writing', function () {
    $album = Album::factory()->create();

    $this->actingAs(userWithRole('teacher'))->getJson(route('gallery.api.albums.index'))->assertForbidden();

    $viewer = User::factory()->create();
    userWithRole('admin');
    $viewer->givePermissionTo(['admin.access', 'gallery.view']);
    $viewer = $viewer->fresh();

    $this->actingAs($viewer)->getJson(route('gallery.api.albums.index'))->assertOk();
    $this->actingAs($viewer)->postJson(route('gallery.api.albums.store'), ['title_bn' => 'x'])->assertForbidden();
    $this->actingAs($viewer)->deleteJson(route('gallery.api.albums.destroy', $album))->assertForbidden();
});

it('previews the latest photos on the homepage', function () {
    Storage::fake('public');
    $album = Album::factory()->create();
    app(GalleryService::class)->addPhotos($album, [tinyPng('a.png'), tinyPng('b.png')]);

    expect(Laramod\Facades\Modules::find('gallery')->homepageSections()[0]->data['photos'])->toHaveCount(2);
});
