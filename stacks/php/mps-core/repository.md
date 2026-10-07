# Repository

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они.

## ЗАПРЕЩЕНО в Repository — нарушение = переписать в Service или Command

| Что написал | Куда перенести |
|---|---|
| `if ($item['status'] === ...)` | Service / Command |
| `$item['price'] * 0.9` или любая трансформация данных | Service / Command |
| `throw new BadRequestAppException(...)` | Service / Command |
| `throw new ValidationAppException(...)` | Service / Command |
| вызов `$this->otherService->...` | Service / Command |
| вызов `$this->otherRepository->...` | Service / Command |
| любое решение на основе значений (не только их выборка) | Service / Command |

`NotFoundAppException` — единственное допустимое исключение в Repository, если метод гарантирует возврат.

---

```php
namespace App\Domains\YourDomain\Modules\YourModule\Repositories;

use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use MPS\Core\Components\CRUD\Repositories\Database\Eloquent\CRUDRepository;
use MPS\Utils\Components\QueryFilters\V2\IntFilter;
use MPS\Utils\Components\QueryFilters\V2\StringFilter;

class YourRepository extends CRUDRepository implements YourRepositoryInterface
{
    public function __construct(YourModel $model)
    {
        $this->model = $model;
    }

    protected function filterSearch(BuilderContract $query, array &$params = []): void
    {
        $tableName = $this->getTableName();
        $expanded  = $params['expanded'] ?? [];

        $query->select([
            "{$tableName}.id",
            "{$tableName}.status",
            "{$tableName}.name",
            "{$tableName}.created_at",
            "{$tableName}.updated_at",
        ]);

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

        // expand — загружать связи только по запросу
        if (in_array(YourExpandEnum::RELATION->value, $expanded)) {
            $query->with('relation');
        }
    }
}
```

**Правила filterSearch:**
- Всегда явно указывать `select` — не `select *`
- JOIN'ы до WHERE условий
- Фильтры только из `MPS\Utils\Components\QueryFilters\V2\`
- Связи только через `with()` при наличии в `expanded`

---

## Контракт репозитория

Репозиторий — это **тонкий слой доступа к данным**. Он возвращает сырые данные и не содержит бизнес-логики.

**Репозиторий делает:**
- Выборку данных по фильтрам
- Запись / обновление / удаление
- JOIN-ы и агрегации на уровне SQL

**Репозиторий НЕ делает:**
- Не применяет бизнес-правила (`if order.status == PAID then ...`)
- Не трансформирует и не маппит данные в DTO или другие структуры
- Не вызывает другие сервисы или репозитории
- Не бросает бизнес-исключения (`NotFoundAppException` допустимо, логика домена — нет)

```php
// ❌ Бизнес-логика в репозитории
public function findActiveWithDiscount(int $id): array
{
    $item = $this->findById($id);
    if ($item['status'] !== StatusEnum::ACTIVE->value) {
        throw new BadRequestAppException('Not active'); // бизнес-правило → в Service
    }
    
    $item['price'] = $item['price'] * 0.9; // трансформация → в Service/Command
    
    return $item;
}

// ✅ Репозиторий возвращает сырые данные
public function findById(int $id): array
{
    return $this->searchById($id); // просто данные из БД
}

public function getAllByColumn(string $value): Collection
{
    return $this->allByCondition(['column' => $value]);
}
```
