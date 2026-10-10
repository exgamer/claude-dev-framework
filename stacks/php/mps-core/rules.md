# Правила разработки

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

## DI — рекомендуется через definitions.php

```php
// НЕ РЕКОМЕНДУЕТСЯ стандартный биндинг
$app->singleton(SomeInterface::class, SomeClass::class);

// ПРАВИЛЬНО — definitions.php домена или config/definitions.php
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

**Где находится:**
- `Infrastructure/Postgres/{Domain}/Providers/definitions.php` — биндинги домена (целевая структура)
- `config/definitions.php` — только глобальные, cross-domain биндинги

**Поля:**
- `abstract` — интерфейс (обязательно)
- `concrete` — реализация (обязательно)
- `mock` — мок для тестов, используется когда есть внешние зависимости (HTTP, файловая система)
- `bindType` — по умолчанию `'singleton'`. До core 1.9.0 — только строки `'singleton'`/`'bind'` (иначе исключение). С **core ≥ 1.9.0** — строка или `MPS\Core\Components\Container\Enums\BindTypeEnum`: `singleton`, `bind`, `scoped`, `singletonIf`, `bindIf`, `scopedIf`.
- `alias` — строковый псевдоним для `$application->alias()`

Новый домен — новый `definitions.php` в `Infrastructure/Postgres/{Domain}/Providers/`. Не добавлять биндинги домена в глобальный `config/definitions.php`.

## Исключения — рекомендуется из MPS\Core\Exceptions

```php
// НЕ РЕКОМЕНДУЕТСЯ
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

`CRUDRepository::update()`/`updateById()` пишут query builder-ом: `$casts` не применяются, `$fillable` не проверяется — отсекаются только несуществующие колонки (проверено на core 1.6.0, AINA-2978); `create()` тоже принимает произвольный массив. Поэтому из сервиса/Command/workflow они с массивом не вызываются: запись — методами репозитория с DTO (`conventions.md` п. 11a), внутри — явный список колонок и `fill()->save()` у модели с casts.

```php
// плохо: массив из сервиса, json/enum мимо casts, любая колонка таблицы запишется
$this->repository->updateById($id, $dto->toArrayWithSnakeKeys());

// хорошо: репозиторий
public function updateFromDto(Tariff $tariff, TariffDto $dto): void
{
    $tariff->fill($this->columns($dto))->save();   // модель нашёл и проверил сервис (../conventions.md п. 12a)
}
```

## Cache — зависит от версии mps/utils

Версию смотреть в `composer.lock`, API — в `capabilities.md`, «Кеш и Redis».

```php
// utils < 2.6.4 (например superapp-api, 2.6.2) — другого API нет:
$cache->getOrCreate($key, fn () => ..., $ttl);

// utils ≥ 2.6.4 — getOrCreate и getCacheKeyByParts @deprecated:
$cache->getOrSet(KeyHelper::generate('orders', $id), fn () => ..., $ttl);

// utils ≥ 2.8 — нужны теги / автообновление:
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
| Простой справочник, логика в хуках | `extends CRUDService` |
| Кастомные сигнатуры методов, сложный флоу | `extends CrudServiceDecorator` (core ≥ 1.7.0); в более старом ядре — `extends Service` + свои методы |
| Очень сложный сервис, свои методы | `implements ServiceInterface` напрямую |

Интерфейс сложного сервиса **не наследует** `CRUDServiceInterface` — это фиксирует сигнатуры и возвращает исходную проблему.
