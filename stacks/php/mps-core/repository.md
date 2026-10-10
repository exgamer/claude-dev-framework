# Repository

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

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

Своих `*OrFail`-методов в репозитории нет (`../conventions.md` п. 12a, SRP: метод ищет, а что делать с отсутствием — решает вызывающий): `get…`/`oneBy…` возвращают `?Model`, `NotFoundAppException` бросает сервис/Command/workflow.

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
    }
}
```

**Правила filterSearch:**
- Всегда явно указывать `select` — не `select *`
- JOIN'ы до WHERE условий
- Фильтры только из `MPS\Utils\Components\QueryFilters\V2\` (**[ОШИБКА]**, тег `repository-reinvents-core-helper`). Исключения — только то, чего в V2 нет (AINA-2978):
  - `whereExists`/`EXISTS`-подзапрос (связь «есть/нет записей») — условия **внутри** подзапроса через V2;
  - `!=` и `IS NULL OR !=` — обычный `where` с биндингом (оператора в V2 нет);
  - bool-колонка — `IntFilter` со значением `0`/`1`, не исключение.
- Значение, которое V2 молча отбросит (не-строка в `StringFilter`, не-число в `IntFilter`), — фильтр пропадает и выборка **расширяется**. Поэтому тип значения приводится или неподходящее отсекается в сервисе до репозитория (`ValidationAppException` по ключу фильтра), а не обходится сырым `where` в репозитории.

  ```php
  // плохо: обход V2 «потому что StringFilter отбросит не-строку»
  $query->where("{$table}.name", 'ilike', "%{$params['name']}%");

  // хорошо: сервис отсёк не-строку до репозитория, репозиторий — V2
  StringFilter::make()->setColumn("{$table}.name")->setValue($name)->apply($query);
  ```
- Без Eloquent-связей и `with()`: связанные данные — отдельными методами репозитория пачкой по id (`allByOrganizationIds(array $ids)`), собирает и раскладывает сервис; параметра `expanded` нет (`service.md`, «Связанные данные — без `expanded`»; `../conventions.md`, «Модели и внешние системы»)

---

## Контракт репозитория

Репозиторий — это **тонкий слой доступа к данным**. Он возвращает сырые данные и не содержит бизнес-логики.

**Репозиторий делает:**
- Выборку данных по фильтрам
- Запись / обновление / удаление
- JOIN-ы и агрегации на уровне SQL
- Запись принимает DTO или типизированные аргументы (`createFromDto`, `updateFromDto`, `setDefault(int, bool)`), колонки собирает сам — не массив от сервиса (`../conventions.md` п. 11a)

**Репозиторий НЕ делает:**
- Не применяет бизнес-правила (`if order.status == PAID then ...`)
- Не трансформирует и не маппит данные в DTO или другие структуры
- Не вызывает другие сервисы или репозитории
- Не бросает бизнес-исключения и «не найдено» — возвращает `null`, решает вызывающий

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

// ✅ Репозиторий возвращает сырые данные по условию, решение — в Service
public function getDefaultByParkingId(int $parkingId): ?Tariff
{
    /** @var ?Tariff */
    return $this->getQuery()
        ->where('parking_id', $parkingId)
        ->where('is_default', true)
        ->first();
}

public function getAllByColumn(string $value): Collection
{
    return $this->allByCondition(['column' => $value]);
}
```
