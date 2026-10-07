# StatusChange

`MPS\Utils\Components\StatusChange\Services\StatusChangeServiceTrait`

Трейт для сервисов. Добавляет смену статуса с автоматической валидацией.

## Подключение

```php
use MPS\Utils\Components\StatusChange\Services\StatusChangeServiceInterface;
use MPS\Utils\Components\StatusChange\Services\StatusChangeServiceTrait;

class YourService extends CRUDService implements YourServiceInterface, StatusChangeServiceInterface
{
    use StatusChangeServiceTrait;
}
```

## Методы

```php
$service->statusChange(int|string $id, array $data): bool
$service->statusChangeByModel(Model $model, array $data): bool
```

Автоматически проверяет:
- `$data['status']` определён → иначе `StatusNotDefinedException`
- Новый статус отличается от текущего → иначе `EntityAlreadyThisStatusException`

Выполняется в транзакции.

## Хуки

```php
protected function beforeStatusChange(UpdateCommandDataObject $dto): void
{
    // например: проверить допустимость перехода
}

protected function afterStatusChange(UpdateCommandDataObject $dto): void
{
    // например: отправить событие
}
```

## Request

`MPS\Utils\Components\StatusChange\Requests\StatusChangeRequest` — готовый Request
с валидацией поля `status`.
