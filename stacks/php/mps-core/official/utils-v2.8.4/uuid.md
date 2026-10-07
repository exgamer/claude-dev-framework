# Uuid

`MPS\Utils\Components\Uuid\Services\UuidServiceTrait`

Трейт для сервисов. Добавляет поиск сущностей по UUID-полю.

## Подключение

```php
use MPS\Utils\Components\Uuid\Services\UuidServiceInterface;
use MPS\Utils\Components\Uuid\Services\UuidServiceTrait;

class YourService extends CRUDService implements YourServiceInterface, UuidServiceInterface
{
    use UuidServiceTrait;
}
```

## Методы

```php
$service->getByUuid(string $value): ?Model
$service->getByUuidOrFail(string $value, ?string $message = null): Model  // NotFoundAppException
$service->getByUuids(array $values): Collection  // keyed by uuid
```
