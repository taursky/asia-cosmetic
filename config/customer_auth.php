<?php

return [
    'default_country_code' => env('CUSTOMER_AUTH_COUNTRY_CODE', '7'),

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'code_length' => 6,
        'ttl_minutes' => 5,
        'max_attempts' => 5,
        'resend_seconds' => 60,
    ],
];
