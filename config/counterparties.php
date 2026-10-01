<?php

return [
    'mongo_connection' => env('COUNTERPARTY_MONGO_CONNECTION', 'mongodb'),
    'fresh_days' => (int) env('COUNTERPARTY_FRESH_DAYS', 30),
    'remote_min_chars' => (int) env('COUNTERPARTY_REMOTE_MIN_CHARS', 4),
    'suggest_limit' => (int) env('COUNTERPARTY_SUGGEST_LIMIT', 10),
    'query_log_ttl_days' => (int) env('COUNTERPARTY_QUERY_LOG_TTL_DAYS', 90),

    'dadata' => [
        'token' => env('DADATA_TOKEN'),
        'base_url' => env('DADATA_BASE_URL', 'https://suggestions.dadata.ru/suggestions/api/4_1/rs'),
        'timeout' => (int) env('DADATA_TIMEOUT', 10),
    ],
];
