<?php

return [
    /*
    | Defaults for the fee settings that an admin edits (stored in the Core settings table with a "fees_" prefix).
    | Money values are whole poisha.
    */
    'defaults' => [
        'due_day' => 10,
        'late_fine_amount' => 0,
        'late_fine_after_days' => 0,
        'late_fine_mode' => 'once',
        'auto_generate_monthly' => false,
        'manual_methods' => [],
        'gateway_enabled' => false,
        'gateway_driver' => 'manual',
        'admit_card_requires' => 'exam_invoice',
        'receipt_footer' => '',
        'sms_enabled' => false,
    ],

    // Payment gateway credentials come from .env only, never from the database.
    'gateway' => [
        'sslcommerz' => [
            'store_id' => env('SSLCOMMERZ_STORE_ID'),
            'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
            'sandbox' => env('SSLCOMMERZ_SANDBOX', true),
        ],
        'bkash' => [
            'app_key' => env('BKASH_APP_KEY'),
            'app_secret' => env('BKASH_APP_SECRET'),
            'username' => env('BKASH_USERNAME'),
            'password' => env('BKASH_PASSWORD'),
            'sandbox' => env('BKASH_SANDBOX', true),
        ],
    ],
];
