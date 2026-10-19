<?php

namespace Modules\Teachers\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Models\Subject;
use Modules\Academic\Services\AcademicDirectory;
use Modules\Accounts\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Teachers\Models\Teacher;
use Modules\Teachers\Services\TeacherAssignments;
use Modules\Teachers\Services\TeacherRegistrar;

class DemoTeacherSeeder extends Seeder
{
    /**
     * Teacher => subject codes. Together they cover every subject the demo classes teach.
     */
    private const array TEACHERS = [
        ['name' => 'মাওলানা আব্দুল করিম', 'designation' => 'প্রধান শিক্ষক', 'subjects' => ['QURAN', 'ARABIC']],
        ['name' => 'ফাতেমা খাতুন', 'designation' => 'সহকারী শিক্ষিকা', 'subjects' => ['BANGLA', 'ISLAM']],
        ['name' => 'রাশেদুল ইসলাম', 'designation' => 'সহকারী শিক্ষক', 'subjects' => ['ENGLISH', 'GK']],
        ['name' => 'নাসরিন আক্তার', 'designation' => 'সহকারী শিক্ষিকা', 'subjects' => ['MATH']],
        ['name' => 'মুহাম্মদ ইউসুফ', 'designation' => 'সহকারী শিক্ষক', 'subjects' => ['MENTAL_MATH']],
    ];

    /**
     * Five teachers (teacher1@example.com … teacher5@example.com, password "password"), each with a subject group in every
     * class, and two class sections each as class teacher.
     */
    public function run(TeacherRegistrar $registrar, TeacherAssignments $assignments, AcademicDirectory $academic): void
    {
        if (Teacher::query()->exists()) {
            return;
        }

        $this->callSilent(RolesAndPermissionsSeeder::class);

        $session = $academic->currentSession();
        $teachers = [];

        foreach (self::TEACHERS as $index => $row) {
            $number = $index + 1;
            $teachers[] = $registrar->register(
                ['name_bn' => $row['name'], 'designation' => $row['designation'], 'phone' => '0181000000'.$number],
                ['email' => "teacher{$number}@example.com", 'password' => 'password'],
            );
        }

        $subjects = Subject::query()->pluck('id', 'code');

        foreach (SchoolClass::query()->with(['sections', 'subjects'])->orderBy('numeric_order')->get() as $classIndex => $class) {
            $taught = $class->subjects->pluck('id')->all();

            foreach ($class->sections as $section) {
                foreach (self::TEACHERS as $index => $row) {
                    foreach ($row['subjects'] as $code) {
                        $subjectId = $subjects[$code] ?? null;

                        if ($subjectId && in_array($subjectId, $taught, true)) {
                            $assignments->assignSubject($teachers[$index], $session->id, $class->id, $section->id, $subjectId);
                        }
                    }
                }

                $assignments->setClassTeacher($teachers[$classIndex % count($teachers)], $session->id, $class->id, $section->id);
            }
        }
    }
}
