# Exceptions

`MPS\Core\Exceptions`

---

## AppException — базовый класс

Все исключения приложения наследуют `AppException`. Умеет сам рендериться в JSON-ответ.

```php
use MPS\Core\Exceptions\AppException;
use MPS\Core\Enums\AppErrorTypeEnum;

throw new AppException(
    message: 'Something went wrong',
    errorType: AppErrorTypeEnum::INTERNAL_SERVER_ERROR,
    statusCode: 500,
    details: ['context' => 'additional info'],
);
```

### JSON-ответ

```json
{
    "status": 500,
    "error": "INTERNAL_SERVER_ERROR",
    "app_error_code": 0,
    "message": "Something went wrong",
    "hostname": "...",
    "details": { "context": "additional info" },
    "file": "...",
    "trace": [...]
}
```

Поля `file` и `trace` присутствуют только при `app.debug = true`.

### Методы

```php
$e->getErrorType(): string
$e->getStatusCode(): int
$e->getDetails(): array
$e->isTrackable(): bool
$e->setIsTrackable(bool $value): static
$e->getAppErrorCode(): AppErrorCodeEnumInterface|int
$e->setAppErrorCode(AppErrorCodeEnumInterface|int): static  // если enum имеет label — устанавливает message
$e->setMessage(string): void
```

### Отслеживаемость (trackable)

По умолчанию `$isTrackable = true`. Выключить для исключений, которые не нужно логировать:

```php
throw (new AppException('Rate limit exceeded', AppErrorTypeEnum::TOO_MANY_REQUESTS))
    ->setIsTrackable(false);
```

---

## Готовые исключения

| Класс | HTTP статус | Когда использовать |
|---|---|---|
| `NotFoundAppException` | 404 | Сущность не найдена |
| `BadRequestAppException` | 400 | Некорректный запрос |
| `ValidationAppException` | 422 | Ошибка валидации данных |
| `UnAuthorizeAppException` | 401 | Не авторизован |
| `AccessDeniedAppException` | 403 | Нет прав |
| `TooManyRequestsAppException` | 429 | Превышен лимит запросов |
| `OperationFailedAppException` | 503 | Операция не выполнена |
| `InvalidConfigurationAppException` | 500 | Ошибка конфигурации (dev/config) |
| `UnTrackableAppException` | — | Ожидаемая ошибка — никогда не логируется |

Все принимают `$message` первым аргументом. HTTP статус определяется автоматически по типу.

```php
use MPS\Core\Exceptions\NotFoundAppException;
use MPS\Core\Exceptions\AccessDeniedAppException;

throw new NotFoundAppException('User not found');
throw new AccessDeniedAppException('Access denied');
```

---

## UnTrackableAppException

Специальный класс для исключений, которые **по своей природе никогда не логируются**.

```php
use MPS\Core\Exceptions\UnTrackableAppException;

throw new UnTrackableAppException(
    message: 'Token expired',
    errorType: AppErrorTypeEnum::UNAUTHORIZED,
    statusCode: 401,
    details: [],
);
```

- `isTrackable()` всегда возвращает `false` — иммутабельно
- `setIsTrackable()` бросает `InvalidConfigurationAppException` — мутация запрещена
- Гарантия на уровне типа: если выбрасывается этот класс, исключение точно не попадёт в Sentry/логи

### Когда использовать

| Ситуация | Подход |
|---|---|
| Ошибка всегда ожидаема по природе (истёкший токен, неверный OTP) | `UnTrackableAppException` |
| Ошибка обычно логируется, но в конкретном месте — нет | `->setIsTrackable(false)` на любом другом исключении |

---

## InvalidConfigurationAppException

Для ошибок конфигурации, некорректных аргументов, нарушения инвариантов при сборке объектов. Используется в `AssertHelper`, `DefinitionHelper`, `InstanceHelper`.

```php
use MPS\Core\Exceptions\InvalidConfigurationAppException;

throw new InvalidConfigurationAppException('Class must implement ServiceInterface');
```

---

## Handler

`MPS\Core\Exceptions\Handler`

Базовый обработчик исключений. Подключается в `bootstrap/app.php` или `app/Exceptions/Handler.php`. Рендерит `AppException` через встроенный `render()` и логирует trackable-исключения.
