# StressTest

`MPS\Utils\Components\StressTest`

Компонент для маркировки нагрузочных запросов: обходит throttle и добавляет контекст в логи.
Активируется через HTTP-заголовок — флагом или JWT-токеном.

## Middleware

Два варианта — выбрать один в зависимости от требований к безопасности:

| Middleware | Заголовок                  | Принцип |
|---|----------------------------|---|
| `StressTestingByFlagMiddleware` | `Stress-Test-Enabled: 1`   | IP из `allowed_ips` |
| `StressTestingByTokenMiddleware` | `Stress-Test-Token: <jwt>` | JWT + IP из `allowed_ips` |

```php
use MPS\Utils\Components\StressTest\Http\Middleware\StressTestingByFlagMiddleware;
use MPS\Utils\Components\StressTest\Http\Middleware\StressTestingByTokenMiddleware;

// routes/api.php
Route::middleware([StressTestingByFlagMiddleware::class])->group(...);
// или
Route::middleware([StressTestingByTokenMiddleware::class])->group(...);
```

При неверном или просроченном токене `StressTestingByTokenMiddleware` бросает `UnauthorizedHttpException`.
Если заголовок `Stress-Test-Token` отсутствует — запрос пропускается без ошибки.

## Обход throttle

`StressTestThrottleRequests` расширяет стандартный `ThrottleRequests` и пропускает
запросы без ограничений, когда стресс-тест активен и `disable_throttle=true`.

`ServiceProvider` автоматически алиасирует `throttle` на него при старте:

```php
// регистрируется автоматически, если disable_throttle=true
$app['router']->aliasMiddleware('throttle', StressTestThrottleRequests::class);
```

## StressTestContext

`MPS\Utils\Components\StressTest\StressTestContext` — scoped-объект (один экземпляр на запрос).
Хранит состояние: активен ли стресс-тест и произвольные метаданные.

```php
$context->isEnabled(): bool
$context->metadata(): array

$context->enable(array $metadata = []): void
$context->disable(): void
```

Доступен через DI:

```php
public function __construct(private readonly StressTestContext $context) {}
```

## Лог-контекст

При активации любой из middleware добавляет контекст в логи текущего запроса:

```php
Log::withContext(['stress_test' => true]);
```

При Octane контекст очищается автоматически через `RequestTerminated`-listener,
который регистрируется в `ServiceProvider::boot()`, если Octane установлен:

```php
if (class_exists(\Laravel\Octane\Events\RequestTerminated::class)) {
    Event::listen(\Laravel\Octane\Events\RequestTerminated::class, function () {
        Log::withoutContext();
    });
}
```

## Конфигурация

```php
// config/mps-utils.php
'stress_test' => [
    'secret'           => env('MPS_UTILS_STRESS_TEST_SECRET'),
    'disable_throttle' => env('MPS_UTILS_STRESS_TEST_DISABLE_THROTTLE', true),
    'allowed_ips'      => array_filter(explode(',', env('MPS_UTILS_STRESS_TEST_ALLOWED_IPS', ''))),
],
```

| Переменная | По умолчанию | Описание |
|---|---|---|
| `MPS_UTILS_STRESS_TEST_SECRET` | — | Секрет для подписи JWT. Обязателен для `StressTestingByTokenMiddleware` |
| `MPS_UTILS_STRESS_TEST_DISABLE_THROTTLE` | `true` | Алиасировать `throttle` на `StressTestThrottleRequests` |
| `MPS_UTILS_STRESS_TEST_ALLOWED_IPS` | `''` (все) | Список разрешённых IP через запятую |

## Генерация токена

```bash
php artisan mps:utils:stress-test:token:generate --ttl=3600
```

Требует `MPS_UTILS_STRESS_TEST_SECRET`. Выводит JWT-токен, который передаётся
в заголовке `Stress-Test-Token` при нагрузочном тестировании.
