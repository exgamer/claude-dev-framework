# Queries

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

Query — большая операция **чтения** одного домена, вынесенная из сервиса (`architecture/layers.md`): несколько выборок своих модулей и сборка результата. Другой домен — workflow (O-7).

## Структура

```
Domains/{Domain}/Modules/{Module}/Queries/
└── {Action}Query.php            — final, метод execute(); интерфейс — только если Query подменяется в тестах или инжектируется через границу (../conventions.md п. 5)
```

## Implementation

Параметры — типизированный DTO фильтра, не `array`; данные других модулей своего домена — через их интерфейсы (O-6), пачкой по id (`approaches/patterns/performance.md`).

```php
namespace App\ParkingApp\Domains\YourDomain\Modules\YourModule\Queries;

use MPS\Core\Queries\Query;
use MPS\Utils\Components\Logger\Interfaces\LoggerAwareInterface;
use MPS\Utils\Components\Logger\Traits\LoggerAwareTrait;

final class ListYourItemsWithRelatedQuery extends Query implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly YourRepositoryInterface $repository,
        private readonly RelatedRepositoryInterface $relatedRepository,   // другой модуль своего домена
    ) {
    }

    /**
     * @return list<YourItemView>
     */
    public function execute(YourItemsFilterDto $filter): array
    {
        $this->getLogger()->for($this);

        $items = $this->repository->listByFilter($filter);
        $relatedByItemId = $this->relatedRepository->allByItemIds(array_column($items, 'id'));

        $result = [];
        foreach ($items as $item) {
            $result[] = new YourItemView($item, $relatedByItemId[$item->id] ?? []);
        }

        return $result;
    }
}
```

## Использование

```php
public function __construct(
    private readonly ListYourItemsWithRelatedQuery $query,
) {
}

$items = $this->query->execute($filter);
```

## Когда Query, когда метод сервиса

| Ситуация | Что использовать |
|---|---|
| Простая выборка одного модуля | метод сервиса → метод репозитория |
| Несколько выборок и сборка внутри одного домена | `Query` |
| Сложный SQL (UNION, подзапросы) | метод репозитория; `Query` только собирает результат |
| Данные другого домена | workflow (O-7) |
