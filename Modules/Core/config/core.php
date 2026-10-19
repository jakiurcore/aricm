<?php

return [
    /*
    | Defaults for the key/value settings table. The settings seeder writes the institute data;
    | anything missing from the table falls back to the value here.
    */
    'settings' => [
        'institute_name_bn' => 'আর রহমান ইন্টারন্যাশনাল ক্যাডেট মাদরাসা',
        'institute_name_en' => 'AR Rahman International Cadet Madrasa',
        'institute_name_ar' => 'مدرسة الرحمن الدولية للكاديت',
        'tagline_bn' => 'শিশুর মেধা বিকাশে আমাদের প্রতিশ্রুতি…',
        'tagline_en' => 'Our promise to nurture every child’s talent…',
        'established_year' => 2024,
        'logo' => null,
        'address_bn' => 'লেংগা বাজার, কলেজ পাড়া, গাইবান্ধা সদর, গাইবান্ধা',
        'address_en' => 'Lenga Bazar, College Para, Gaibandha Sadar, Gaibandha',
        'phones' => ['01714844346', '01761781338'],
        'email' => null,
        'principal_name_bn' => null,
        'principal_name_en' => null,
        'principal_message_bn' => null,
        'principal_message_en' => null,
        'principal_signature' => null,
        'social_facebook' => null,
        'social_youtube' => null,
        'admit_card_rules_bn' => null,
        'admit_card_rules_en' => null,
        'result_requires_fee_paid' => false,
        'teacher_marks_entry_enabled' => false,
    ],

    /*
    | SMS. Off by default: "log" only writes to the log. Switch SMS_DRIVER to "bulk" once the provider is configured.
    */
    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'bulk' => [
            'url' => env('SMS_BULK_URL'),
            'api_key' => env('SMS_BULK_API_KEY'),
            'sender_id' => env('SMS_BULK_SENDER_ID'),
        ],
    ],
];
