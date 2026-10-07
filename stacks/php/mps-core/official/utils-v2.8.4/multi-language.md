# MultiLanguage

Паттерн переводов: основная таблица + отдельная таблица переводов
(`entity_id`, `language`, переводимые поля).

## Модель

```php
use MPS\Utils\Components\MultiLanguage\Models\TranslationModelInterface;
use MPS\Utils\Components\MultiLanguage\Models\TranslationModelTrait;

class Brand extends Model implements TranslationModelInterface
{
    use TranslationModelTrait;
    // добавляет: translation() → hasMany(BrandTranslation, 'entity_id')
}
```

`TranslationModelTrait` автоматически определяет класс перевода как `{ModelClass}Translation`.

## Repository

```php
use MPS\Utils\Components\MultiLanguage\Repositories\TranslationEntityRepository;

class BrandRepository extends TranslationEntityRepository implements BrandRepositoryInterface
{
    protected array $translatableAttributes = ['name', 'description'];

    public function translationRepository(): TranslationRepositoryInterface
    {
        return app(BrandTranslationRepositoryInterface::class);
    }
}
```

В `filterSearch` — добавить JOIN через трейт-метод:

```php
protected function filterSearch(BuilderContract $query, array &$params = []): void
{
    $tableName = $this->getTableName();

    $query->select(["{$tableName}.id", "{$tableName}.status"]);

    // JOIN с таблицей переводов + COALESCE(текущий язык, язык по умолчанию)
    $this->translationFilter($query, ['name', 'description']);
}
```

## Service

```php
use MPS\Utils\Components\MultiLanguage\Services\TranslatableServiceInterface;
use MPS\Utils\Components\MultiLanguage\Services\TranslatableServiceTrait;

class BrandService extends CRUDService implements BrandServiceInterface, TranslatableServiceInterface
{
    use TranslatableServiceTrait;

    public function afterCreate(CreateCommandDataObject $dto): void
    {
        $this->translationUpsert($dto, $dto->getModel()->id);
    }

    public function afterUpdate(UpdateCommandDataObject $dto): void
    {
        $this->translationUpsert($dto);
    }
}
```

Методы трейта: `translationSearch(SearchDataObject $dto)`, `translationUpsert(CommandDataObject $dto, ?int $entityId)`.

## Request

Расширить `TranslatableRequest`, переопределить `translatableRules()`:

```php
use MPS\Utils\Components\MultiLanguage\Requests\TranslatableRequest;

class BrandCreateRequest extends TranslatableRequest
{
    protected function translatableRules(): array
    {
        return [
            'name'        => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string', 'max:1024'],
        ];
    }
}
```

Базовый класс добавляет валидацию структуры `translation.{lang}.{field}` автоматически.

## Формат данных

```json
{
    "status": 1,
    "translation": {
        "ru": { "name": "Название", "description": "Описание" },
        "kz": { "name": "Атауы" }
    }
}
```
