# Исключения

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается), кроме фактов, сверенных по исходнику ядра и помеченных как ошибка доки (коды ниже); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

## Иерархия

```
AppException (base, trackable)
├── NotFoundAppException          → 404
├── BadRequestAppException        → 400
├── ValidationAppException        → 422
├── OperationFailedAppException   → 503
├── AccessDeniedAppException      → 403
├── UnAuthorizeAppException       → 401
├── InvalidConfigurationAppException → 400 (тип BAD_REQUEST, в Sentry — warning)
├── TooManyRequestsAppException   → 429
└── UnTrackableAppException       → любой HTTP-код, isTrackable=false (иммутабельно)
```

Коды — по исходнику ядра (`AppErrorTypeEnum::statuses()`, проверено на core 1.6–1.10.2). Таблица `official/core-v1.10.2/exceptions.md` даёт `InvalidConfigurationAppException` 500 — это ошибка доки: ядро отдаёт 400 и пишет в Sentry warning. От кода зависит Swagger (`swagger.md`): где `OperationFailedAppException` доходит до ответа — 503, не 500. Пропавший или битый конфиг — свой наследник с 500 (раздел «Где бросать исключения»).

---

## Когда бросать какое исключение

| Ситуация                                                         | Исключение |
|------------------------------------------------------------------|---|
| Сущность не найдена                                              | `NotFoundAppException` |
| Запрос прошёл валидацию, но нарушает бизнес-правило или переход недопустим (`! $model->canArchive()`) | `ValidationAppException` (422) с ошибкой по полю (`regulations/entity.md`, «Переход состояния») |
| Запрос не разбирается (тело, формат, который Request не выразил)  | `BadRequestAppException` (400) |
| Входные данные не прошли валидацию (поля, форматы)               | `ValidationAppException` |
| Операция не может быть выполнена (внешняя ошибка, недоступность) | `OperationFailedAppException` (503) |
| Пользователь не имеет прав на действие                           | `AccessDeniedAppException` |
| Пользователь не аутентифицирован                                 | `UnAuthorizeAppException` |
| Пропал или битый конфиг; объект собран неправильно (не выставлен скоуп DTO) — ошибка кода | свой `final`-наследник `InvalidConfigurationAppException` с 500 (`ServerConfigurationException`, ниже) |
| Превышен лимит запросов                                          | `TooManyRequestsAppException` |

---

## Примеры

```php
// Сущность не найдена; данные с владельцем — со скоупом (../conventions.md п. 12a)
$order = $this->repository->getByIdInBuildings($id, $buildingIds);
if (! $order) {
    throw new NotFoundAppException('Заказ не найден');
}

// Переход недопустим — метод модели (O-26), 422 по полю; inline-проверка статуса — invariants-inline
if (! $order->canCancel()) {
    throw new ValidationAppException('VALIDATION ERROR', ['id' => ['Заказ нельзя отменить в текущем статусе']]);
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
- `OPERATION_FAILED` (503)
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
| Отложенный Job (`Jobs/`, `ShouldQueue`) | Только временная ошибка — `OperationFailedAppException` (Job повторится). Постоянная (записи нет, переход уже не допустим) — без исключения, выход без повтора (`jobs.md`) |
| Controller | Только перевод результата сервиса в HTTP: `NotFoundAppException`, если сервис вернул `null` (решение об ответе — у точки входа; из консоли/очереди «нет записи» обрабатывается по-своему). Бизнес-исключений (`BadRequestAppException`, `ValidationAppException` по инвариантам, проверки статусов) не бросает — это логика сервиса |
| Request | `ValidationAppException` — через `failedValidation()` |

**Ошибка сборки или конфигурации** — пропал или битый конфиг (env, `config()`, обязательная настройка адаптера, провайдера, Command) или объект собран неправильно (не выставлен скоуп DTO — `TariffFilterDto::getParkingIds()`, нет ключа скоупа в `params` `filterSearch()`, нет атрибута контекста в `RequestHelper`; `../conventions.md` п. 19a) — свой `final`-наследник `InvalidConfigurationAppException` в `Core/Exceptions/` подпроекта с типом `INTERNAL_SERVER_ERROR` (500), а не класс ядра как есть. Почему: это ошибка кода или окружения, а не клиента; ядро отдаёт `InvalidConfigurationAppException` 400 и пишет в Sentry warning — клиент получит «плохой запрос», а сбой сервера потеряется среди предупреждений. Наследник готового класса — `../conventions.md` п. 13. Для бизнес-правил (статус, лимит, существование) — **никогда**: там `ValidationAppException`/`NotFoundAppException`. `InvalidConfigurationAppException` ядра как есть свой код не бросает (его бросает само ядро — конструктор `CRUDRepository`, `setIsTrackable()`).

```php
namespace App\YourApp\Core\Exceptions;

use MPS\Core\Enums\AppErrorTypeEnum;
use MPS\Core\Exceptions\AppException;
use MPS\Core\Exceptions\InvalidConfigurationAppException;

/**
 * Ошибка сборки или конфигурации: ошибка сервера — 500 и error в Sentry, а не 400 и warning ядра.
 *
 * @author Имя Фамилия <name@example.com>
 */
final class ServerConfigurationException extends InvalidConfigurationAppException
{
    public function __construct(string $message, array $details = [])
    {
        // конструктор InvalidConfigurationAppException жёстко ставит BAD_REQUEST — вызывается конструктор AppException
        AppException::__construct($message, AppErrorTypeEnum::INTERNAL_SERVER_ERROR, null, $details);
    }
}

// конструктор адаптера: конфиг — аргументом (../conventions.md, «Модели и внешние системы»)
if ($url === '') {
    throw new ServerConfigurationException('Не задан parking-app.partner_billing.url');
}
```
