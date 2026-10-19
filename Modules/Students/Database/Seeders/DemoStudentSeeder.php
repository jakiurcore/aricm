<?php

namespace Modules\Students\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Services\AcademicDirectory;
use Modules\Students\Enums\ResidencyType;
use Modules\Students\Models\Student;
use Modules\Students\Services\StudentRegistrar;

class DemoStudentSeeder extends Seeder
{
    private const array BOYS = ['মোহাম্মদ আব্দুল্লাহ', 'আহমাদ ইবনে হাসান', 'মুহাম্মদ ইব্রাহিম', 'আব্দুর রহমান', 'ইউসুফ আলী', 'ওমর ফারুক', 'বিলাল হোসেন', 'সাইফুল্লাহ', 'ইয়াহইয়া মাহমুদ', 'যুবায়ের আহমেদ', 'তাহমিদ রহমান', 'আরিফ হোসেন', 'নাঈম উদ্দিন', 'মাহিন আহমেদ', 'রাফি ইসলাম'];

    private const array GIRLS = ['আয়েশা সিদ্দিকা', 'ফাতিমা জান্নাত', 'মারিয়াম আক্তার', 'খাদিজা বেগম', 'রুকাইয়া খাতুন', 'সুমাইয়া ইসলাম', 'হাফসা আক্তার', 'জান্নাতুল ফেরদৌস', 'উম্মে সালমা', 'নুসাইবা তাসনিম', 'আফিফা রহমান', 'তাবাসসুম মীম', 'সাদিয়া আফরিন', 'ইশরাত জাহান', 'মেহেরুন নেসা'];

    public function run(StudentRegistrar $registrar, AcademicDirectory $academic): void
    {
        if (Student::query()->exists()) {
            return;
        }

        $session = $academic->currentSession();
        $classes = SchoolClass::query()->with('sections')->whereIn('name_en', ['Play', 'Nursery', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight'])->orderBy('numeric_order')->get();
        $age = 4;

        foreach ($classes as $classIndex => $class) {
            $age = 4 + $classIndex;

            foreach (range(1, 3) as $position) {
                $isBoy = ($classIndex + $position) % 2 === 0;
                $names = $isBoy ? self::BOYS : self::GIRLS;
                $name = $names[($classIndex * 3 + $position) % count($names)];

                $registrar->register([
                    'session_id' => $session->id,
                    'class_id' => $class->id,
                    'section_id' => $class->sections->first()?->id,
                    'roll' => $position,
                    'name_bn' => $name,
                    'name_en' => null,
                    'father_name' => 'মোঃ '.self::BOYS[($classIndex + $position + 3) % count(self::BOYS)],
                    'mother_name' => self::GIRLS[($classIndex + $position + 5) % count(self::GIRLS)],
                    'guardian_name' => 'মোঃ '.self::BOYS[($classIndex + $position + 3) % count(self::BOYS)],
                    'guardian_phone' => '017'.str_pad((string) (10000000 + $classIndex * 100 + $position), 8, '0', STR_PAD_LEFT),
                    'guardian_relation' => 'পিতা',
                    'date_of_birth' => now()->subYears($age + 2)->subDays($position * 17)->toDateString(),
                    'gender' => $isBoy ? 'male' : 'female',
                    'blood_group' => ['A+', 'B+', 'O+', 'AB+'][($classIndex + $position) % 4],
                    'religion' => 'ইসলাম',
                    'present_address' => 'গাইবান্ধা সদর, গাইবান্ধা',
                    'permanent_address' => 'গাইবান্ধা সদর, গাইবান্ধা',
                    'admission_date' => '2026-01-10',
                    'status' => 'active',
                    'residency_type' => $position === 3 ? ResidencyType::Residential->value : ResidencyType::NonResidential->value,
                ]);
            }
        }
    }
}
