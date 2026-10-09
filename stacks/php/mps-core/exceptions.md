# Исключения

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они.

## Иерархия

```
AppException (base, trackable)
├── NotFoundAppException          → 404
├── BadRequestAppException        → 400
├── ValidationAppException        → 422
├── OperationFailedAppException   → OPERATION_FAILED
├── AccessDeniedAppException      → 403
├── UnAuthorizeAppException       → 401
├── InvalidConfigurationAppException → 400
├── TooManyRequestsAppException   → 429
└── UnTrackableAppException       → любой HTTP-код, isTrackable=false (иммутабельно)
```

---

## Когда бросать какое исключение

| Ситуация                                                         | Исключение |
|------------------------------------------------------------------|---|
| Сущность не найдена                                              | `NotFoundAppException` |
| Запрос прошёл валидацию, но нарушает бизнес-правила              | `BadRequestAppException` |
| Входные данные не прошли валидацию (поля, форматы)               | `ValidationAppException` |
| Операция не может быть выполнена (внешняя ошибка, недоступность) | `OperationFailedAppException` |
| Пользователь не имеет прав на действие                           | `AccessDeniedAppException` |
| Пользователь не аутентифицирован                                 | `UnAuthorizeAppException` |
| Неверная конфигурация кода (ошибка разработчика)                 | `InvalidConfigurationAppException` |
| Превышен лимит запросов                                          | `TooManyRequestsAppException` |

---

## Примеры

```php
// Сущность не найдена
$order = $this->repository->findById($id);
if (! $order) {
    throw new NotFoundAppException('Order not found');
}

// Бизнес-правило нарушено
if ($order['status'] !== OrderStatusEnum::PENDING->value) {
    throw new BadRequestAppException('Order is not in pending status');
}

// Ошибка внешнего сервиса
$response = $this->paymentGateway->charge($amount);
if (! $response->isSuccessful()) {
    throw new OperationFailedAppException('Payment failed');
}

// Валидация на уровне домена (редко — обычно Request)
if (count($items) === 0) {
    throw new ValidationAppException('Items list cannot be empty', ['items' => ['required']]);
}
```

---

## details

`BadRequestAppException`, `ValidationAppException`, `OperationFailedAppException`,
`InvalidConfigurationAppException`, `TooManyRequestsAppException` принимают второй
аргумент `$details` — структурированные данные для клиента или лога.

```php
throw new BadRequestAppException(
    'Cannot cancel order',
    ['reason' => 'Payment already processed', 'order_id' => $id]
);

// ValidationAppException: details — массив field => [errors]
throw new ValidationAppException(
    'Validation failed',
    ['phone' => ['Invalid format'], 'email' => ['Already taken']]
);
```

---

## Sentry / трекинг

По умолчанию все исключения **отправляются в Sentry**. HTTP 500 логируются как error,
остальные — как warning.

Следующие типы логируются как **warning** (не error):
- `VALIDATION_ERROR` (422)
- `NOT_FOUND` (404)
- `BAD_REQUEST` (400)
- `OPERATION_FAILED`
- `ACCESS_DENIED` (403)

**`UnTrackableAppException`** — исключение с гарантированным `isTrackable=false`.
Принимает полный набор параметров (`message`, `errorType`, `statusCode`, `details`).
Вызов `setIsTrackable()` на нём бросает `InvalidConfigurationAppException` — мутация запрещена.

```php
// Когда нужно исключение, которое точно не попадёт в Sentry
throw new UnTrackableAppException(
    message: 'Duplicate webhook event',
    errorType: AppErrorTypeEnum::BAD_REQUEST,
    statusCode: 400,
);
```

Чтобы отключить Sentry для обычного исключения разово:

```php
throw (new BadRequestAppException('...'))
    ->setIsTrackable(false);
```

Когда использовать `UnTrackableAppException` вместо `setIsTrackable(false)`:
- Ошибка всегда ожидаема по природе — не должна попасть в Sentry ни при каких условиях
- Нужна гарантия на уровне типа, а не вызова метода

---

## Где бросать исключения

| Слой | Что допустимо |
|---|---|
| Repository | Не бросать «не найдено» — `?Model`, `NotFoundAppException` бросает вызывающий (`../conventions.md` п. 12a) |
| Service | Любое бизнес-исключение |
| Command | Любое бизнес-исключение |
| Job | `OperationFailedAppException`, `NotFoundAppException` |
| Controller | Только перевод результата сервиса в HTTP: `NotFoundAppException`, если сервис вернул `null` (решение об ответе — у точки входа; из консоли/очереди «нет записи» обрабатывается по-своему). Бизнес-исключений (`BadRequestAppException`, `ValidationAppException` по инвариантам, проверки статусов) не бросает — это логика сервиса |
| Request | `ValidationAppException` — через `failedValidation()` |

`InvalidConfigurationAppException` — только в инфраструктурном и системном коде
(Aware-трейты, провайдеры, адаптеры). **Никогда** в бизнес-логике.
