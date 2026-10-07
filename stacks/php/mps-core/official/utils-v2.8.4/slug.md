# Slug

`MPS\Utils\Components\Slug\Services\SlugServiceTrait`

Трейт для сервисов. Автогенерация слага при создании, проверка уникальности.

## Подключение

```php
use MPS\Utils\Components\Slug\Services\SlugServiceInterface;
use MPS\Utils\Components\Slug\Services\SlugServiceTrait;

class YourService extends CRUDService implements YourServiceInterface, SlugServiceInterface
{
    use SlugServiceTrait;

    protected string $slugableAttribute = 'name'; // атрибут-источник (по умолчанию 'name')
}
```

## Использование в хуках

```php
protected function beforeCreate(CreateCommandDataObject $dto): void
{
    $this->setDataSlug($dto); // автогенерация + проверка уникальности
}

protected function beforeUpdate(UpdateCommandDataObject $dto): void
{
    $this->setDataSlug($dto); // только если slug передан явно
}
```

## Методы

```php
$service->setDataSlug(CommandDataObject $dto): void      // вызвать в before-хуке
$service->generateSlug(string $value): string            // Str::slug(normalize($value))
$service->getBySlug(string $value): ?Model
$service->getByMultiSlug(array $values): Collection      // keyed by slug
$service->checkSlugUnique(string $value, ?int $entityId): void  // ValidationAppException
```

## Логика setDataSlug

- Автогенерация только при **create** и только если `slug` не передан явно
- При **update** применяет переданный slug (не генерирует)
- Проверяет уникальность в обоих случаях (исключая текущую сущность при update)
