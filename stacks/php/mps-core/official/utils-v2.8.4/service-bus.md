# ServiceBus

`MPS\Utils\Components\Integration\ServiceBus`

Набор базовых классов для межсервисного взаимодействия через HTTP (internal API).
Построен поверх `HttpRepository` из компонента `Http`.

## Структура

```
Integration/
└── ServiceBus/
    ├── DataObjects/
    │   ├── ServicesResponse.php   — базовый ответ (success + data)
    │   ├── SuccessResponse.php    — успешный ответ с data[]
    │   ├── ErrorResponse.php      — ошибочный ответ с ErrorInfo
    │   └── ErrorInfo.php          — детали ошибки (message, error, status, details)
    ├── Repositories/
    │   ├── RepositoryInterface.php
    │   ├── Repository.php         — базовый HTTP-репозиторий с Auth-Key и обработкой MPS-конверта
    │   ├── CRUDRepositoryInterface.php
    │   └── CRUDRepository.php     — CRUD-методы поверх Repository
    └── Services/
        └── CRUDService.php        — CRUD-сервис поверх CRUDRepositoryInterface
```

## Использование

### Repository — базовый репозиторий

Расширять `Repository`, переопределив `getHost()`, `getBaseUrl()` и `getAppKey()`.

```php
use MPS\Utils\Components\Integration\ServiceBus\Repositories\Repository;

class ProductServiceRepository extends Repository
{
    protected function getHost(): string
    {
        return config('services.product-service.url');
    }

    protected function getBaseUrl(): string
    {
        return '/api/v1/products';
    }

    protected function getAppKey(): string
    {
        return config('services.product-service.key');
    }

    public function findById(int $id): array
    {
        return $this->processResponse($this->get("/{$id}"))->getData();
    }

    public function create(array $data): array
    {
        return $this->processResponse($this->post(data: $data))->getData();
    }
}
```

`processResponse()` разворачивает MPS-конверт `{ success, data }` → `SuccessResponse`.
`getData()` возвращает содержимое поля `data`.

### CRUDRepository — готовые CRUD-операции

Расширять `CRUDRepository`, если внешний сервис поддерживает стандартный REST CRUD.

```php
use MPS\Utils\Components\Integration\ServiceBus\Repositories\CRUDRepository;

class OrderRepository extends CRUDRepository
{
    protected function getHost(): string
    {
        return config('services.order-service.url');
    }

    protected function getBaseUrl(): string
    {
        return '/api/v1/orders';
    }

    protected function getAppKey(): string
    {
        return config('services.order-service.key');
    }
}
```

Методы возвращают `SuccessResponse` (кроме `deleteById` — `void`):

```php
$repository->create(['user_id' => 1, 'total' => 500])->getData();
$repository->paginate(['page' => 1, 'per_page' => 20])->getData();
$repository->getById(42)->getData();
$repository->updateById(42, ['status' => 'completed'])->getData();
$repository->deleteById(42);
```

`paginate()` поддерживает параметр `sort` в виде массива — автоматически преобразует через `implode(',', ...)`.

### CRUDService — сервисный слой поверх CRUDRepository

Оборачивает `CRUDRepository`, распаковывает `SuccessResponse` и вызывает хуки `before`/`after`.

```php
use MPS\Utils\Components\Integration\ServiceBus\Services\CRUDService;
use MPS\Core\Components\CRUD\DataObjects\SearchDataObject;

class OrderService extends CRUDService
{
    public function __construct(OrderRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function searchDataKey(): string
    {
        return 'orders'; // ключ в пагинированном ответе
    }
}

// Использование:
$service->search(SearchDataObject::make()->setParams(['status' => 'new']));
$service->searchById(42);
$service->create($createDto);           // CreateCommandDataObject
$service->updateById(42, ['status' => 'completed']);
$service->deleteById(42);              // $checkExistence = true — делает getById перед удалением
```

`search()` автоматически переключается между `paginate()` (без id) и `getById()` (с id).
`searchByIds()`, массовые `update()` и `delete()` — бросают `BadRequestException` (не поддерживаются).

## Заголовки

`Auth-Key` и proxied-заголовки устанавливаются один раз в `init()` при создании репозитория.

### Proxied-заголовки

Заголовки из `ServiceBusHeaderEnum::proxied()` (например, `Stress-Test-Enabled`) читаются из входящего запроса в момент конструирования репозитория и проксируются на все исходящие вызовы.

## Конфигурация

Компонент не требует собственного конфига. Заголовок `Stress-Test-Enabled` управляется через `StressTestContext` из компонента [StressTest](stress-test.md).

## Поведение по умолчанию

| Параметр | Значение | Описание |
|---|---|---|
| `retry` | `2` | Повторные попытки при ошибке соединения |
| `timeout` | `10` | Таймаут запроса в секундах |
| `Auth-Key` | из `getAppKey()` | Заголовок авторизации между сервисами, устанавливается в `init()` |
| `Stress-Test-Enabled` | проксируется | Читается из входящего запроса при конструировании, если присутствует |
| `catchException` | `true` | Исключение перехватывается, ответ разбирается из тела ошибки |

## Concurrent-запросы

`concurrent()` переопределён в `Repository` — автоматически вызывает `handleError()` для каждого ответа:

```php
$responses = $this->concurrent(function () {
    return [
        'order'   => $this->getById(1),
        'invoice' => $this->getById(2),
    ];
});
```

## Обработка ошибок

При неуспешном HTTP-ответе `handleError()` разбирает тело в формате MPS-конверта
и выбрасывает `AppException` с деталями из `ErrorInfo`:

```json
{
    "success": false,
    "data": {
        "message": "Not found",
        "error": "resource_not_found",
        "status": 404,
        "details": {},
        "app_error_code": "ERR_001"
    }
}
```

Если тело не содержит `success`/`data` — автоматически оборачивается через `ResponseHelper::wrap()`.

## Известные проблемы

`retry` и `timeout` захардкожены в `Repository::init()`. При необходимости изменить — переопределить метод `init()` в своём репозитории.

`Repository` не поддерживает Octane. Заголовки `Auth-Key` и proxied-заголовки устанавливаются в `init()` при конструировании — один раз на жизненный цикл объекта. В Octane репозиторий живёт между запросами, поэтому proxied-заголовки (например, `Stress-Test-Enabled`) фиксируются от первого запроса и утекают в последующие.
