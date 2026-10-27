<?php

declare(strict_types=1);

// Фрагмент config/parking-app.php хоста: outbox событий о тарифах и подпись машинного вызова, значения — только из env
return [
    'outbox_tariff_events' => [
        // Попыток отправки до FAILED (ручной разбор)
        'max_attempts' => env('OUTBOX_TARIFF_EVENTS_MAX_ATTEMPTS'),
        // Событий за одну выборку повтора
        'retry_batch_size' => env('OUTBOX_TARIFF_EVENTS_RETRY_BATCH_SIZE'),
        // Сколько секунд событие не менялось, чтобы считаться зависшим
        'retry_stale_after_seconds' => env('OUTBOX_TARIFF_EVENTS_RETRY_STALE_AFTER_SECONDS'),
    ],

    'integration_signature' => [
        // Общий HMAC-секрет с партнёром
        'secret' => env('INTEGRATION_SIGNATURE_SECRET'),
        // Допустимый разбег часов партнёра и сервиса, секунды
        'tolerance_seconds' => env('INTEGRATION_SIGNATURE_TOLERANCE_SECONDS'),
        // Сколько секунд хранится использованный nonce; не меньше двух окон времени — меньше middleware не запустится
        'nonce_ttl_seconds' => env('INTEGRATION_SIGNATURE_NONCE_TTL_SECONDS'),
    ],
];
