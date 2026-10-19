<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laramod\Facades\Modules;
use Modules\Accounts\Models\User;
use Modules\Notices\Models\Notice;
use Modules\Notices\Services\NoticeDirectory;

it('shows published notices and hides drafts and scheduled ones from the public', function () {
    $live = Notice::factory()->create(['title_bn' => 'প্রকাশিত']);
    $draft = Notice::factory()->create(['title_bn' => 'খসড়া', 'is_published' => false]);
    $later = Notice::factory()->create(['title_bn' => 'ভবিষ্যৎ', 'publish_at' => now()->addDay()]);
    $past = Notice::factory()->create(['title_bn' => 'অতীত', 'publish_at' => now()->subDay()]);

    $this->get(route('notices.public.index'))->assertOk()->assertSee('প্রকাশিত')->assertSee('অতীত')->assertDontSee('খসড়া')->assertDontSee('ভবিষ্যৎ');

    $this->get(route('notices.public.show', $live))->assertOk()->assertSee('প্রকাশিত');
    $this->get(route('notices.public.show', $draft))->assertNotFound();
    $this->get(route('notices.public.show', $later))->assertNotFound();
    $this->get(route('notices.public.show', $past))->assertOk();
});

it('puts pinned notices first and filters by category', function () {
    Notice::factory()->create(['title_bn' => 'সাধারণ নোটিশ', 'created_at' => now()]);
    Notice::factory()->create(['title_bn' => 'পুরনো গুরুত্বপূর্ণ', 'is_pinned' => true, 'created_at' => now()->subWeek()]);
    Notice::factory()->create(['title_bn' => 'পরীক্ষার নোটিশ', 'category' => 'exam']);

    expect(app(NoticeDirectory::class)->latest()->first()->title_bn)->toBe('পুরনো গুরুত্বপূর্ণ');
    $this->get(route('notices.public.index', ['category' => 'exam']))->assertSee('পরীক্ষার নোটিশ')->assertDontSee('সাধারণ নোটিশ');
});

it('feeds the ticker only with visible notices marked for it', function () {
    Notice::factory()->create(['title_bn' => 'টিকারে', 'show_in_ticker' => true]);
    Notice::factory()->create(['title_bn' => 'টিকারে নয়', 'show_in_ticker' => false]);
    Notice::factory()->create(['title_bn' => 'অপ্রকাশিত টিকার', 'show_in_ticker' => true, 'is_published' => false]);

    $items = Modules::find('notices')->tickerItems();

    expect(collect($items)->pluck('text')->all())->toBe(['টিকারে']);
    $this->get(route('notices.public.index'))->assertSee('টিকারে');
});

it('adds the latest notices to the homepage sections', function () {
    Notice::factory()->count(8)->create();

    $section = Modules::find('notices')->homepageSections()[0];

    expect($section->view)->toBe('notices::home.latest')->and($section->data['notices'])->toHaveCount(6);
});

it('keeps teachers and guests out of the notices admin API', function () {
    $this->getJson(route('notices.api.index'))->assertUnauthorized();
    $this->actingAs(userWithRole('teacher'))->getJson(route('notices.api.index'))->assertForbidden();
});

it('lets admins create, update and delete a notice with an attachment', function () {
    Storage::fake('public');
    $admin = userWithRole('admin');

    $id = $this->actingAs($admin)->post(route('notices.api.store'), [
        'title_bn' => 'ছুটির নোটিশ', 'body_bn' => 'কাল ছুটি।', 'category' => 'holiday', 'is_published' => true, 'show_in_ticker' => true,
        'attachment' => UploadedFile::fake()->create('notice.pdf', 100, 'application/pdf'),
    ], ['Accept' => 'application/json'])->assertCreated()->json('data.id');

    $notice = Notice::find($id);
    Storage::disk('public')->assertExists($notice->attachment);
    expect($notice->attachment_name)->toBe('notice.pdf')->and($notice->show_in_ticker)->toBeTrue();

    $this->actingAs($admin)->put(route('notices.api.update', $notice), ['title_bn' => 'সংশোধিত', 'body_bn' => 'কাল ছুটি।', 'category' => 'holiday', '_method' => 'PUT'], ['Accept' => 'application/json'])->assertOk();
    expect($notice->fresh()->title_bn)->toBe('সংশোধিত')->and($notice->fresh()->attachment)->not->toBeNull();   // untouched without a new file

    $old = $notice->attachment;
    $this->actingAs($admin)->put(route('notices.api.update', $notice), ['title_bn' => 'সংশোধিত', 'body_bn' => 'x', 'category' => 'holiday', 'remove_attachment' => '1'], ['Accept' => 'application/json'])->assertOk();
    Storage::disk('public')->assertMissing($old);
    expect($notice->fresh()->attachment)->toBeNull();

    $this->actingAs($admin)->deleteJson(route('notices.api.destroy', $notice))->assertNoContent();
    expect(Notice::count())->toBe(0);
});

it('validates notices and rejects dangerous attachment types', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->postJson(route('notices.api.store'), ['title_bn' => '', 'category' => 'nope'])->assertJsonValidationErrors(['title_bn', 'body_bn', 'category']);
    $this->actingAs($admin)->post(route('notices.api.store'), ['title_bn' => 'x', 'body_bn' => 'y', 'category' => 'general', 'attachment' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php')], ['Accept' => 'application/json'])->assertJsonValidationErrors('attachment');
});

it('gives read-only users a list but no writes', function () {
    $viewer = User::factory()->create();
    userWithRole('admin');
    $viewer->givePermissionTo(['admin.access', 'notices.view']);
    $viewer = $viewer->fresh();

    $this->actingAs($viewer)->getJson(route('notices.api.index'))->assertOk();
    $this->actingAs($viewer)->postJson(route('notices.api.store'), ['title_bn' => 'x', 'body_bn' => 'y', 'category' => 'general'])->assertForbidden();
});

it('renders the shared public layout with navigation and the language switch', function () {
    $this->get(route('notices.public.index'))->assertOk()->assertSee(route('core.locale', 'en'), false)->assertSee(__('core::nav.notices'));
});
