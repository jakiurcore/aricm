<?php

use Laramod\Facades\Modules;
use Modules\Admission\Models\Inquiry;
use Modules\Admission\Services\AdmissionSettings;

function inquiry(array $overrides = []): array
{
    return ['student_name' => 'রহিম', 'guardian_name' => 'করিম', 'phone' => '01712345678', 'desired_class' => 'প্লে', 'message' => 'ভর্তি সম্পর্কে জানতে চাই', ...$overrides];
}

it('shows the admission page with the banner and the form', function () {
    $this->get(route('admission.public'))->assertOk()->assertSee('ভর্তি চলছে!')->assertSee('২১ অক্টোবর ২০২৬')->assertSee('১ নভেম্বর ২০২৬')->assertSee('নভেম্বরের ১ তারিখে ফ্রি ক্লাস!')->assertSee('student_name', false);
});

it('stores an inquiry and thanks the visitor', function () {
    $this->post(route('admission.inquiry.store'), inquiry())->assertRedirect(route('admission.public'))->assertSessionHas('inquiry_sent');

    $stored = Inquiry::first();
    expect($stored->student_name)->toBe('রহিম')->and($stored->status)->toBe('new')->and($stored->ip)->not->toBeNull();
});

it('accepts a Bangla digit phone number and normalises it', function () {
    $this->post(route('admission.inquiry.store'), inquiry(['phone' => '০১৭১২-৩৪৫৬৭৮']))->assertSessionHasNoErrors();

    expect(Inquiry::first()->phone)->toBe('01712345678');
});

it('validates the form', function () {
    $this->post(route('admission.inquiry.store'), inquiry(['student_name' => '', 'phone' => '12345', 'desired_class' => 'ডক্টরেট']))->assertSessionHasErrors(['student_name', 'phone', 'desired_class']);

    expect(Inquiry::count())->toBe(0);
});

it('stores nothing when the honeypot is filled', function () {
    $this->post(route('admission.inquiry.store'), inquiry(['website' => 'http://spam.example']))->assertSessionHasErrors('website');

    expect(Inquiry::count())->toBe(0);
});

it('throttles repeated inquiries from one address', function () {
    foreach (range(1, 5) as $ignored) {
        $this->post(route('admission.inquiry.store'), inquiry())->assertRedirect();
    }

    $this->post(route('admission.inquiry.store'), inquiry())->assertStatus(429);
    expect(Inquiry::count())->toBe(5);
});

it('lets staff list, update and delete inquiries and keeps others out', function () {
    $inquiry = Inquiry::factory()->create();
    $admin = userWithRole('admin');

    $this->actingAs(userWithRole('teacher'))->getJson(route('admission.api.inquiries.index'))->assertForbidden();
    $this->actingAs($admin)->getJson(route('admission.api.inquiries.index'))->assertOk()->assertJsonCount(1, 'data');
    $this->actingAs($admin)->putJson(route('admission.api.inquiries.update', $inquiry), ['status' => 'contacted', 'admin_note' => 'ফোন করা হয়েছে'])->assertOk();
    $this->actingAs($admin)->putJson(route('admission.api.inquiries.update', $inquiry), ['status' => 'bogus'])->assertJsonValidationErrors('status');

    expect($inquiry->fresh()->status)->toBe('contacted');
    $this->actingAs($admin)->getJson(route('admission.api.inquiries.index', ['status' => 'new']))->assertJsonCount(0, 'data');
    $this->actingAs($admin)->deleteJson(route('admission.api.inquiries.destroy', $inquiry))->assertNoContent();
});

it('edits the banner and page settings', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->putJson(route('admission.api.settings.update'), [
        'banner_enabled' => false, 'banner_title_bn' => 'নতুন শিরোনাম', 'admission_start' => '2027-01-05', 'classes' => ['প্লে', 'নার্সারি'],
    ])->assertOk();

    $settings = app(AdmissionSettings::class);
    expect($settings->bannerEnabled())->toBeFalse()->and($settings->text('banner_title'))->toBe('নতুন শিরোনাম')->and($settings->classes())->toBe(['প্লে', 'নার্সারি']);

    $this->get(route('admission.public'))->assertDontSee('নতুন শিরোনাম');
    $this->actingAs($admin)->putJson(route('admission.api.settings.update'), ['banner_title_bn' => ''])->assertJsonValidationErrors('banner_title_bn');
});

it('contributes the banner to the homepage', function () {
    expect(Modules::find('admission')->homepageSections()[0]->view)->toBe('admission::home.banner');
});
