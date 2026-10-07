# Queries

`MPS\Core\Queries\Query`

Базовый класс для read-only запросов. Паттерн для вынесения сложной логики выборки из репозитория в отдельный объект.

## Использование

Наследовать и реализовать `prepare()`:

```php
use MPS\Core\Queries\Query;

class ActiveProductsQuery extends Query
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    protected function prepare(): mixed
    {
        return $this->repository->allByCondition(
            function (Builder $query) {
                $query
                    ->where('status', StatusEnum::ACTIVE)
                    ->when(
                        isset($this->queryParams['category_id']),
                        fn ($q) => $q->where('category_id', $this->queryParams['category_id']),
                    );
            }
        );
    }
}

// вызов
$products = (new ActiveProductsQuery($repository))
    ->setQueryParams(['category_id' => 5])
    ->get();
```

## Методы

```php
// установить параметры фильтрации целиком
$query->setQueryParams(['status' => 1, 'category_id' => 5]): self

// добавить один параметр
$query->pushQueryParam('category_id', 5): self

// сгруппировать результат по полю (через collect()->keyBy())
$query->setKeyBy('id'): self

// выполнить запрос
$query->get(): mixed
```

## ExpandableTrait

`Query` включает `ExpandableTrait` — можно декларировать eager-loading так же как в сервисах:

```php
$products = (new ActiveProductsQuery($repository))
    ->expand('images')
    ->expand('prices')
    ->get();

// внутри prepare()
protected function prepare(): mixed
{
    $withs = [];

    if ($this->hasExpand('images')) {
        $withs[] = 'images';
    }

    return $this->repository->allByCondition(
        fn ($q) => $q->with($withs)
    );
}
```

## Когда использовать

- Сложная выборка с множеством условий, которая не вписывается в `filterSearch()` репозитория
- Один и тот же запрос нужен из нескольких сервисов
- Запрос требует агрегации из нескольких репозиториев
- Нужен `keyBy` для построения lookup-таблицы

Для простых выборок достаточно методов `CRUDRepository` напрямую.
