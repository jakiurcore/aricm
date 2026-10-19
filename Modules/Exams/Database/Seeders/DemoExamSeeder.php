<?php

namespace Modules\Exams\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Services\AcademicDirectory;
use Modules\Exams\Enums\ExamStatus;
use Modules\Exams\Models\Exam;
use Modules\Exams\Services\ExamConfigurator;

class DemoExamSeeder extends Seeder
{
    public function run(AcademicDirectory $academic, ExamConfigurator $configurator): void
    {
        $session = $academic->currentSession();
        $classes = SchoolClass::query()->whereIn('name_en', ['Play', 'Nursery', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight'])->orderBy('numeric_order')->get();

        $definitions = [
            ['অর্ধবার্ষিক পরীক্ষা ২০২৬', 'Half-yearly Exam 2026', '2026-06-07', ExamStatus::Completed, false],
            ['বার্ষিক পরীক্ষা ২০২৬', 'Annual Exam 2026', '2026-12-06', ExamStatus::Ongoing, true],
        ];

        foreach ($definitions as [$bangla, $english, $start, $status, $released]) {
            $exam = Exam::query()->firstOrCreate(['name_bn' => $bangla], [
                'name_en' => $english, 'session_id' => $session->id, 'start_date' => $start,
                'end_date' => Carbon::parse($start)->addDays(9)->toDateString(), 'status' => $status, 'admit_card_released' => $released,
            ]);

            $configurator->syncClasses($exam, $classes->pluck('id')->all());

            foreach ($classes as $class) {
                $configurator->seedFromClass($exam, $class->id);
                $this->seedWritten($exam, $class->id, $configurator);
                $this->seedRoutine($exam, $class->id, $configurator);
            }
        }
    }

    /**
     * Every subject gets a written part and, for the higher classes, a short oral part, so the marks grid shows
     * components.
     */
    protected function seedWritten(Exam $exam, int $classId, ExamConfigurator $configurator): void
    {
        $rows = $exam->subjects()->where('class_id', $classId)->get()->map(fn ($subject): array => [
            'subject_id' => $subject->subject_id, 'full_marks' => 100, 'pass_marks' => 33,
            'include_in_gpa' => $subject->include_in_gpa, 'is_optional' => $subject->is_optional,
            'components' => [
                ['name_bn' => 'লিখিত', 'name_en' => 'Written', 'full_marks' => 80, 'pass_marks' => 26],
                ['name_bn' => 'মৌখিক', 'name_en' => 'Oral', 'full_marks' => 20, 'pass_marks' => 7],
            ],
        ])->all();

        $configurator->setSubjects($exam, $classId, $rows);
    }

    protected function seedRoutine(Exam $exam, int $classId, ExamConfigurator $configurator): void
    {
        $date = Carbon::parse($exam->start_date);
        $rows = [];

        foreach ($exam->subjects()->where('class_id', $classId)->get() as $subject) {
            while ($date->isFriday()) {
                $date->addDay();
            }

            $rows[] = ['subject_id' => $subject->subject_id, 'exam_date' => $date->toDateString(), 'start_time' => '10:00', 'end_time' => '12:00', 'room' => 'কক্ষ-'.$classId];
            $date->addDay();
        }

        $configurator->setRoutine($exam, $classId, $rows);
    }
}
