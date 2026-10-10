# Queries

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

Запросы инкапсулируют сложные операции чтения, агрегирующие несколько сервисов.

## Структура

Interface — опционален. Создавать если Query инжектируется в другие классы или подменяется в тестах. Если используется только в одном месте — достаточно конкретного класса.

```
Domains/{Domain}/Modules/{Module}/Queries/
├── YourQueryInterface.php   — опционально
└── YourQuery.php
```

## Interface

```php
namespace App\Domains\YourDomain\Modules\YourModule\Queries;

interface YourQueryInterface
{
    public function setFilters(array $filters): self;
    public function prepare(): array;
}
```

## Implementation

```php
namespace App\Domains\YourDomain\Modules\YourModule\Queries;

use MPS\Core\Queries\Query;
use MPS\Utils\Components\Logger\Interfaces\LoggerAwareInterface;
use MPS\Utils\Components\Logger\Traits\LoggerAwareTrait;

class YourQuery extends Query implements YourQueryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private array $filters = [];

    public function __construct(
        private readonly YourServiceInterface $service,
        private readonly RelatedServiceInterface $relatedService,
    ) {}

    public function setFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    public function prepare(): array
    {
        $this->getLogger()->for($this);

        $items = $this->service->searchAll($this->filters);
        $ids   = array_column($items, 'id');

        $related = $this->relatedService->searchByIds($ids);

        foreach ($items as &$item) {
            $item['related'] = $related[$item['id']] ?? [];
        }

        return $items;
    }
}
```

## Использование

```php
public function __construct(
    private readonly YourQueryInterface $query,
) {}

public function getAggregatedData(array $filters): array
{
    return $this->query
        ->setFilters($filters)
        ->prepare();
}
```

## Когда использовать Query vs Service->search()

| Ситуация | Что использовать |
|---|---|
| Простая выборка из одной таблицы | `service->search($dto)` |
| Агрегация нескольких сервисов | `Query` |
| Сложный SQL (UNION, подзапросы) | `Query` с прямым обращением к репозиторию |
| Переиспользуется в нескольких местах | `Query` |
