<?php

namespace Modules\Notices\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Notices\Models\Notice;

class DemoNoticeSeeder extends Seeder
{
    public function run(): void
    {
        if (Notice::query()->exists()) {
            return;
        }

        foreach ([
            ['ভর্তি চলছে! ২০২৬-২৭ শিক্ষাবর্ষ', 'admission', 'প্লে থেকে অষ্টম শ্রেণি ও হিফজ বিভাগে ভর্তি চলছে। ভর্তি শুরু: ২১ অক্টোবর ২০২৬। ক্লাস শুরু: ১ নভেম্বর ২০২৬। নভেম্বরের ১ তারিখে ফ্রি ক্লাস!', true, true],
            ['বার্ষিক পরীক্ষার রুটিন প্রকাশিত', 'exam', 'বার্ষিক পরীক্ষা ২০২৬ এর রুটিন প্রকাশ করা হয়েছে। পরীক্ষার্থীরা পোর্টাল থেকে প্রবেশপত্র সংগ্রহ করবে।', false, true],
            ['অর্ধবার্ষিক পরীক্ষার ফলাফল প্রকাশ', 'result', 'অর্ধবার্ষিক পরীক্ষা ২০২৬ এর ফলাফল প্রকাশিত হয়েছে। ফলাফল পাতা থেকে বা শিক্ষার্থী পোর্টাল থেকে দেখুন।', false, true],
            ['শুক্রবার সাপ্তাহিক ছুটি', 'holiday', 'প্রতি শুক্রবার মাদরাসা সাপ্তাহিক ছুটি থাকে।', false, false],
        ] as [$title, $category, $body, $pinned, $ticker]) {
            Notice::create(['title_bn' => $title, 'category' => $category, 'body_bn' => $body, 'is_pinned' => $pinned, 'show_in_ticker' => $ticker, 'is_published' => true]);
        }
    }
}
