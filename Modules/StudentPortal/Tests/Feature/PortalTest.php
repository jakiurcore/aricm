<?php

use Modules\Core\Services\PublicSite;
use Modules\Diary\Enums\RemarkKind;
use Modules\Diary\Services\DiaryFeed;
use Modules\Diary\Services\DiaryService;
use Modules\Exams\Models\Exam;
use Modules\Fees\Enums\PaymentMethod;
use Modules\Fees\Models\ExamFeeAmount;
use Modules\Fees\Models\FeeHead;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Models\Payment;
use Modules\Fees\Services\FeeSettings;
use Modules\Fees\Services\InvoiceGenerator;
use Modules\Fees\Services\PaymentService;
use Modules\Notices\Models\Notice;
use Modules\Results\Services\ResultProcessor;
use Modules\StudentPortal\Services\PortalMenu;
use Modules\Students\Models\Student;

function portalStudent(array $overrides = [], bool $withFees = true): Student
{
    $p = placement();
    if ($withFees) {
        feeSetup($p);
    }
    $student = enrolled($p, ['roll' => 1, ...$overrides]);
    $student->forceFill(['must_change_password' => false])->save();

    return $student;
}

function asStudent(object $test, Student $student): object
{
    return $test->actingAs($student, 'student');
}

it('sends a guest to the student sign in', function () {
    $this->get(route('portal.dashboard'))->assertRedirect(route('student.login'));
    $this->get(route('portal.fees'))->assertRedirect(route('student.login'));
});

it('keeps an admin account out of the portal', function () {
    $this->actingAs(userWithRole('admin'))->get(route('portal.dashboard'))->assertRedirect(route('student.login'));
});

it('shows a student their dashboard with summary cards and notices', function () {
    $student = portalStudent(['name_bn' => 'রাকিব হোসেন']);
    invoicesFor($student, ['2026-01-01']);
    Notice::factory()->create(['title_bn' => 'পোর্টালের নোটিশ']);

    asStudent($this, $student)->get(route('portal.dashboard'))->assertOk()
        ->assertSee('রাকিব হোসেন')->assertSee('পোর্টালের নোটিশ')
        ->assertSee(__('student-portal::messages.due'))->assertSee('১,৩০০');
});

it('builds the menu from the modules that contribute to it', function () {
    $student = portalStudent();

    $html = asStudent($this, $student)->get(route('portal.dashboard'))->assertOk()->getContent();

    foreach (['portal.routine', 'portal.admit', 'portal.fees', 'portal.results', 'portal.notices', 'portal.profile'] as $route) {
        expect($html)->toContain(route($route));
    }
});

it('shows a due badge on the fees menu entry', function () {
    $student = portalStudent();
    invoicesFor($student, ['2026-01-01', '2026-02-01']);

    asStudent($this, $student)->get(route('portal.dashboard'))->assertOk()->assertSee('bg-red-600', false);
});

it('shows the read-only profile and its own ID card', function () {
    $student = portalStudent(['father_name' => 'আব্দুল করিম']);

    asStudent($this, $student)->get(route('portal.profile'))->assertOk()->assertSee('আব্দুল করিম');

    asStudent($this, $student)->get(route('portal.idcard'))->assertOk()->assertSee($student->student_id)->assertSee(route('portal.idcard.pdf'), false);

    $pdf = asStudent($this, $student)->get(route('portal.idcard.pdf'))->assertOk();
    expect($pdf->headers->get('Content-Type'))->toBe('application/pdf')->and($pdf->getContent())->toStartWith('%PDF');
});

it('lists the exam routine of the student\'s class but not draft exams', function () {
    $p = placement();
    $student = enrolled($p);
    $student->forceFill(['must_change_password' => false])->save();
    $exam = examFor($p);
    $exam->update(['name_bn' => 'দৃশ্যমান পরীক্ষা', 'status' => 'ongoing']);
    $draft = examFor($p);
    $draft->update(['name_bn' => 'লুকানো পরীক্ষা', 'status' => 'draft']);

    asStudent($this, $student)->get(route('portal.routine'))->assertOk()->assertSee('দৃশ্যমান পরীক্ষা')->assertDontSee('লুকানো পরীক্ষা');
});

it('lets a student download an admit card only when released and every rule allows it', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $student->forceFill(['must_change_password' => false])->save();
    $exam = examFor($p, released: false);

    asStudent($this, $student)->get(route('portal.admit'))->assertOk()->assertSee(__('student-portal::messages.no_admit'));
    asStudent($this, $student)->get(route('portal.admit.pdf', $exam))->assertForbidden();

    $exam->update(['admit_card_released' => true]);
    asStudent($this, $student)->get(route('portal.admit'))->assertSee(route('portal.admit.pdf', $exam), false);

    $pdf = asStudent($this, $student)->get(route('portal.admit.pdf', $exam))->assertOk();
    expect($pdf->getContent())->toStartWith('%PDF');
});

it('holds the admit card back until the exam fee is paid and links to the payment page', function () {
    $p = placement();
    $exam = examFor($p);
    FeeHead::factory()->exam()->create();
    ExamFeeAmount::create(['exam_id' => $exam->id, 'class_id' => $p['class']->id, 'amount' => taka(200)]);
    $student = enrolled($p);
    $student->forceFill(['must_change_password' => false])->save();
    app(InvoiceGenerator::class)->generateExam($exam);

    asStudent($this, $student)->get(route('portal.admit'))->assertSee(__('fees::messages.eligibility.exam_fee_unpaid'))->assertSee(route('portal.fees'), false);
    asStudent($this, $student)->get(route('portal.admit.pdf', $exam))->assertForbidden();

    app(PaymentService::class)->collectAtCounter($student, taka(200), PaymentMethod::Cash, [], userWithRole('accountant'));
    asStudent($this, $student)->get(route('portal.admit.pdf', $exam))->assertOk();
});

it('shows the fee overview, month grid, bills and a bill with its PDF', function () {
    $student = portalStudent();
    $invoice = invoicesFor($student, ['2026-01-01'])[0];

    asStudent($this, $student)->get(route('portal.fees'))->assertOk()->assertSee('১,৩০০')->assertSee(route('portal.fees.invoice', $invoice), false);
    asStudent($this, $student)->get(route('portal.fees.invoice', $invoice))->assertOk()->assertSee($invoice->invoice_no)->assertSee('মাসিক বেতন');

    $pdf = asStudent($this, $student)->get(route('portal.fees.invoice.pdf', $invoice))->assertOk();
    expect($pdf->getContent())->toStartWith('%PDF');
});

it('cannot open, download or pay another student\'s invoice or receipt (tampered ids)', function () {
    $p = placement();
    feeSetup($p);
    $a = enrolled($p, ['roll' => 1]);
    $b = enrolled($p, ['roll' => 2]);
    $a->forceFill(['must_change_password' => false])->save();
    $bInvoice = invoicesFor($b, ['2026-01-01'])[0];
    $bPayment = app(PaymentService::class)->collectAtCounter($b, taka(500), PaymentMethod::Cash, [$bInvoice->id], userWithRole('accountant'));
    app(FeeSettings::class)->update(['manual_methods' => [['method' => 'bkash', 'number' => '01711111111', 'account_type' => 'personal']]]);

    asStudent($this, $a)->get(route('portal.fees.invoice', $bInvoice))->assertNotFound();
    asStudent($this, $a)->get(route('portal.fees.invoice.pdf', $bInvoice))->assertNotFound();
    asStudent($this, $a)->get(route('portal.fees.receipt', $bPayment))->assertNotFound();
    asStudent($this, $a)->post(route('portal.fees.pay.store'), ['invoice_ids' => [$bInvoice->id], 'method' => 'bkash', 'transaction_id' => 'TAMPER0001', 'sender_number' => '01711111111'])->assertSessionHasErrors('invoice_ids');

    expect(Payment::where('student_id', $a->id)->count())->toBe(0);
});

it('submits a manual payment as pending through the three step page', function () {
    $student = portalStudent();
    $invoice = invoicesFor($student, ['2026-01-01'])[0];
    app(FeeSettings::class)->update(['manual_methods' => [['method' => 'bkash', 'number' => '01711111111', 'account_type' => 'personal', 'instruction_bn' => 'সেন্ড মানি করুন']]]);

    asStudent($this, $student)->get(route('portal.fees.pay'))->assertOk()->assertSee(bn_digits('01711111111', true))->assertSee('payFlow(', false);

    asStudent($this, $student)->post(route('portal.fees.pay.store'), [
        'invoice_ids' => [$invoice->id], 'method' => 'bkash', 'transaction_id' => 'ABC12345XY', 'sender_number' => '01711111111', 'amount' => '1', 'screenshot' => tinyPng('proof.png'),
    ])->assertRedirect(route('portal.fees.payments'));

    $payment = Payment::first();
    expect($payment->status->value)->toBe('pending')->and($payment->amount->poisha)->toBe(130000)->and($invoice->fresh()->paid->poisha)->toBe(0);

    asStudent($this, $student)->get(route('portal.fees.payments'))->assertOk()->assertSee('ABC12345XY');
});

it('refuses a payment method that is not offered', function () {
    $student = portalStudent();
    $invoice = invoicesFor($student, ['2026-01-01'])[0];

    asStudent($this, $student)->post(route('portal.fees.pay.store'), ['invoice_ids' => [$invoice->id], 'method' => 'bkash', 'transaction_id' => 'ABC12345XY', 'sender_number' => '01711111111'])->assertSessionHasErrors('method');
    asStudent($this, $student)->post(route('portal.fees.pay.store'), ['invoice_ids' => [$invoice->id], 'method' => 'gateway'])->assertSessionHasErrors('method');
});

it('rate limits manual payment submissions', function () {
    $student = portalStudent();
    $invoice = invoicesFor($student, ['2026-01-01'])[0];
    app(FeeSettings::class)->update(['manual_methods' => [['method' => 'bkash', 'number' => '01711111111', 'account_type' => 'personal']]]);

    foreach (range(1, 6) as $i) {
        asStudent($this, $student)->post(route('portal.fees.pay.store'), ['invoice_ids' => [$invoice->id], 'method' => 'bkash', 'transaction_id' => 'TXN0000'.str_pad((string) $i, 3, '0', STR_PAD_LEFT), 'sender_number' => '01711111111']);
    }

    asStudent($this, $student)->post(route('portal.fees.pay.store'), ['invoice_ids' => [$invoice->id], 'method' => 'bkash', 'transaction_id' => 'TXN0000999', 'sender_number' => '01711111111'])->assertStatus(429);
});

it('creates advance month invoices when paying ahead, and nothing outside the session', function () {
    $student = portalStudent();
    $session = $student->session;
    $session->update(['start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()->addYear()]);
    app(FeeSettings::class)->update(['manual_methods' => [['method' => 'bkash', 'number' => '01711111111', 'account_type' => 'personal']]]);
    $month = now()->startOfMonth()->addMonth()->format('Y-m');

    asStudent($this, $student)->post(route('portal.fees.pay.store'), ['advance_months' => [$month], 'method' => 'bkash', 'transaction_id' => 'ADV0000001', 'sender_number' => '01711111111'])->assertRedirect(route('portal.fees.payments'));

    $payment = Payment::first();
    expect($payment->amount->poisha)->toBe(130000)->and(FeeInvoice::whereDate('billing_month', $month.'-01')->exists())->toBeTrue();

    asStudent($this, $student)->post(route('portal.fees.pay.store'), ['advance_months' => [now()->startOfMonth()->subMonth()->format('Y-m')], 'method' => 'bkash', 'transaction_id' => 'ADV0000002', 'sender_number' => '01711111111'])->assertStatus(422);
});

it('serves receipts for the student\'s own approved payments', function () {
    $student = portalStudent();
    $invoice = invoicesFor($student, ['2026-01-01'])[0];
    $payment = app(PaymentService::class)->collectAtCounter($student, taka(1300), PaymentMethod::Cash, [$invoice->id], userWithRole('accountant'));

    $pdf = asStudent($this, $student)->get(route('portal.fees.receipt', $payment))->assertOk();
    expect($pdf->getContent())->toStartWith('%PDF');

    asStudent($this, $student)->get(route('portal.fees.ledger'))->assertOk()->assertSee($invoice->invoice_no);
});

it('tells a student their payment was approved through a notification', function () {
    $student = portalStudent();
    $invoice = invoicesFor($student, ['2026-01-01'])[0];
    app(PaymentService::class)->approve(submit($student, [$invoice->id]), userWithRole('accountant'));

    asStudent($this, $student)->get(route('portal.dashboard'))->assertSee(__('fees::messages.notify.approved_title'));
    asStudent($this, $student)->post(route('portal.notifications.read'))->assertRedirect();
    expect($student->unreadNotifications()->count())->toBe(0);
});

it('shows only visible notices in the portal', function () {
    $student = portalStudent();
    Notice::factory()->create(['title_bn' => 'দৃশ্যমান']);
    $hidden = Notice::factory()->create(['title_bn' => 'লুকানো', 'is_published' => false]);

    asStudent($this, $student)->get(route('portal.notices'))->assertOk()->assertSee('দৃশ্যমান')->assertDontSee('লুকানো');
    asStudent($this, $student)->get(route('portal.notices.show', $hidden))->assertNotFound();
});

function publishedFor(Student $student, array $p): Exam
{
    $setup = gradedExam($p);
    enterMarks($setup['exam'], $p['class']->id, $student, [$setup['first']->id => 90, $setup['second']->id => 80]);
    app(ResultProcessor::class)->process($setup['exam'], $p['class']->id);

    return $setup['exam'];
}

it('hides results until published, then shows the marksheet and PDF', function () {
    $p = placement();
    $student = enrolled($p);
    $student->forceFill(['must_change_password' => false])->save();
    $exam = publishedFor($student, $p);

    asStudent($this, $student)->get(route('portal.results'))->assertOk()->assertDontSee($exam->name_bn);
    asStudent($this, $student)->get(route('portal.results.show', $exam))->assertNotFound();
    asStudent($this, $student)->get(route('portal.results.pdf', $exam))->assertNotFound();

    $exam->forceFill(['result_published' => true])->save();

    asStudent($this, $student)->get(route('portal.results'))->assertSee($exam->name_bn)->assertSee('GPA');
    asStudent($this, $student)->get(route('portal.results.show', $exam))->assertOk()->assertSee('GPA')->assertSee(route('portal.results.pdf', $exam), false);
    expect(asStudent($this, $student)->get(route('portal.results.pdf', $exam))->getContent())->toStartWith('%PDF');
});

it('never shows another student\'s result', function () {
    $p = placement();
    $a = enrolled(placement());             // another class: no result in this exam
    $b = enrolled($p, ['roll' => 2]);
    $a->forceFill(['must_change_password' => false])->save();
    $exam = publishedFor($b, $p);
    $exam->forceFill(['result_published' => true])->save();

    asStudent($this, $a)->get(route('portal.results.show', $exam))->assertNotFound();   // a has no result for this exam
    asStudent($this, $a)->get(route('portal.results'))->assertDontSee($exam->name_bn);
});

it('blocks a published result for overdue fees with a pay link when the setting is on', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $student->forceFill(['must_change_password' => false])->save();
    $exam = publishedFor($student, $p);
    $exam->forceFill(['result_published' => true])->save();
    invoicesFor($student, ['2026-01-01']);
    app(FeeSettings::class)->update(['result_requires_no_dues' => true]);

    asStudent($this, $student)->get(route('portal.results'))->assertSee(__('fees::messages.eligibility.result_dues'))->assertDontSee('GPA');
    asStudent($this, $student)->get(route('portal.results.show', $exam))->assertOk()->assertSee(__('fees::messages.eligibility.result_dues'))->assertSee(route('portal.fees'), false);
    asStudent($this, $student)->get(route('portal.results.pdf', $exam))->assertForbidden();

    app(PaymentService::class)->collectAtCounter($student, taka(1300), PaymentMethod::Cash, [], userWithRole('accountant'));
    asStudent($this, $student)->get(route('portal.results.pdf', $exam))->assertOk();
});

it('forces the first-login password change before the portal opens', function () {
    $student = portalStudent();
    $student->forceFill(['must_change_password' => true])->save();

    asStudent($this, $student)->get(route('portal.dashboard'))->assertRedirect(route('student.password.edit'));
    asStudent($this, $student)->get(route('portal.fees'))->assertRedirect(route('student.password.edit'));
});

it('signs the student out from the portal', function () {
    $student = portalStudent();

    asStudent($this, $student)->post(route('student.logout'))->assertRedirect(route('student.login'));
    $this->assertGuest('student');
});

it('renders the portal in English too', function () {
    $student = portalStudent();

    $this->from('/')->get(route('core.locale', 'en'));
    asStudent($this, $student)->get(route('portal.dashboard'))->assertSee('Assalamu Alaikum')->assertSee('Dashboard');
});

/**
 * A student with a published diary entry for today (Thursday 22 Oct 2026) from a teacher of their section.
 *
 * @return array<string, mixed>
 */
function portalDiary(array $payload = []): array
{
    $s = diarySetup();
    $s['student']->forceFill(['must_change_password' => false])->save();
    $entry = app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s, $payload));
    $s['entry'] = app(DiaryService::class)->publish($s['teacher']->user, $entry);

    return $s;
}

it('shows a student today’s diary and marks it as seen', function () {
    $s = portalDiary(['lesson' => 'আজ বিশেষ পাঠ', 'homework' => 'অনুশীলনী ২ করবে']);

    asStudent($this, $s['student'])->get(route('portal.diary'))->assertOk()
        ->assertSee('আজ বিশেষ পাঠ')->assertSee('অনুশীলনী ২ করবে')->assertSee($s['subject']->name_bn);

    // Opening the day is what marks it as seen.
    expect(app(DiaryFeed::class)->unseenCount($s['student']))->toBe(0);
});

it('does not show drafts, other sections or the future', function () {
    $s = diarySetup();
    $s['student']->forceFill(['must_change_password' => false])->save();
    app(DiaryService::class)->save($s['teacher']->user, diaryPayload($s, ['lesson' => 'শুধু খসড়া']));

    asStudent($this, $s['student'])->get(route('portal.diary'))->assertOk()->assertDontSee('শুধু খসড়া')->assertSee(__('diary::messages.portal.no_entries'));
    asStudent($this, $s['student'])->get(route('portal.diary', ['date' => '2026-12-31']))->assertOk()->assertDontSee('শুধু খসড়া');
});

it('hides a classmate’s remark but shows the student’s own', function () {
    $s = portalDiary();
    $classmate = enrolled($s['p'], ['name_bn' => 'করিম']);
    $classmate->forceFill(['must_change_password' => false])->save();
    app(DiaryService::class)->addRemark($s['teacher']->user, $s['entry'], $s['student'], RemarkKind::Praise, 'রহিমের প্রশংসা');
    app(DiaryService::class)->addRemark($s['teacher']->user, $s['entry'], $classmate, RemarkKind::Concern, 'করিমের মন্তব্য');

    asStudent($this, $s['student'])->get(route('portal.diary'))->assertSee('রহিমের প্রশংসা')->assertDontSee('করিমের মন্তব্য');
});

it('records an acknowledgement and shows it as done', function () {
    $s = portalDiary();

    asStudent($this, $s['student'])->post(route('portal.diary.acknowledge', $s['entry']))->assertRedirect();
    asStudent($this, $s['student'])->get(route('portal.diary'))->assertSee(__('diary::messages.portal.acknowledged'));

    expect($s['entry']->acknowledgements()->whereNotNull('acknowledged_at')->count())->toBe(1);
});

it('refuses to acknowledge another section’s entry', function () {
    $s = portalDiary();
    $outsider = enrolled(['session' => $s['p']['session'], 'class' => $s['p']['class'], 'section' => $s['other']]);
    $outsider->forceFill(['must_change_password' => false])->save();

    asStudent($this, $outsider)->post(route('portal.diary.acknowledge', $s['entry']))->assertNotFound();
});

it('acknowledges the whole day at once', function () {
    $s = portalDiary();

    asStudent($this, $s['student'])->post(route('portal.diary.acknowledge-day'), ['date' => '2026-10-22'])->assertRedirect();

    expect($s['entry']->acknowledgements()->whereNotNull('acknowledged_at')->count())->toBe(1);
});

it('puts a diary item with an unseen badge in the portal menu', function () {
    $s = portalDiary();
    $menu = app(PortalMenu::class)->items($s['student']);
    $item = collect($menu)->firstWhere('route', 'portal.diary');

    expect($item)->not->toBeNull()->and($item->badgeFor($s['student']))->toBe(1);

    asStudent($this, $s['student'])->get(route('portal.dashboard'))->assertOk()->assertSee(route('portal.diary'));
});

it('says the madrasa is closed on a holiday', function () {
    $s = portalDiary();

    asStudent($this, $s['student'])->get(route('portal.diary', ['date' => '2026-10-16']))->assertOk()->assertSee(__('diary::messages.portal.holiday'));
});

it('shows the program line under the school name in the portal layout', function () {
    $program = app(PublicSite::class)->identity()['program'];

    asStudent($this, portalStudent())->get(route('portal.dashboard'))->assertOk()->assertSee($program)->assertSee('data-icon="', false);
});
