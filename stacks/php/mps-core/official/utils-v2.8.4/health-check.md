# HealthCheck

`MPS\Utils\Components\HealthCheck`

Два эндпоинта по Kubernetes-соглашению. Включается только при `health_check.enabled: true`.

## Эндпоинты

| Эндпоинт | Назначение |
|---|---|
| `GET /health/liveness` | Приложение живо (просто возвращает app info) |
| `GET /health/readiness` | Проверяет DB, Cache, RabbitMQ |

## Конфигурация

```php
// config/mps-utils.php
'health_check' => [
    'enabled'          => env('MPS_UTILS_HEALTH_CHECK_ENABLED', true),
    'register_routes'  => env('MPS_UTILS_HEALTH_CHECK_REGISTER_ROUTES', true),
    'middlewares'      => [],
],
```

## Регистрация

```php
\MPS\Utils\Components\HealthCheck\ServiceProvider::class
```

## Консольные команды

```bash
php artisan mps:utils:health-check:liveness   # проверка работоспособности приложения
php artisan mps:utils:health-check:readiness  # проверка готовности компонентов
```
