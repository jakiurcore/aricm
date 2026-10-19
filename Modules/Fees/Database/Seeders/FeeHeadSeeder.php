<?php

namespace Modules\Fees\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Fees\Enums\FeeHeadType;
use Modules\Fees\Models\FeeHead;

class FeeHeadSeeder extends Seeder
{
    /**
     * The standard heads (written once; edits made in the admin are kept).
     */
    public function run(): void
    {
        if (FeeHead::query()->exists()) {
            return;
        }

        foreach ([
            ['মাসিক বেতন', 'Monthly Tuition', FeeHeadType::Monthly], ['ভর্তি ফি', 'Admission Fee', FeeHeadType::OneTime], ['সেশন ফি', 'Session Fee', FeeHeadType::OneTime],
            ['পরীক্ষার ফি', 'Exam Fee', FeeHeadType::Exam], ['আবাসিক ফি', 'Residential Fee', FeeHeadType::Monthly], ['খাবার ফি', 'Meal Fee', FeeHeadType::Monthly],
            ['মেন্টাল অ্যাবাকাস ফি', 'Mental Abacus Fee', FeeHeadType::Monthly], ['বই-খাতা', 'Books & Stationery', FeeHeadType::OneTime],
        ] as $order => [$bangla, $english, $type]) {
            FeeHead::create(['name_bn' => $bangla, 'name_en' => $english, 'type' => $type, 'is_active' => true, 'sort_order' => $order + 1]);
        }
    }
}
