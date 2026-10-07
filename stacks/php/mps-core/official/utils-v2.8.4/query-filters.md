# QueryFilters

`MPS\Utils\Components\QueryFilters\V2\*`

Использовать только **V2**. V1 помечен `@deprecated`.

## Доступные фильтры

```php
use MPS\Utils\Components\QueryFilters\V2\IntFilter;
use MPS\Utils\Components\QueryFilters\V2\StringFilter;
use MPS\Utils\Components\QueryFilters\V2\RangeFilter;
use MPS\Utils\Components\QueryFilters\V2\BoolFilter;
use MPS\Utils\Components\QueryFilters\V2\InFilter;
```

## Использование в Repository

```php
protected function filterSearch(BuilderContract $query, array &$params = []): void
{
    $tableName = $this->getTableName();

    if ($id = $params['id'] ?? null) {
        IntFilter::make()
            ->setColumn("{$tableName}.id")
            ->setValue($id)
            ->apply($query);
    }

    if ($name = $params['name'] ?? null) {
        StringFilter::make()
            ->setColumn("{$tableName}.name")
            ->setValue($name)
            ->apply($query);
    }

    if ($status = $params['status'] ?? null) {
        IntFilter::make()
            ->setColumn("{$tableName}.status")
            ->setValue($status)
            ->apply($query);
    }

    // RangeFilter — диапазон дат или чисел
    if ($createdAt = $params['created_at'] ?? null) {
        RangeFilter::make()
            ->setColumn("{$tableName}.created_at")
            ->setValue($createdAt) // ['from' => '...', 'to' => '...']
            ->apply($query);
    }
}
```

## StringFilter — режимы поиска

По умолчанию `LIKE %value%`. Поддерживает точное совпадение через конфигурацию фильтра.

## acceptNull

V2 добавил `acceptNull()` — фильтр применяется даже если значение `null` (WHERE IS NULL).
