# ElasticSearch

Структура аналогична Eloquent-слою: `SearchIndex` → `CRUDRepository` → `CRUDService`.

## SearchIndex

Определяет индекс и маппинг. Расширяет `ElasticIndex` из `mps/elastic-query`.

```php
use MPS\Utils\Components\ElasticSearch\SearchIndex;

class ProductSearchIndex extends SearchIndex
{
    protected string $indexName = 'products';

    // маппинг и настройки — через API mps/elastic-query
}
```

Методы: `documentCreate`, `documentUpdate`, `documentIndex` (upsert), `delete`,
`bulk`, `search`, `mappings`, `clone`, `updateSettings`, `getSettings`.

## Repository

```php
use MPS\Utils\Components\ElasticSearch\Repositories\CRUDRepository;

class ProductSearchRepository extends CRUDRepository implements ProductSearchRepositoryInterface
{
    public function __construct(ProductSearchIndex $index)
    {
        $this->index = $index;
    }

    protected function filterSearch(SearchQuery $query, array &$params = []): void
    {
        if ($name = $params['name'] ?? null) {
            $query->where('name', 'like', $name);
        }
    }
}
```

Методы: `create(CreateAction)`, `update(UpdateAction)`, `index(IndexAction)`,
`delete(DeleteAction)`, `bulk(array $actions)`, `search(array $params, ...)`,
`oneById`, `oneByIdOrFail`, `allByCondition`, `initIndex`, `dropIndex`.

## Actions

```php
use MPS\Utils\Components\ElasticSearch\Actions\CreateAction;
use MPS\Utils\Components\ElasticSearch\Actions\UpdateAction;
use MPS\Utils\Components\ElasticSearch\Actions\IndexAction;
use MPS\Utils\Components\ElasticSearch\Actions\DeleteAction;

// IndexAction — upsert (создать или заменить)
$repository->index(IndexAction::make($id)->setData($data));

// UpdateAction — частичное обновление (только переданные поля)
$repository->update(UpdateAction::make($id)->setData(['price' => 1000]));

// Bulk операции
$repository->bulk([
    IndexAction::make($id1)->setData($data1),
    DeleteAction::make($id2),
]);
```

## Service

```php
use MPS\Utils\Components\ElasticSearch\Services\CRUDService as ElasticCRUDService;

class ProductSearchService extends ElasticCRUDService implements ProductSearchServiceInterface
{
    // create/update/delete — не реализованы (бросают BadRequestException)
    // использовать search() и методы репозитория напрямую
}
```

## Пагинация

`search()` возвращает `LengthAwarePaginatorInterface` с полем `aggregation`.
Лимит ES на один запрос — 10 000 документов.
