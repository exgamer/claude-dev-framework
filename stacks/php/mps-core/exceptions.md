# Исключения

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

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
$order = $this->repository->oneById($id);
if (! $order) {
    throw new NotFoundAppException('Заказ не найден');
}

// Бизнес-правило нарушено
if ($order->status !== OrderStatusEnum::PENDING) {
    throw new BadRequestAppException('Заказ не в статусе ожидания');
}

// Ошибка внешнего сервиса
$response = $this->paymentRepository->charge($amount);
if (! $response->isSuccessful()) {
    throw new OperationFailedAppException('Оплата не прошла');
}

// Доменный инвариант — через {E}DtoValidator (P-8), сервис бросает исключение
$errors = OrderDtoValidator::validate($dto);
if ($errors) {
    throw new ValidationAppException('VALIDATION ERROR', $errors);
}
```

---

## details

`BadRequestAppException`, `ValidationAppException`, `OperationFailedAppException`,
`InvalidConfigurationAppException`, `TooManyRequestsAppException` принимают второй
аргумент `$details` — структурированные данные для клиента или лога.

```php
throw new BadRequestAppException(
    'Заказ нельзя отменить',
    ['reason' => 'Платёж уже проведён', 'order_id' => $id]
);

// ValidationAppException: details — массив field => [errors]
throw new ValidationAppException(
    'VALIDATION ERROR',
    ['phone' => ['Неверный формат'], 'email' => ['Уже занят']]
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

Ошибку, которую намеренно не пробрасывают (побочный эффект после коммита), — в Sentry через `ExceptionHandler::report($e)`, не только в лог — `../conventions.md` п. 17a.

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
| Consumer RabbitMQ (`Entrypoints/Queue`) | Только временная ошибка — `OperationFailedAppException` (сообщение уйдёт в повтор). Постоянная (битый payload, нет поля, запись не появится) — без исключения: статус/лог и `return` (`jobs.md`) |
| Отложенный Job (`Jobs/`, `ShouldQueue`) | `OperationFailedAppException`, `NotFoundAppException` |
| Controller | Только перевод результата сервиса в HTTP: `NotFoundAppException`, если сервис вернул `null` (решение об ответе — у точки входа; из консоли/очереди «нет записи» обрабатывается по-своему). Бизнес-исключений (`BadRequestAppException`, `ValidationAppException` по инвариантам, проверки статусов) не бросает — это логика сервиса |
| Request | `ValidationAppException` — через `failedValidation()` |

`InvalidConfigurationAppException` — ошибка сборки или конфигурации, не бизнес-правило: инфраструктурный и системный код (Aware-трейты, провайдеры, адаптеры — нет обязательной настройки) и домен, когда объект собран неправильно — не выставлен скоуп DTO (`TariffFilterDto::getParkingIds()`, `../conventions.md` п. 19a), нет обязательной настройки Command (конструкторы Command outbox эталона). Для бизнес-правил (статус, лимит, существование) — **никогда**: там `ValidationAppException`/`NotFoundAppException`/`BadRequestAppException`.
