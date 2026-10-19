<?php

namespace Modules\Results\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Exams\Models\Exam;
use Modules\Exams\Services\ExamDirectory;
use Modules\Results\Services\MarksService;
use Modules\Results\Services\ResultProcessor;
use Modules\Results\Services\ResultPublisher;
use Modules\Students\Services\StudentDirectory;

class DemoResultSeeder extends Seeder
{
    public function run(ExamDirectory $exams, StudentDirectory $students, MarksService $marks, ResultProcessor $processor, ResultPublisher $publisher): void
    {
        $exam = Exam::query()->where('name_bn', 'অর্ধবার্ষিক পরীক্ষা ২০২৬')->first();

        if (! $exam || $exam->result_published) {
            return;
        }

        mt_srand(2026);

        foreach ($exam->classes as $class) {
            $columns = $marks->columns($exam, $class->id);
            $entries = [];

            foreach ($students->activeInClass($class->id, null, $exam->session_id) as $index => $student) {
                // One student per class is weak, to give the demo a failing result.
                $weak = $index === 2;

                foreach ($columns as $column) {
                    $ratio = $weak ? mt_rand(15, 40) / 100 : mt_rand(55, 98) / 100;
                    $entries[] = ['student_id' => $student->id, 'exam_subject_id' => $column['exam_subject_id'], 'component_id' => $column['component_id'], 'obtained' => round($column['full'] * $ratio)];
                }
            }

            $marks->save($exam, $class->id, $entries, null);
            $processor->process($exam, $class->id);
        }

        $publisher->publish($exam);
    }
}
