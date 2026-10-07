# mps/core

Базовый фреймворк-каркас поверх Laravel. Предоставляет абстракции, соглашения и инфраструктурный код для всех MPS-приложений.

Требует PHP 8.3–8.4, Laravel 11–12.

## Установка

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://git.mpinnovations.kz/mps/packages/core.git"
    }
]
```

```bash
composer require mps/core
```

## Консольные команды

```bash
# пересобрать кэш схемы БД вручную
php artisan mps:core:database:schema:preload
```

## Код стайл фиксер
```bash
composer csfix-validate
composer csfix
```

## Статический анализатор кода
```bash
composer phpstan
```

## Тесты
```bash
./vendor/bin/pest --filter=InstanceHelperTest
```

## Структура пакета
```
src/
├── Application.php          — расширение Laravel Application
├── ServiceProvider.php      — точка входа пакета
│
├── Commands/                — базовый Command (application-команды)
├── DataObjects/             — базовые DTO
├── Entitites/               — базовые сущности (Entity)
├── Enums/                   — общие enum'ы + Enumerable интерфейс
├── Events/                  — базовое событие
├── Exceptions/              — иерархия исключений
├── Helpers/                 — утилиты (InstanceHelper, DefinitionHelper и др.)
├── Http/
│   ├── Actions/             — базовый Action
│   ├── Controllers/         — базовый Controller
│   ├── Requests/            — FormRequest, SearchRequest, CommandRequest и др.
│   └── Responses/           — Response, JsonResponse
├── Interfaces/              — Arrayable, Expandable, MakeAwareInterface, Validatable
├── Models/                  — базовая Eloquent-модель
├── Modules/                 — ModuleServiceProvider
├── Providers/               — базовый ServiceProvider
├── Queries/                 — базовый Query
├── Repositories/
│   └── Database/Eloquent/   — базовый Eloquent-репозиторий
├── Services/                — базовый Service
├── Traits/                  — ArrayableTrait, ExpandableTrait, MakeAwareTrait, ValidatableTrait
│
└── Components/
    ├── CRUD/                — готовый CRUD-стек
    ├── Container/           — временная подмена биндингов контейнера
    ├── Database/            — Schema/ (кэш схемы БД), Transaction/ (менеджеры транзакций)
    └── ItemsContainer/      — типизированный контейнер коллекций
```

## Документация по слоям и компонентам

| Файл                                                     | Что описывает |
|----------------------------------------------------------|---|
| [container.md](docs/container.md)                             | ContainerSwapper — временная подмена биндингов с автооткатом |
| [crud.md](docs/crud.md)                                  | CRUDService, CrudServiceDecorator, CRUDRepository, HTTP Actions, DataObjects |
| [data-objects.md](docs/data-objects.md)                       | DataObject, DataObjectCollection, гидрация |
| [helpers.md](docs/helpers.md)                                 | AssertHelper, DefinitionHelper, InstanceHelper, JsonHelper и др. |
| [http.md](docs/http.md)                                       | Action, Controller, FormRequest, SearchRequest, Response |
| [traits.md](docs/traits.md)                                   | ArrayableTrait, ExpandableTrait, MakeAwareTrait, ValidatableTrait |
| [module-service-provider.md](docs/module-service-provider.md) | ModuleServiceProvider — автозагрузчик по соглашению |
| [items-container.md](docs/items-container.md)                 | ItemsContainer, ItemsContainerTrait |
| [exceptions.md](docs/exceptions.md)                           | AppException и вся иерархия исключений |
| [enums.md](docs/enums.md)                                     | Enumerable, EnumerableTrait, встроенные enum'ы пакета |
| [queries.md](docs/queries.md)                                 | Query — базовый класс для read-only запросов |
| [commands.md](docs/commands.md)                               | Command — базовый класс для application-команд |
| [transactions.md](docs/transactions.md)                       | TransactionManager — управление транзакциями на уровне сервиса |

---

## Ключевые механики

### DefinitionHelper — декларативный DI

Биндинги описываются в PHP-массиве (`definitions.php`) без явных вызовов `$app->singleton()` в коде.
Поддерживает: `singleton`/`bind`, mock-подмену при `runningUnitTests()`, алиасы.

```php
// definitions.php
return [
    ['abstract' => SomeInterface::class, 'concrete' => SomeClass::class],
    ['abstract' => SomeInterface::class, 'concrete' => SomeClass::class, 'mock' => MockClass::class],
];
```

### InstanceHelper::objectFromArray — автоматическая гидрация

Заполняет свойства объекта из массива через Reflection. Умеет:
- рекурсивно создавать вложенные `DataObject`
- разворачивать `BackedEnum` / `UnitEnum`
- парсить `Carbon` из строк
- вызывать сеттеры (`setPropertyName`) если они есть
- молча игнорировать ошибки несоответствия типов (через `Log::warning`)

### SchemaDataProvider — кэш схемы БД

Схема таблиц кэшируется в `bootstrap/cache/database-schema.php` и пересобирается автоматически после `artisan migrate` через `MigrationsEnded` event → `SchemaPreloadCommand`.
`CRUDRepository::getColumns()` сначала смотрит в кэш, и только если его нет — идёт в БД.

Пересобрать вручную:

```bash
php artisan mps:core:database:schema:preload
```

### ModuleServiceProvider — автозагрузчик по соглашению

Наследник сам находит `routes/`, `definitions/`, `config/`, `database/migrations`, `resources/lang`, `commands/` относительно своего расположения. Файл или директория — оба варианта поддерживаются.

### ExpandableTrait — механизм eager-loading по запросу

Паттерн для сервисного слоя: методом `expand('relation')` контроллер декларирует что нужно подтянуть, а сервис проверяет `hasExpand()` и решает — загружать связь или нет.

---

## Оценка

### Хорошее

- `DefinitionHelper` — биндинги в конфиге вместо разбросанных `$app->singleton()`. Mock-подмена для тестов из коробки.
- `InstanceHelper::objectFromArray` — мощная гидрация. Рекурсия, Carbon, Enum, сеттеры — всё учтено.
- `ModuleServiceProvider` — соглашение над конфигурацией. Модуль сам знает где его файлы.
- `SchemaDataProvider` + автопересборка после миграций — практичное решение для ускорения `getColumns()`.

### Спорное

- `ExpandableTrait` — ручной eager-loading через строки. Легко опечататься, нет типизации, нет IDE-поддержки.
- `CRUDService` хуки — пустые по умолчанию, ничего не возвращают. При сложной логике цепочка становится непрозрачной.
- `AppErrorTypeEnum` — старый стиль (константы в классе), есть TODO "избавиться от него".

### Проблемное

- `InstanceHelper::objectFromArray` молча игнорирует ошибки (`Log::warning` + пустой catch). Данные тихо не заполняются.
- `CacheMangerAwareTait.php` — опечатка в имени файла и класса (Manger вместо Manager).
- Дублирование: `CRUDServiceInterface` существует в двух местах — `Services/` и `Services/Database/Eloquent/`.

---

## Предложения по улучшению

| Приоритет | Задача | Причина |
|---|---|---|
| 1 | Silent failures в `InstanceHelper` | Риск потери данных |
| 2 | Дубли `CRUDServiceInterface` | Риск расхождения контрактов |
| 3 | `AppErrorTypeEnum` → нативный enum | Технический долг с TODO |
| 4 | Опечатка `CacheMangerAwareTait` | Растёт с каждым наследником |
| 5 | `ExpandableTrait` → enum | Улучшение DX, не срочно |
| 6 | Хуки `CRUDService` возвращают DTO | При следующем касании |
