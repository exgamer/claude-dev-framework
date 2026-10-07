# MPS\Core\Components\CRUD

Компонент предоставляет полный стек для реализации CRUD-операций: репозиторий, сервис, HTTP Actions, DataObjects, события и хуки.

## Структура

```
Components/CRUD/
├── DataObjects/          — DTO для команд и поиска
├── Enums/                — CommandEnum
├── Events/               — события жизненного цикла операций
├── Http/
│   ├── Actions/          — готовые HTTP-действия
│   └── Controllers/      — базовый CRUDController
├── Interfaces/           — контракты
├── Repositories/         — CRUDRepository
├── Services/             — CRUDService, CrudServiceDecorator
└── Traits/               — SearchAwareTrait
```

---

## DataObjects

### CommandDataObject — базовый DTO для команд

| Метод | Описание |
|---|---|
| `setData(array\|DataObjectInterface)` | Данные для записи в БД |
| `getData(): array` | Возвращает массив (вызывает `toArray()` если DataObject) |
| `setCondition(array\|Closure)` | Условие WHERE |
| `getCondition()` | Возвращает условие |
| `setModel(?Model)` | Привязанная модель |
| `getModel(): ?Model` | Возвращает модель |
| `setSuccess(bool)` | Результат операции |
| `isSuccess(): bool` | Успешно ли выполнена операция |
| `getEntityId(string $key = 'id')` | ID сущности из condition или model |
| `processDataCallback(Closure)` | Модифицировать `$data` через колбэк |

### CreateCommandDataObject

Наследует `CommandDataObject`. Устанавливает `action = CommandEnum::CREATE`.

```php
$dto = (new CreateCommandDataObject())
    ->setData($request->validated());
```

### UpdateCommandDataObject

Наследует `CommandDataObject`. Устанавливает `action = CommandEnum::UPDATE`.

```php
$dto = (new UpdateCommandDataObject())
    ->setCondition(['id' => $id])
    ->setData(['status' => 'active']);
```

### DeleteCommandDataObject

Наследует `CommandDataObject`. Устанавливает `action = CommandEnum::DELETE`.

```php
$dto = (new DeleteCommandDataObject())
    ->setCondition(['user_id' => $userId]);
```

### SearchDataObject — DTO для поиска

| Метод | Описание |
|---|---|
| `setParams(array)` | Параметры фильтрации |
| `setId(mixed)` | Поиск по конкретному ID |
| `setPerPage(int\|bool\|null)` | Размер страницы; `false` — без пагинации |
| `setPage(?int)` | Номер страницы |
| `setItems(Collection\|array)` | Результаты (заполняется сервисом) |
| `processParamsCallback(Closure)` | Модифицировать `$params` через колбэк |
| `processItemsCallback(Closure)` | Модифицировать результаты через колбэк |

```php
$dto = (new SearchDataObject())
    ->setParams(['status' => 'active', 'sort' => ['-created_at']])
    ->setPerPage(20)
    ->setPage(2);

$result = $service->search($dto);
```

### PaginationSearchResult

DTO результата пагинационного поиска.

| Метод | Описание |
|---|---|
| `getItems(): Collection` | Элементы страницы |
| `getPagination(): LengthAwarePaginator` | Объект пагинации |
| `getParams(): array` | Параметры с которыми выполнялся поиск |

---

## CRUDRepository

Абстрактный репозиторий. Наследовать и указать модель через `getModel()`.

### Методы

| Метод | Описание |
|---|---|
| `create(array $data): TModel` | Создание записи |
| `oneById(int\|string\|array $id): ?TModel` | Поиск по PK |
| `oneByIdOrFail(id, ?message): TModel` | Поиск по PK или `NotFoundAppException` |
| `oneByCondition(array\|Closure): ?TModel` | Первая запись по условию |
| `allByCondition(array\|Closure): Collection<int, TModel>` | Все записи по условию |
| `countByCondition(array\|Closure): int` | Количество по условию |
| `isUnique(array\|Closure, ?excludeId): bool` | Проверка уникальности |
| `update(array\|Closure $condition, array $data): bool` | Обновление по условию |
| `updateById(id, array $data): bool` | Обновление по PK |
| `upsert(array $values, array\|string $uniqueBy): bool` | Вставка или обновление |
| `upsertViaModel(array $values, array\|string $uniqueBy): bool` | upsert через fillable модели |
| `delete(array\|Closure $condition): bool` | Удаление по условию |
| `deleteById(id): bool` | Удаление по PK |
| `search(params, id, perPage, page, query): LengthAwarePaginator<int, TModel>\|Collection<int, TModel>` | Поиск с фильтрами и пагинацией |

### Типизация (дженерики)

`CRUDRepository` и `CRUDRepositoryInterface` параметризованы `@template TModel of Model`. В наследнике достаточно указать конкретную модель через `@extends` — IDE и phpstan начнут видеть точные типы возврата (`oneById(): ?Category` вместо `?Model`):

```php
/**
 * @extends CRUDRepository<Category>
 */
class CategoryRepository extends CRUDRepository implements CategoryRepositoryInterface
{
    public function __construct(Category $model)
    {
        $this->model = $model;
    }
}
```

### Условия (condition)

```php
// скалярное значение
$repo->oneByCondition(['status' => 'active']);

// IN clause (значение — массив)
$repo->allByCondition(['id' => [1, 2, 3]]);

// Closure — полный контроль над запросом
$repo->allByCondition(function (Builder $query) {
    $query->where('status', 'active')->orderBy('created_at');
});
```

#### Защита от запроса без WHERE в update/delete

Условие-массив фильтруется по колонкам таблицы (`filterColumns`) — ключи, не совпадающие с колонками, отбрасываются. Если **непустое** условие после фильтрации стало пустым (опечатка в имени колонки, позиционное условие и т.п.), `update`/`delete` выбросят `InvalidConfigurationAppException` (в тексте — список не совпавших ключей) вместо выполнения запроса без `WHERE` на всю таблицу:

```php
// колонки нет → раньше тихо удаляло всю таблицу
// теперь InvalidConfigurationAppException: "...no valid columns: usre_id"
$repo->delete(['usre_id' => 5]); // опечатка в user_id

// позиционное условие тоже не проходит фильтр колонок → исключение
$repo->delete([['status', '!=', 'active']]);
```

Изначально пустое условие (`[]`) — допустимая намеренная операция над всей таблицей:

```php
$repo->delete([]);                 // удалить все строки
$repo->update([], ['active' => 0]); // обновить все строки
```

Closure-условия фильтр не затрагивает — полный контроль остаётся за вызывающим.

### Переопределяемые методы

```php
// кастомные фильтры поиска
protected function filterSearch(Builder $query, array &$params): void {}

// ключ имени страницы в пагинации (по умолчанию 'page')
protected string $pageParamName = 'page';

// ключ сортировки в params (по умолчанию 'sort')
protected string $sortParamName = 'sort';
```

### Сортировка

Передаётся через `params['sort']` как массив строк. Префикс `-` — сортировка по убыванию:

```php
$dto->setParams(['sort' => ['-created_at', 'name']]);
// ORDER BY created_at DESC, name ASC
```

Сортировка только по колонкам таблицы — защита от SQL-инъекций.

---

## CRUDService (наследование + хуки)

Базовый класс для простых сервисов. Полный CRUD с lifecycle-хуками, все операции — в транзакции.

### Методы

| Метод | Описание |
|---|---|
| `create(CreateCommandDataObject): Model` | Создание в транзакции |
| `update(UpdateCommandDataObject): bool` | Обновление в транзакции |
| `updateById(id, array): bool` | Обновление по PK |
| `updateByModel(Model, array): bool` | Обновление по модели |
| `delete(DeleteCommandDataObject): bool` | Удаление в транзакции |
| `deleteById(id, checkExistence): bool` | Удаление по PK |
| `deleteByModel(Model): bool` | Удаление по модели |
| `search(SearchDataObject): array` | Поиск с фильтрами |
| `searchById(id): array` | Поиск по PK |
| `searchByModel(Model): array` | Поиск по модели |
| `searchByIds(array, ?callable, keyBy): array` | Поиск по массиву PK |

### Хуки

Переопределяются в наследнике. Вызываются автоматически внутри транзакции.

| Хук | Когда вызывается |
|---|---|
| `beforeCreate(CommandDataObject)` | До создания |
| `afterCreate(CommandDataObject)` | После создания |
| `beforeUpdate(CommandDataObject)` | До обновления |
| `afterUpdate(CommandDataObject)` | После обновления |
| `beforeModify(CommandDataObject)` | До create и update |
| `afterModify(CommandDataObject)` | После create и update |
| `beforeDelete(CommandDataObject)` | До удаления |
| `afterDelete(CommandDataObject)` | После удаления |
| `beforeSearch(SearchDataObject)` | До поиска |
| `afterSearch(SearchDataObject)` | После поиска |

### Пример

```php
class CategoryService extends CRUDService implements CategoryServiceInterface
{
    public function __construct(CategoryRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    protected function beforeCreate(CommandDataObject $dto): void
    {
        $dto->processDataCallback(function (array &$data) {
            $data['slug'] = Str::slug($data['name']);
        });
    }

    protected function afterCreate(CommandDataObject $dto): void
    {
        $this->cache->invalidate('categories');
    }
}
```

### Типизация (дженерики)

`CRUDService` и `CRUDRepositoryAwareTrait` параметризованы `@template TRepository of CRUDRepositoryInterface`. Без `@extends` свойство `$repository` имеет тип `?CRUDRepositoryInterface`, и phpstan ругается на переопределённый `getRepository()` с конкретным типом возврата (`return.type`). Достаточно указать репозиторий через `@extends` — `$this->repository` и `getRepository()` будут типизированы конкретным интерфейсом. Так как свойство nullable, их тип — `CategoryRepositoryInterface|null`; на level 8+ перед возвратом вызовите `$this->checkRepository()` — он помечен `@phpstan-assert !null $this->repository` и сужает тип до `CategoryRepositoryInterface`:

```php
/**
 * @extends CRUDService<CategoryRepositoryInterface>
 */
class CategoryService extends CRUDService implements CategoryServiceInterface
{
    public function __construct(CategoryRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getRepository(): CategoryRepositoryInterface
    {
        $this->checkRepository();

        return $this->repository;
    }
}
```

### Когда использовать

- Простые справочники
- Логика умещается в хуки без обращения к state сервиса
- Сигнатуры `create/update/delete` не требуют изменений

---

## CrudServiceDecorator (композиция)

Конкретный (не абстрактный) класс для сложных сервисов. Делегирует стандартные CRUD-операции внутреннему `CRUDService` через `__call`, оставляя свободу для кастомных методов с любыми сигнатурами.

### Когда использовать

- Методы требуют доменных DTO вместо `CreateCommandDataObject`
- В транзакции участвует несколько репозиториев
- Нужен полный контроль над флоу операции
- Хуки зависят от state сервиса (userId, deviceId и т.п.)

### Пример

```php
class OrderService extends CrudServiceDecorator implements OrderServiceInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $repository,
        private readonly PaymentServiceInterface $paymentService,
        private readonly NotificationServiceInterface $notificationService,
    ) {
        parent::__construct();
        $this->setRepository($repository);
    }

    public function createOrder(CreateOrderDTO $dto): Order
    {
        return $this->repository->transaction(function () use ($dto) {
            $order = $this->repository->create($dto->toArray());
            $this->paymentService->initiate($order);
            $this->notificationService->orderCreated($order);

            return $order;
        });
    }

    // стандартный CRUD делегируется через __call:
    // $this->deleteById($id), $this->search($dto), $this->updateByModel($model, $data)
}
```

### Запрет методов

```php
class ReadOnlyService extends CrudServiceDecorator
{
    protected const FORBIDDEN_METHODS = [
        'truncate',
        'create',
        'update',
        'updateById',
        'updateByModel',
        'delete',
        'deleteById',
        'deleteByModel',
    ];
}
```

### Антипаттерны

```php
// НЕЛЬЗЯ — интерфейс сервиса наследует CRUDServiceInterface
// фиксирует сигнатуры, возвращает исходную проблему
interface OrderServiceInterface extends CRUDServiceInterface {}

// НЕЛЬЗЯ — переопределять хуки в CrudServiceDecorator
// хуки не вызываются в декораторе, флоу снова непрозрачен
protected function beforeCreate(CommandDataObject $dto): void {}
```

---

## События

Кидаются автоматически внутри `CRUDService`.

| Событие | Когда | DTO |
|---|---|---|
| `BeforeModifyEvent` | До create и update | `ModifyCommandDataObjectInterface` |
| `AfterModifyEvent` | После create и update | `ModifyCommandDataObjectInterface` |
| `BeforeDeleteEvent` | До удаления | `DeleteCommandDataObject` |
| `AfterDeleteEvent` | После удаления | `DeleteCommandDataObject` |
| `AfterSearchEvent` | После поиска | `SearchDataObject` |

```php
class OrderCreatedListener
{
    public function handle(AfterModifyEvent $event): void
    {
        $dto = $event->getDto();

        if ($dto->getAction() === CommandEnum::CREATE) {
            // реакция на создание
        }
    }
}
```

---

## HTTP Actions

Готовые действия для стандартных CRUD-эндпоинтов.

| Action | Метод | Описание |
|---|---|---|
| `IndexAction` | `run()` | Список с фильтрами и пагинацией |
| `ViewAction` | `run(int $id)` | Одна запись по ID |
| `CreateAction` | `run()` | Создание из `$request->validated()` |
| `UpdateAction` | `run(int $id)` | Обновление из `$request->validated()` |
| `DeleteAction` | `run(int $id)` | Удаление, возвращает 204 |

```php
class CategoryController extends CRUDController
{
    public function __construct(CategoryServiceInterface $service)
    {
        $this->service = $service;
    }

    public function index(CategorySearchRequest $request): JsonResponse
    {
        return (new IndexAction($this))->setRequest($request)->run();
    }

    public function store(CategoryCreateRequest $request): JsonResponse
    {
        return (new CreateAction($this))->setRequest($request)->run();
    }

    public function update(int $id, CategoryUpdateRequest $request): JsonResponse
    {
        return (new UpdateAction($this))->setRequest($request)->run($id);
    }

    public function destroy(int $id): JsonResponse
    {
        return (new DeleteAction($this))->run($id);
    }
}
```

---

## Выбор между CRUDService и CrudServiceDecorator

| Критерий | CRUDService | CrudServiceDecorator |
|---|---|---|
| Простой справочник | ✓ | |
| Логика только в хуках | ✓ | |
| Кастомная сигнатура create/update | | ✓ |
| Несколько репозиториев в транзакции | | ✓ |
| Хуки зависят от state сервиса | | ✓ |
| Нужен явный, читаемый флоу | | ✓ |
