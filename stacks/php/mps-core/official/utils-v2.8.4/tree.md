# Tree

`MPS\Utils\Components\Tree`

Компонент для работы с иерархическими структурами (категории, разделы меню).

## Repository Interface

```php
use MPS\Utils\Components\Tree\Repositories\TreeRepositoryInterface;

interface YourTreeRepositoryInterface extends TreeRepositoryInterface {}
```

Методы: `getById(string $id): ?StdClass`, `getByIds(array $ids): Collection`,
`getByEntityId(int $id): Collection`, `getByEntityIds(array $ids): Collection`.

## TreeHelper

Утилиты для построения дерева из плоского списка:

```php
use MPS\Utils\Components\Tree\TreeHelper;

$tree = TreeHelper::buildTree($flatList, parentKey: 'parent_id');
```

## TreeCacheService

Кеширование дерева. Использует `CacheManager`.

```php
use MPS\Utils\Components\Tree\Services\TreeCacheService;

class YourTreeCacheService extends TreeCacheService
{
    protected string $key = 'your-tree';
    protected int $ttl = 3600;
}
```

## Известная проблема

`TreeCacheService` использует deprecated метод `getOrCreate` из `CacheManager`.
Также использует `CacheMangerAwareTait` (опечатка в имени трейта).
