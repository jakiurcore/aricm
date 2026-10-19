<?php

namespace Modules\Diary\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Diary\Models\QuickPhrase;

class QuickPhraseSeeder extends Seeder
{
    /**
     * The shared phrases teachers tap instead of typing. Only runs while there are none, so edits in the admin are kept.
     */
    public function run(): void
    {
        if (QuickPhrase::query()->whereNull('teacher_id')->exists()) {
            return;
        }

        $phrases = [
            'lesson' => ['আজ নতুন অধ্যায় শুরু হয়েছে।', 'আজ আগের পড়ার পুনরালোচনা হয়েছে।', 'আজ মুখস্থ ও আবৃত্তি করানো হয়েছে।', 'আজ লিখিত অনুশীলন করানো হয়েছে।', 'আজ ক্লাস টেস্ট নেওয়া হয়েছে।'],
            'homework' => ['পাঠ্যবইয়ের নির্দিষ্ট অংশ সুন্দর হাতের লেখায় লিখে আনবে।', 'আজকের পড়া মুখস্থ করে আসবে।', 'খাতায় অনুশীলনী সমাধান করে আনবে।', 'কালকের ক্লাসে টেস্ট হবে, ভালোভাবে প্রস্তুতি নেবে।', 'কোনো বাড়ির কাজ নেই।'],
            'note' => ['অভিভাবকগণ সন্তানের খাতা নিয়মিত দেখবেন।', 'আগামীকাল নতুন বই ও খাতা আনতে হবে।', 'ছাত্রদের পরিষ্কার-পরিচ্ছন্ন পোশাকে আসতে বলুন।'],
            'remark' => ['আজ খুব ভালো করেছে।', 'আজ বাড়ির কাজ আনেনি।', 'ক্লাসে মনোযোগ কম ছিল।', 'হাতের লেখায় আরও যত্ন নিতে হবে।', 'আজ অনুপস্থিত ছিল।'],
        ];

        foreach ($phrases as $field => $texts) {
            foreach ($texts as $order => $text) {
                QuickPhrase::create(['field' => $field, 'text_bn' => $text, 'sort_order' => $order, 'is_active' => true, 'teacher_id' => null]);
            }
        }
    }
}
