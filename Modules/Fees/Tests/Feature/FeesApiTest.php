<?php

use Illuminate\Support\Facades\Route;
use Modules\Academic\Models\AcademicSession;
use Modules\Accounts\Models\User;
use Modules\Fees\Enums\PaymentMethod;
use Modules\Fees\Models\FeeHead;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Models\FeeStructure;
use Modules\Fees\Models\Payment;
use Modules\Fees\Models\StudentConcession;
use Modules\Fees\Services\FeeSettings;
use Modules\Fees\Services\PaymentService;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;

it('gives teachers a 403 on every fee route', function () {
    $teacher = userWithRole('teacher');

    $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route) => str_starts_with($route->getName() ?? '', 'fees.api.'));
    expect($routes->count())->toBeGreaterThan(20);

    foreach ($routes as $route) {
        $uri = preg_replace('/\{[^}]+\}/', '1', $route->uri());

        $this->actingAs($teacher)->json($route->methods()[0], '/'.$uri)->assertForbidden();
    }
});

it('gives guests a 401 on fee routes', function () {
    $this->getJson(route('fees.api.invoices.index'))->assertUnauthorized();
});

it('lets an accountant collect, verify, generate and report but not change the structure or settings', function () {
    $accountant = userWithRole('accountant');
    $p = placement();
    $head = FeeHead::factory()->monthly()->create();
    $invoice = FeeInvoice::factory()->create();

    $this->actingAs($accountant)->getJson(route('fees.api.heads.index'))->assertOk();
    $this->actingAs($accountant)->getJson(route('fees.api.reports.show', 'dues'))->assertOk();
    $this->actingAs($accountant)->postJson(route('fees.api.invoices.generate.monthly'), ['month' => '2026-03'])->assertOk();

    $this->actingAs($accountant)->postJson(route('fees.api.heads.store'), ['name_bn' => 'x', 'type' => 'monthly'])->assertForbidden();
    $this->actingAs($accountant)->putJson(route('fees.api.structures.update'), ['session_id' => $p['session']->id, 'rows' => []])->assertForbidden();
    $this->actingAs($accountant)->putJson(route('fees.api.settings.update'), [])->assertForbidden();
    $this->actingAs($accountant)->postJson(route('fees.api.concessions.store'), [])->assertForbidden();
    $this->actingAs($accountant)->postJson(route('fees.api.invoices.waive', $invoice), ['reason' => 'x'])->assertForbidden();
    $this->actingAs($accountant)->deleteJson(route('fees.api.heads.destroy', $head))->assertForbidden();
});

it('manages fee heads', function () {
    $admin = userWithRole('admin');

    $id = $this->actingAs($admin)->postJson(route('fees.api.heads.store'), ['name_bn' => 'লাইব্রেরি ফি', 'type' => 'monthly', 'is_active' => true, 'sort_order' => 3])->assertCreated()->json('data.id');
    $this->actingAs($admin)->putJson(route('fees.api.heads.update', $id), ['name_bn' => 'লাইব্রেরি', 'type' => 'one_time', 'is_active' => false])->assertOk();

    expect(FeeHead::find($id)->type->value)->toBe('one_time');
    $this->actingAs($admin)->postJson(route('fees.api.heads.store'), ['name_bn' => '', 'type' => 'nope'])->assertJsonValidationErrors(['name_bn', 'type']);
    $this->actingAs($admin)->deleteJson(route('fees.api.heads.destroy', $id))->assertNoContent();
});

it('refuses to delete a head that is in use', function () {
    $p = placement();
    $heads = feeSetup($p);

    $this->actingAs(userWithRole('admin'))->deleteJson(route('fees.api.heads.destroy', $heads['tuition']))->assertUnprocessable();
});

it('saves the structure matrix, removes cleared cells and keeps residency variants apart', function () {
    $p = placement();
    $head = FeeHead::factory()->monthly()->create();
    $admin = userWithRole('admin');
    $put = fn (array $rows) => $this->actingAs($admin)->putJson(route('fees.api.structures.update'), ['session_id' => $p['session']->id, 'rows' => $rows]);

    $put([
        ['class_id' => $p['class']->id, 'fee_head_id' => $head->id, 'amount' => '800'],
        ['class_id' => $p['class']->id, 'fee_head_id' => $head->id, 'residency_type' => 'residential', 'amount' => '2500.50'],
    ])->assertOk()->assertJsonCount(2, 'data');

    expect(FeeStructure::count())->toBe(2)->and(FeeStructure::where('scope', 'residential')->first()->amount->poisha)->toBe(250050);

    $put([['class_id' => $p['class']->id, 'fee_head_id' => $head->id, 'amount' => '900']])->assertOk();
    expect(FeeStructure::where('scope', 'all')->first()->amount->poisha)->toBe(90000)->and(FeeStructure::count())->toBe(2);

    $put([['class_id' => $p['class']->id, 'fee_head_id' => $head->id, 'residency_type' => 'residential', 'amount' => '']])->assertOk();
    expect(FeeStructure::count())->toBe(1);
});

it('does not take exam fee heads in the structure matrix', function () {
    $p = placement();
    $exam = FeeHead::factory()->exam()->create();

    $this->actingAs(userWithRole('admin'))->putJson(route('fees.api.structures.update'), ['session_id' => $p['session']->id, 'rows' => [['class_id' => $p['class']->id, 'fee_head_id' => $exam->id, 'amount' => '100']]])->assertUnprocessable();
});

it('copies a structure to another session without overwriting', function () {
    $p = placement();
    feeSetup($p);
    $next = AcademicSession::factory()->create(['name' => '2027']);
    $admin = userWithRole('admin');

    $this->actingAs($admin)->postJson(route('fees.api.structures.copy'), ['from_session_id' => $p['session']->id, 'to_session_id' => $next->id])->assertOk()->assertJsonPath('data.copied', 2);
    $this->actingAs($admin)->postJson(route('fees.api.structures.copy'), ['from_session_id' => $p['session']->id, 'to_session_id' => $next->id])->assertOk()->assertJsonPath('data.copied', 0);
});

it('stores concessions as percent or poisha and logs who approved them', function () {
    $p = placement();
    $heads = feeSetup($p);
    $student = enrolled($p);
    $admin = userWithRole('admin');

    $this->actingAs($admin)->postJson(route('fees.api.concessions.store'), ['student_id' => $student->id, 'fee_head_id' => $heads['tuition']->id, 'kind' => 'percent', 'value' => 25, 'reason_type' => 'sibling', 'valid_from_month' => '2026-01'])->assertCreated();
    $this->actingAs($admin)->postJson(route('fees.api.concessions.store'), ['student_id' => $student->id, 'kind' => 'fixed', 'value' => '150.50', 'reason_type' => 'merit', 'valid_from_month' => '2026-01', 'valid_to_month' => '2026-06'])->assertCreated();

    $percent = StudentConcession::where('kind', 'percent')->first();
    $fixed = StudentConcession::where('kind', 'fixed')->first();

    expect($percent->value)->toBe(25)->and($fixed->value)->toBe(15050)->and($fixed->fee_head_id)->toBeNull()->and($percent->approved_by)->toBe($admin->id)
        ->and($fixed->valid_to_month->format('Y-m'))->toBe('2026-06');

    $this->actingAs($admin)->postJson(route('fees.api.concessions.store'), ['student_id' => $student->id, 'kind' => 'percent', 'value' => 150, 'reason_type' => 'merit', 'valid_from_month' => '2026-01'])->assertJsonValidationErrors('value');
    $this->actingAs($admin)->postJson(route('fees.api.concessions.store'), ['student_id' => $student->id, 'kind' => 'fixed', 'value' => 10, 'reason_type' => 'merit', 'valid_from_month' => '2026-05', 'valid_to_month' => '2026-02'])->assertJsonValidationErrors('valid_to_month');
});

it('previews monthly generation without writing, then confirms', function () {
    $p = placement();
    feeSetup($p);
    enrolled($p);
    $admin = userWithRole('admin');

    $preview = $this->actingAs($admin)->postJson(route('fees.api.invoices.generate.monthly'), ['month' => '2026-03'])->assertOk();
    expect($preview->json('data.dry_run'))->toBeTrue()->and($preview->json('data.created'))->toBe(1)->and(FeeInvoice::count())->toBe(0);

    $this->actingAs($admin)->postJson(route('fees.api.invoices.generate.monthly'), ['month' => '2026-03', 'confirm' => true])->assertOk()->assertJsonPath('data.dry_run', false);
    expect(FeeInvoice::count())->toBe(1);

    $this->actingAs($admin)->postJson(route('fees.api.invoices.generate.monthly'), ['month' => '2026-03', 'confirm' => true])->assertOk()->assertJsonPath('data.created', 0)->assertJsonPath('data.skipped_existing', 1);
    $this->actingAs($admin)->postJson(route('fees.api.invoices.generate.monthly'), ['month' => 'March'])->assertJsonValidationErrors('month');
});

it('sets exam fee amounts per class and generates exam invoices', function () {
    $p = placement();
    $exam = examFor($p);
    FeeHead::factory()->exam()->create();
    enrolled($p);
    $admin = userWithRole('admin');

    $this->actingAs($admin)->putJson(route('fees.api.exam-fees.update', $exam), ['rows' => [['class_id' => $p['class']->id, 'amount' => '250', 'due_date' => '2026-05-25']]])
        ->assertOk()->assertJsonPath('data.0.amount', 25000);

    $other = placement();
    $this->actingAs($admin)->putJson(route('fees.api.exam-fees.update', $exam), ['rows' => [['class_id' => $other['class']->id, 'amount' => '250']]])->assertUnprocessable();

    $this->actingAs($admin)->postJson(route('fees.api.invoices.generate.exam'), ['exam_id' => $exam->id, 'confirm' => true])->assertOk()->assertJsonPath('data.created', 1);
});

it('creates a one-off invoice and waives an invoice with a logged reason', function () {
    $p = placement();
    $heads = feeSetup($p);
    $student = enrolled($p);
    $admin = userWithRole('admin');

    $id = $this->actingAs($admin)->postJson(route('fees.api.invoices.store'), ['student_id' => $student->id, 'items' => [['fee_head_id' => $heads['tuition']->id, 'amount' => '500']]])->assertCreated()->json('data.id');

    $this->actingAs($admin)->postJson(route('fees.api.invoices.waive', $id), [])->assertJsonValidationErrors('reason');
    $this->actingAs($admin)->postJson(route('fees.api.invoices.waive', $id), ['reason' => 'গরিব'])->assertOk()->assertJsonPath('data.status', 'waived');

    expect(Activity::where('description', 'waived')->count())->toBe(1);
});

it('lists and filters invoices', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    invoicesFor($student, ['2026-01-01', '2026-02-01']);
    $admin = userWithRole('admin');

    $this->actingAs($admin)->getJson(route('fees.api.invoices.index'))->assertOk()->assertJsonCount(2, 'data');
    $this->actingAs($admin)->getJson(route('fees.api.invoices.index', ['month' => '2026-02']))->assertJsonCount(1, 'data');
    $this->actingAs($admin)->getJson(route('fees.api.invoices.index', ['status' => 'paid']))->assertJsonCount(0, 'data');
    $this->actingAs($admin)->getJson(route('fees.api.invoices.index', ['search' => $student->student_id]))->assertJsonCount(2, 'data');
});

it('runs the collection desk: search, dues, pay, receipt', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p, ['name_bn' => 'রাকিব হোসেন']);
    $invoices = invoicesFor($student, ['2026-01-01']);
    $accountant = userWithRole('accountant');

    $this->actingAs($accountant)->getJson(route('fees.api.collection.search', ['q' => 'রাকিব']))->assertOk()->assertJsonPath('data.0.due', 130000);
    $this->actingAs($accountant)->getJson(route('fees.api.collection.search', ['q' => 'র']))->assertUnprocessable();

    $this->actingAs($accountant)->getJson(route('fees.api.collection.student', $student))->assertOk()->assertJsonPath('data.total_due', 130000)->assertJsonCount(1, 'data.invoices');

    $paid = $this->actingAs($accountant)->postJson(route('fees.api.collection.pay'), ['student_id' => $student->id, 'invoice_ids' => [$invoices[0]->id], 'amount' => '1300', 'method' => 'cash'])->assertCreated();
    $paid->assertJsonPath('data.status', 'approved');

    $receipt = $this->actingAs($accountant)->get($paid->json('data.receipt_url'))->assertOk();
    expect($receipt->headers->get('Content-Type'))->toBe('application/pdf')->and($receipt->getContent())->toStartWith('%PDF');

    $thermal = $this->actingAs($accountant)->get(route('fees.api.payments.receipt', [Payment::first(), 'format' => 'thermal']))->assertOk();
    expect($thermal->getContent())->toStartWith('%PDF');
});

it('refuses counter payments with a gateway method or a bad amount', function () {
    $student = enrolled(placement());
    $accountant = userWithRole('accountant');

    $this->actingAs($accountant)->postJson(route('fees.api.collection.pay'), ['student_id' => $student->id, 'amount' => '100', 'method' => 'gateway'])->assertJsonValidationErrors('method');
    $this->actingAs($accountant)->postJson(route('fees.api.collection.pay'), ['student_id' => $student->id, 'amount' => '-5', 'method' => 'cash'])->assertJsonValidationErrors('amount');
});

it('works the verification queue: pending first, newest first, approve, reject', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoices = invoicesFor($student, ['2026-01-01', '2026-02-01']);
    $first = submit($student, [$invoices[0]->id], 'QUEUE00001');
    $second = submit($student, [$invoices[1]->id], 'QUEUE00002');
    $accountant = userWithRole('accountant');

    $this->actingAs($accountant)->getJson(route('fees.api.payments.index', ['status' => 'pending']))->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.invoices.0.invoice_no', $invoices[1]->invoice_no);
    $this->actingAs($accountant)->getJson(route('fees.api.payments.pending-count'))->assertJsonPath('data.pending', 2);

    $this->actingAs($accountant)->postJson(route('fees.api.payments.approve', $first))->assertOk()->assertJsonPath('data.status', 'approved');
    $this->actingAs($accountant)->postJson(route('fees.api.payments.reject', $second), [])->assertJsonValidationErrors('reason');
    $this->actingAs($accountant)->postJson(route('fees.api.payments.reject', $second), ['reason' => 'ট্রানজেকশন মেলেনি'])->assertOk()->assertJsonPath('data.status', 'rejected');
    $this->actingAs($accountant)->postJson(route('fees.api.payments.approve', $second))->assertUnprocessable();

    $this->actingAs($accountant)->get(route('fees.api.payments.receipt', $second))->assertNotFound();
});

it('serves the reports as JSON, PDF and Excel', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoices = invoicesFor($student, ['2026-01-01', '2026-02-01']);
    app(PaymentService::class)->collectAtCounter($student, taka(1300), PaymentMethod::Cash, [$invoices[0]->id], userWithRole('accountant'));
    $admin = userWithRole('admin');

    $this->actingAs($admin)->getJson(route('fees.api.reports.show', ['report' => 'cash-book', 'from' => now()->startOfMonth()->toDateString(), 'to' => now()->toDateString()]))->assertOk()->assertJsonPath('data.totals.amount', 130000)->assertJsonCount(1, 'data.rows');
    $this->actingAs($admin)->getJson(route('fees.api.reports.show', ['report' => 'monthly', 'month' => '2026-01']))->assertOk()->assertJsonPath('data.totals.collected', 130000);
    $this->actingAs($admin)->getJson(route('fees.api.reports.show', ['report' => 'head-wise', 'from' => '2020-01-01', 'to' => '2030-01-01']))->assertOk()->assertJsonPath('data.totals.collected', 130000)->assertJsonPath('data.totals.billed', 260000);
    $this->actingAs($admin)->getJson(route('fees.api.reports.show', ['report' => 'dues', 'up_to' => '2027-01-01']))->assertOk()->assertJsonPath('data.totals.due', 130000);
    $this->actingAs($admin)->getJson(route('fees.api.reports.show', ['report' => 'collectors', 'from' => now()->startOfMonth()->toDateString(), 'to' => now()->toDateString()]))->assertOk()->assertJsonPath('data.rows.0.payments', 1);
    $this->actingAs($admin)->getJson(route('fees.api.reports.show', ['report' => 'ledger', 'student_id' => $student->id]))->assertOk()->assertJsonPath('data.totals.debit', 260000)->assertJsonPath('data.totals.credit', 130000);
    $this->actingAs($admin)->getJson(route('fees.api.reports.show', 'nope'))->assertNotFound();

    $pdf = $this->actingAs($admin)->get(route('fees.api.reports.export', ['report' => 'dues', 'format' => 'pdf', 'up_to' => '2027-01-01']))->assertOk();
    expect($pdf->getContent())->toStartWith('%PDF');

    $xlsx = $this->actingAs($admin)->get(route('fees.api.reports.export', ['report' => 'cash-book', 'format' => 'xlsx']))->assertOk();
    expect($xlsx->headers->get('Content-Type'))->toContain('spreadsheetml')->and(substr($xlsx->getContent(), 0, 2))->toBe('PK');
});

it('keeps reports away from users without fees.reports', function () {
    $user = User::factory()->create()->givePermissionTo(Permission::findOrCreate('admin.access', 'web'));
    userWithRole('admin');

    $this->actingAs($user->fresh())->getJson(route('fees.api.reports.show', 'dues'))->assertForbidden();
});

it('shows and saves the fee settings', function () {
    $admin = userWithRole('admin');
    $payload = ['due_day' => 15, 'late_fine_amount' => '50', 'late_fine_after_days' => 3, 'late_fine_mode' => 'per_month', 'auto_generate_monthly' => true, 'gateway_driver' => 'manual', 'admit_card_requires' => 'all_dues_to_exam_month', 'result_requires_no_dues' => true, 'receipt_footer' => 'ধন্যবাদ', 'manual_methods' => [['method' => 'bkash', 'number' => '01711111111', 'account_type' => 'personal', 'instruction_bn' => 'সেন্ড মানি']]];

    $this->actingAs($admin)->putJson(route('fees.api.settings.update'), $payload)->assertOk();

    $settings = app(FeeSettings::class);
    expect($settings->dueDay())->toBe(15)->and($settings->lateFine()->poisha)->toBe(5000)->and($settings->lateFineMode())->toBe('per_month')
        ->and($settings->autoGenerate())->toBeTrue()->and($settings->admitCardRule())->toBe('all_dues_to_exam_month')->and($settings->resultRequiresNoDues())->toBeTrue()
        ->and(setting('result_requires_fee_paid'))->toBeTrue();

    $this->actingAs($admin)->putJson(route('fees.api.settings.update'), [...$payload, 'due_day' => 40, 'late_fine_mode' => 'weekly'])->assertJsonValidationErrors(['due_day', 'late_fine_mode']);
});

it('reports the dashboard numbers', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoice = invoicesFor($student, [now()->startOfMonth()->toDateString()])[0];
    submit($student, [$invoice->id], 'DASH000001');
    app(PaymentService::class)->collectAtCounter($student, taka(300), PaymentMethod::Cash, [], userWithRole('accountant'));

    $this->actingAs(userWithRole('admin'))->getJson(route('fees.api.dashboard'))->assertOk()
        ->assertJsonPath('data.billed_this_month', 130000)->assertJsonPath('data.collected_today', 30000)->assertJsonPath('data.pending_verifications', 1)->assertJsonPath('data.outstanding', 100000);
});
