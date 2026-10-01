<?php

return [
    'base_url' => env('API_FNS_BASE_URL', 'https://api-fns.ru/api'),
    'key' => env('API_FNS_KEY'),
    'timeout' => (int) env('API_FNS_TIMEOUT', 15),
    'cache_ttl' => (int) env('API_FNS_CACHE_TTL', 3600),
];
