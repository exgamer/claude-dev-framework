<?php

declare(strict_types=1);

// Фрагмент config/mps-utils.php хоста: healthcheck из mps/utils (Components\HealthCheck) вместо своего — GET /health/liveness, /health/readiness (БД, кеш, RabbitMQ)
return [
    'health_check' => [
        'enabled' => true,
        'register_routes' => true,
        // Пробы вызывает оркестратор без авторизации
        'middlewares' => [],
    ],
];
