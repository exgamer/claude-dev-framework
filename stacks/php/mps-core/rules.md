# Правила разработки

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

## DI — только через definitions.php

```php
// НЕЛЬЗЯ (di-outside-definitions, ../conventions.md п. 7); $this->app->bind() в провайдере — только порт подпроекта с переопределением извне
$app->singleton(SomeInterface::class, SomeClass::class);

// ПРАВИЛЬНО — definitions.php из перечня ../structure.md («Биндинги DI»)
return [
    // singleton с маппингом интерфейс → класс
    [
        'abstract'  => SomeInterface::class,
        'concrete'  => SomeClass::class,
    ],

    // с mock-подменой в тестах
    [
        'abstract'  => SomeInterface::class,
        'concrete'  => SomeClass::class,
        'mock'      => MockSomeClass::class,
    ],

    // bind вместо singleton
    [
        'bindType'  => 'bind',
        'abstract'  => SomeInterface::class,
        'concrete'  => SomeClass::class,
    ],

    // с алиасом
    [
        'abstract'  => SomeInterface::class,
        'concrete'  => SomeClass::class,
        'alias'     => 'some-alias',
    ],
];
```

**Где находится** — перечень мест один: `../structure.md`, «Биндинги DI» (`../conventions.md` п. 7).

**Поля:**
- `abstract` — интерфейс (обязательно)
- `concrete` — реализация (обязательно)
- `mock` — мок для тестов, используется когда есть внешние зависимости (HTTP, файловая система)
- `bindType` — по умолчанию `'singleton'`. До core 1.9.0 — только строки `'singleton'`/`'bind'` (иначе исключение). С **core ≥ 1.9.0** — строка или `MPS\Core\Components\Container\Enums\BindTypeEnum`: `singleton`, `bind`, `scoped`, `singletonIf`, `bindIf`, `scopedIf`.
- `alias` — строковый псевдоним для `$application->alias()`

Новый домен и глобальный `config/definitions.php` — там же (`../structure.md`, «Биндинги DI»).

## Исключения — только из MPS\Core\Exceptions

```php
// НЕЛЬЗЯ (exception-not-domain-type, ../conventions.md п. 13)
throw new \Exception('...');
throw new \RuntimeException('...');

// ПРАВИЛЬНО
use MPS\Core\Exceptions\NotFoundAppException;
use MPS\Core\Exceptions\BadRequestAppException;
use MPS\Core\Exceptions\ValidationAppException;
use MPS\Core\Exceptions\OperationFailedAppException;
use MPS\Core\Exceptions\AccessDeniedAppException;
use MPS\Core\Exceptions\UnAuthorizeAppException;
```

## Queue — только RabbitMQ V2

```php
// НЕЛЬЗЯ — устарел
RabbitManager::putIn(...);

// ПРАВИЛЬНО
use MPS\Utils\Components\Queue\RabbitMQ\V2\Interfaces\QueueManagerInterface;
$this->queueManager->putInQueue($queueConfig, $payload);
```

## QueryFilters — только V2

```php
// НЕЛЬЗЯ
use MPS\Utils\Components\QueryFilters\StringFilter; // V1

// ПРАВИЛЬНО
use MPS\Utils\Components\QueryFilters\V2\StringFilter;
use MPS\Utils\Components\QueryFilters\V2\IntFilter;
use MPS\Utils\Components\QueryFilters\V2\RangeFilter;
```

## Запись — `create`/`update`/`updateById` ядра

`CRUDRepository::update()`/`updateById()` пишут query builder-ом: `$casts` не применяются, `$fillable` не проверяется — отсекаются только несуществующие колонки (проверено на core 1.6.0, AINA-2978); `create()` тоже принимает произвольный массив. Поэтому из сервиса/Command/workflow они с массивом не вызываются: запись — методами репозитория с DTO (`conventions.md` п. 11a), внутри — явный список колонок. Вставка — `fill()->save()` у модели с casts; обновление — одним `UPDATE … WHERE` (`architecture/layers.md`, «Метод репозитория — один запрос»), casts при этом не работают — enum и JSON в списке колонок приводятся явно.

```php
// плохо: массив из сервиса, json/enum мимо casts, любая колонка таблицы запишется
$this->repository->updateById($id, $dto->toArrayWithSnakeKeys());

// хорошо: репозиторий; false — запись отклонил уникальный индекс, решение (422) — у сервиса
public function updateFromDto(int $id, TariffDto $dto): bool
{
    $columns = $this->columns($dto);   // запись нашёл и проверил сервис (../conventions.md п. 12a); enum — ->value

    try {
        $this->getQuery()
            ->whereKey($id)
            ->update($columns);
    } catch (QueryException $e) {
        if ($this->isUniqueViolation($e, self::DEFAULT_TARIFF_UNIQUE_INDEX)) {
            return false;
        }

        throw $e;
    }

    return true;
}
```

Без уникального индекса на изменяемых колонках метод может быть `void`. Эталон — `TariffRepository::updateFromDto` в `../examples/parking_app/Infrastructure/Postgres/Billing/Tariffs/Repositories/`.

## Cache — образец cache-aside, API — по версии mps/utils

Форма — cache-aside inline в сервисе-потребителе (`approaches/patterns/cache.md`): кеш-репозиторий домена (`{E}CacheRepositoryInterface` в `Domains/…/Repositories/`, реализация — `Infrastructure/Cache/{D}/{M}/Repositories/`) тупой — `get…`/`set`/`forget` над `CacheManagerInterface`, без решений; сервис пробует кеш → промах → репозиторий БД → запись в кеш; ошибка кеша не роняет сценарий (`OperationFailedAppException` → `ExceptionHandler::report()`, `../conventions.md` п. 17a); сброс — в сценарии записи, после коммита. Эталон — `TariffCrudService::findById` и `TariffCacheRepository` (`../examples/parking_app/`).

```php
// сервис: cache-aside inline
$cached = null;

try {
    $cached = $this->cacheRepository->getById($id);
} catch (OperationFailedAppException $e) {
    $this->exceptionHandler->report($e);   // кеш недоступен — читаем из БД
}

if ($cached) {
    return $cached;                        // запись со скоупом — сверка скоупа после чтения (architecture/layers.md)
}

$tariff = $this->repository->getByIdInParkings($id, $parkingIds);
```

API менеджера кеша — по версии `mps/utils` из `composer.lock` (`capabilities.md`, «Кеш и Redis»):

```php
// ключ: utils < 2.6.4 (например 2.6.2, эталон) — другого API нет
$this->cacheManager->getCacheKeyByParts(CacheKeyEnum::TARIFF->value, (string) $id);

// ключ: utils ≥ 2.6.4 — getCacheKeyByParts и getOrCreate @deprecated
KeyHelper::generate(CacheKeyEnum::TARIFF->value, $id);

// замыкание «прочитать или посчитать» — если оно нужно (существующий код): < 2.6.4 — getOrCreate, ≥ 2.6.4 — getOrSet
$cache->getOrCreate($key, fn () => ..., $ttl);
$cache->getOrSet($key, fn () => ..., $ttl);

// теги / автообновление — только utils ≥ 2.8, SmartCache
$smartCache->getOrSet($key, new CallbackOptions(Handler::class, 'method', $params), CacheOptions::make()->ttl(3600)->tags('orders'));

// НЕЛЬЗЯ ни в какой версии — теги через Laravel Cache в обход ядра:
$cache->tags(['orders'])->put(...);
```

## Logger

```php
// НЕЛЬЗЯ — deprecated
$this->getLogger()->setMessagePrefix('{SomeService}');

// Автоматическая генерация префикса
$this->setLogger($logger); // внутри $logger->for($this);

// ПРАВИЛЬНО — префикс из имени класса
$this->getLogger()->for($this);

// ПРАВИЛЬНО — кастомный префикс
$this->getLogger()->for($this)->setPrefix('{Custom}');
```

LogContext::clear() обязателен в middleware при Octane — без этого контекст предыдущего запроса утекает в следующий.

## FileSystem — только UploadManager для загрузки

```php
// НЕЛЬЗЯ — deprecated since v2.3.0
$fileManager->upload($file, $dir);
$fileManager->uploadFromUrl($url, $dir);

// ПРАВИЛЬНО — у UploadManager нет метода upload(), метод по источнику:
$uploadManager->fromObject($file, $dir);
$uploadManager->fromUrl($url, $dir);
$uploadManager->fromBase64($base64, $dir);

// FileManager оставить только для: get, has, put, getUrl, getTmpUrl, getFileInfo
```

## Сервисы — выбор базового класса

| Ситуация | Класс |
|---|---|
| Простой справочник на чтение (поиск/показ ядра) | `extends CRUDService`; запись — свои методы репозитория с DTO (`createFromDto`/`updateFromDto`), не `create()`/`update()` ядра массивом (`../conventions.md` п. 11a, P-18, тег `write-array-instead-of-dto`) |
| Кастомные сигнатуры методов, сложный флоу | `extends CrudServiceDecorator` (core ≥ 1.7.0); в более старом ядре — `extends Service` + свои методы |
| Очень сложный сервис, свои методы | `implements ServiceInterface` напрямую |

Интерфейс сложного сервиса **не наследует** `CRUDServiceInterface` — это фиксирует сигнатуры и возвращает исходную проблему.
