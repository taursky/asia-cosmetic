<?php

return [
    'client_id' => env('ONEC_CLIENT_ID', 'asia-cosmetic-1c'),
    'secret' => env('ONEC_SECRET'),
    'token' => env('ONEC_TOKEN'),
    'max_clock_skew' => (int) env('ONEC_MAX_CLOCK_SKEW', 300),
    'locale' => env('ONEC_LOCALE', 'ru'),
    'source' => env('ONEC_SOURCE', '1c-unf'),
];
