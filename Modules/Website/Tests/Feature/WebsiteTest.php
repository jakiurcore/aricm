<?php

use Illuminate\Support\Facades\Storage;
use Modules\Academic\Database\Seeders\DemoAcademicSeeder;
use Modules\Admission\Models\Inquiry;
use Modules\Exams\Models\Exam;
use Modules\Gallery\Models\Album;
use Modules\Gallery\Services\GalleryService;
use Modules\Notices\Models\Notice;

it('serves the home page composed from every module in order', function () {
    Notice::factory()->create(['title_bn' => 'হোমের নোটিশ', 'show_in_ticker' => true]);
    $response = $this->get('/')->assertOk();

    $response->assertSee('ভর্তি চলছে!')                          // Admission banner
        ->assertSee('অ্যাবাকাস ও ব্রেইন জিম প্রোগ্রাম')    // Website: program with four benefits
        ->assertSee('কিডস বিভাগ')->assertSee('জুনিয়র বিভাগ')->assertSee('৫–১১')->assertSee('১২–১৫')->assertSee('ইংলিশ ফোনেটিকস প্রোগ্রাম')->assertSee('দ্বীনি ও আধুনিক শিক্ষার সমন্বয়')
        ->assertSee('অধ্যক্ষের বাণী')->assertSee('হোমের নোটিশ')       // Notices latest + ticker
        ->assertSee('শিশুর মেধা বিকাশে আমাদের প্রতিশ্রুতি')            // tagline
        ->assertSee('tel:01714844346', false);

    $html = $response->getContent();
    expect(strpos($html, 'ভর্তি চলছে!'))->toBeLessThan(strpos($html, 'কিডস বিভাগ'))->and(strpos($html, 'কিডস বিভাগ'))->toBeLessThan(strpos($html, 'অধ্যক্ষের বাণী'));
});

it('shows the result search widget only once a result is published', function () {
    $this->get('/')->assertDontSee('name="exam_id"', false);

    $p = placement();
    $setup = gradedExam($p);
    $setup['exam']->forceFill(['result_published' => true])->save();

    $this->get('/')->assertSee('name="exam_id"', false);
});

it('renders the gallery preview on the home page', function () {
    Storage::fake('public');
    $album = Album::factory()->create();
    app(GalleryService::class)->addPhotos($album, [tinyPng('a.png')]);

    $this->get('/')->assertOk()->assertSee(route('gallery.public.show', $album), false);
});

it('serves the inner pages', function (string $route, string $text) {
    $this->get(route($route))->assertOk()->assertSee($text);
})->with([
    ['website.about', 'আমাদের সম্পর্কে'],
    ['website.academics', 'শিক্ষা কার্যক্রম'],
    ['website.contact', 'যোগাযোগ'],
    ['website.routine', 'পরীক্ষার রুটিন'],
]);

it('lists academics classes by division', function () {
    $this->seed(DemoAcademicSeeder::class);

    $this->get(route('website.academics'))->assertSee('প্লে')->assertSee('হিফজ');
});

it('shows exam routines only for exams that are not drafts, with a card list for phones', function () {
    $p = placement();
    $exam = examFor($p);
    $exam->update(['name_bn' => 'গোপন পরীক্ষা', 'status' => 'draft']);

    $this->get(route('website.routine'))->assertOk()->assertDontSee('গোপন পরীক্ষা');

    $exam->update(['status' => 'ongoing', 'name_bn' => 'প্রকাশিত পরীক্ষা']);
    $this->get(route('website.routine', ['exam' => $exam->id, 'class' => $p['class']->id]))->assertOk()->assertSee('প্রকাশিত পরীক্ষা')->assertSee('md:hidden', false);
});

it('hides draft exams even when asked for by id', function () {
    $p = placement();
    $draft = examFor($p);
    $draft->update(['name_bn' => 'ড্রাফট', 'status' => 'draft']);

    $this->get(route('website.routine', ['exam' => $draft->id]))->assertOk()->assertDontSee('ড্রাফট');
});

it('puts SEO and social meta tags on pages', function () {
    $this->get('/')->assertSee('<meta name="description"', false)->assertSee('og:title', false)->assertSee('og:image', false);
});

it('serves a sitemap with public pages and visible content only', function () {
    $visible = Notice::factory()->create();
    $hidden = Notice::factory()->create(['is_published' => false]);

    $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();

    expect($xml)->toContain(route('home'))->toContain(route('notices.public.show', $visible))->not->toContain(route('notices.public.show', $hidden))
        ->and(simplexml_load_string($xml))->not->toBeFalse();
});

it('serves robots.txt that keeps private areas out and points to the sitemap', function () {
    $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Disallow: /api')->assertSee('Sitemap: '.url('/sitemap.xml'));
});

it('switches language and renders the English home', function () {
    $this->from('/')->get(route('core.locale', 'en'))->assertRedirect('/');

    $this->get('/')->assertSee('Abacus &amp; Brain Gym Program', false)->assertSee('Principal’s message');
});

it('takes the visitor from the home page to an admission inquiry', function () {
    $this->get('/')->assertSee(route('admission.public'), false);
    $this->post(route('admission.inquiry.store'), ['student_name' => 'রহিম', 'guardian_name' => 'করিম', 'phone' => '01712345678'])->assertRedirect();

    expect(Inquiry::count())->toBe(1);
});

it('shows exams from a past session as a routine too', function () {
    $p = placement();
    $exam = examFor($p);
    $exam->update(['status' => 'completed']);

    expect(Exam::query()->where('status', 'completed')->count())->toBe(1);
    $this->get(route('website.routine'))->assertOk();
});
