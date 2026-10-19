<?php

use Laramod\Contracts\Module;
use Laramod\Facades\Modules;
use Modules\Students\Contracts\ProvidesStudentSummary;
use Modules\Students\Models\Student;
use Modules\Students\Services\StudentSummaryCollector;
use Modules\Students\Support\StudentSummaryItem;

it('collects summary items from modules providing the contract, in sort order', function () {
    $module = new class implements Module, ProvidesStudentSummary
    {
        public function name(): string
        {
            return 'fake-summary';
        }

        public function path(): string
        {
            return __DIR__;
        }

        public function studentSummary(Student $student): array
        {
            return [new StudentSummaryItem('বকেয়া', '৳১০০', 'warn', sortOrder: 2), new StudentSummaryItem('GPA', '৫.০০', sortOrder: 1)];
        }
    };

    Modules::register($module);

    $items = app(StudentSummaryCollector::class)->for(Student::factory()->make());

    expect(collect($items)->pluck('label')->all())->toBe(['GPA', 'বকেয়া']);
});
