<?php

namespace Modules\Students\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Students\Models\Student;

class StudentExporter
{
    /**
     * Write the students of a query as CSV (UTF-8 with BOM so Excel shows Bangla correctly).
     *
     * @param  Builder<Student>  $query
     * @param  resource  $out
     */
    public function write(Builder $query, $out): void
    {
        fwrite($out, StudentCsv::BOM);
        fputcsv($out, ['student_id', ...StudentCsv::COLUMNS]);

        $query->with(['schoolClass', 'section'])->orderBy('class_id')->orderBy('roll')->orderBy('id')->chunk(200, function ($students) use ($out): void {
            foreach ($students as $student) {
                fputcsv($out, [
                    $student->student_id, $student->name_bn, $student->name_en, $student->schoolClass?->name_bn, $student->section?->name_bn, $student->roll,
                    $student->father_name, $student->mother_name, $student->guardian_name, $student->guardian_phone, $student->guardian_relation,
                    $student->date_of_birth?->toDateString(), $student->gender->value, $student->blood_group, $student->religion,
                    $student->present_address, $student->permanent_address, $student->admission_date?->toDateString(), $student->residency_type->value,
                ]);
            }
        });
    }

    /**
     * @param  resource  $out
     */
    public function writeTemplate($out): void
    {
        fwrite($out, StudentCsv::BOM);
        fputcsv($out, StudentCsv::COLUMNS);
        fputcsv($out, ['রহিম উদ্দিন', 'Rahim Uddin', 'প্লে', 'ক', 1, 'আব্দুল করিম', 'রহিমা বেগম', 'আব্দুল করিম', '01711111111', 'পিতা', '2020-05-14', 'male', 'A+', 'ইসলাম', 'গাইবান্ধা', 'গাইবান্ধা', '2026-01-10', 'non_residential']);
    }
}
