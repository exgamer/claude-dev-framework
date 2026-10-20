<?php

declare(strict_types=1);

// Фрагмент config/database.php приложения: таймаут запроса к БД — statement_timeout сервера Postgres
// на подключении: запрос не держит соединение и блокировки дольше предела; значение — только из env
return [
    'connections' => [
        'pgsql' => [
            // PostgresConnector Laravel (12.70 стенда) кладёт server_options в DSN: options='-c statement_timeout=…';
            // миллисекунды, запрос дольше — ошибка БД вместо зависшего воркера. За pgbouncer в режиме transaction
            // параметры старта не передаются — там таймаут задаётся на роли или в пуле
            'server_options' => [
                'statement_timeout' => env('DB_STATEMENT_TIMEOUT_MS'),
            ],
        ],
    ],
];
