<?php

namespace Modules\Academic\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Academic\Enums\Division;
use Modules\Academic\Models\AcademicSession;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Models\Subject;

class DemoAcademicSeeder extends Seeder
{
    public function run(): void
    {
        AcademicSession::query()->firstOrCreate(
            ['name' => '২০২৬'],
            ['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_current' => true],
        );

        $classes = [
            ['Play', 'প্লে', Division::Kids], ['Nursery', 'নার্সারি', Division::Kids],
            ['One', 'প্রথম', Division::Kids], ['Two', 'দ্বিতীয়', Division::Kids], ['Three', 'তৃতীয়', Division::Kids],
            ['Four', 'চতুর্থ', Division::Kids], ['Five', 'পঞ্চম', Division::Kids],
            ['Six', 'ষষ্ঠ', Division::Junior], ['Seven', 'সপ্তম', Division::Junior], ['Eight', 'অষ্টম', Division::Junior],
            ['Hifz', 'হিফজ', Division::Junior],
        ];

        foreach ($classes as $index => [$english, $bangla, $division]) {
            $class = SchoolClass::query()->updateOrCreate(
                ['name_en' => $english],
                ['name_bn' => $bangla, 'numeric_order' => $index + 1, 'division' => $division, 'is_active' => true],
            );

            $class->sections()->firstOrCreate(['name_bn' => 'ক'], ['name_en' => 'A']);
        }

        $subjects = [
            'quran' => ['কুরআন', 'Quran'], 'arabic' => ['আরবি', 'Arabic'], 'bangla' => ['বাংলা', 'Bangla'],
            'english' => ['English', 'English'], 'math' => ['গণিত', 'Mathematics'], 'mental_math' => ['মেন্টাল ম্যাথ', 'Mental Math'],
            'islam' => ['ইসলাম শিক্ষা', 'Islamic Studies'], 'gk' => ['সাধারণ জ্ঞান', 'General Knowledge'],
        ];

        $created = [];
        foreach ($subjects as $code => [$bangla, $english]) {
            $created[$code] = Subject::query()->updateOrCreate(['code' => strtoupper($code)], ['name_bn' => $bangla, 'name_en' => $english]);
        }

        $mapping = [
            'Play' => ['quran', 'bangla', 'english', 'math', 'mental_math'],
            'Nursery' => ['quran', 'bangla', 'english', 'math', 'mental_math'],
            'Hifz' => ['quran', 'arabic', 'islam'],
        ];
        $everything = array_keys($subjects);

        foreach (SchoolClass::query()->get() as $class) {
            $codes = $mapping[$class->name_en] ?? $everything;
            $sync = [];

            foreach ($codes as $position => $code) {
                $sync[$created[$code]->id] = ['is_optional' => $code === 'gk', 'sort_order' => $position + 1];
            }

            $class->subjects()->sync($sync);
        }
    }
}
