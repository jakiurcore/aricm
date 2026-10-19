<?php

use Modules\Core\Services\PdfService;
use Modules\Results\Models\ExamResult;
use Modules\Results\Services\ResultPdf;
use Modules\Results\Services\ResultProcessor;

function pdfScenario(): array
{
    $p = placement();
    $setup = gradedExam($p);
    foreach ([1, 2] as $roll) {
        enterMarks($setup['exam'], $p['class']->id, enrolled($p, ['roll' => $roll]), [$setup['first']->id => 70 + $roll, $setup['second']->id => 80]);
    }
    app(ResultProcessor::class)->process($setup['exam'], $p['class']->id);

    return [$p, $setup['exam']];
}

it('serves marksheets, the tabulation sheet and the merit list as PDFs', function () {
    [$p, $exam] = pdfScenario();
    $admin = userWithRole('admin');

    foreach (['results.api.pdf.marksheets', 'results.api.pdf.tabulation', 'results.api.pdf.merit'] as $route) {
        $response = $this->actingAs($admin)->get(route($route, [$exam, $p['class']]))->assertOk();

        expect($response->headers->get('Content-Type'))->toBe('application/pdf')->and($response->getContent())->toStartWith('%PDF');
    }
});

it('prints one marksheet page per student', function () {
    [$p, $exam] = pdfScenario();

    $pdf = app(ResultPdf::class)->marksheets(ExamResult::query()->orderBy('roll')->get());

    expect(preg_match_all('/\/Type\s*\/Page[^s]/', $pdf))->toBe(2);
});

it('limits a marksheet to one student when asked', function () {
    [$p, $exam] = pdfScenario();
    $one = ExamResult::query()->orderBy('roll')->first();

    $this->actingAs(userWithRole('admin'))->get(route('results.api.pdf.marksheets', [$exam, $p['class'], 'student_id' => $one->student_id]))->assertOk();
});

it('needs the view permission and a processed class', function () {
    $p = placement();
    $setup = gradedExam($p);

    $this->actingAs(userWithRole('accountant'))->get(route('results.api.pdf.merit', [$setup['exam'], $p['class']]))->assertForbidden();
    $this->actingAs(userWithRole('admin'))->get(route('results.api.pdf.marksheets', [$setup['exam'], $p['class']]))->assertNotFound();
});

it('renders Bangla text through the PDF fonts without errors', function () {
    $pdf = app(PdfService::class)->render('<p style="font-family: notosansbengali">আর রহমান ইন্টারন্যাশনাল ক্যাডেট মাদরাসা ক্ষত্রিয় শ্রদ্ধা ১২৩</p>');

    expect($pdf)->toStartWith('%PDF');
});
