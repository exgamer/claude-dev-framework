# Repository

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

## ЗАПРЕЩЕНО в Repository — нарушение = переписать в Service или Command

Контракт репозитория общий для стеков — `architecture/layers.md`, «Правила слоёв»; ниже — как это выглядит в PHP.

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

## Форма (1) — список через `search()` ядра

Форма пагинации списка выбирается пользователем на задаче и записана в разделе «Список» `design.md` (P-19, `../conventions.md` п. 2a): (1) — ниже, (2) через `Page` (`paginate…({E}FilterDto, Page)`, рекомендация; эталон `../examples/parking_app` — на ней) — `../conventions.md` п. 2a, 12c, 12d. Существующий код на (1) — не нарушение. API (1) по справочнику ядра (`reference/mps-core-v1.10.2.md`): репозиторий — `CRUDRepository::search(array $params = [], $id = null, int $perPage = 30, ?int $page = null, ?BuilderContract $query = null): LengthAwarePaginatorContract|Collection` + хук `filterSearch()`; сервис — `search(SearchDataObject $dto): array` (`SearchAwareInterface`; `SearchDataObject`: `setParams`, `setPerPage`, `setPage`); запрос — наследник `Core/Requests/SearchRequest` подпроекта (`page`, `per_page`, `sort`, сортируемые поля — `$sortAttributes`); ответ — `Core/Resources/PaginateResource` подпроекта, помощник запроса — `Core/Database/Query/PaginatedQueryHelper`. API этих классов — по эталону: `../examples/parking_app/Core/Requests/SearchRequest.php`, `../examples/parking_app/Core/Resources/PaginateResource.php`, `../examples/parking_app/Core/Database/Query/PaginatedQueryHelper.php`.

```php
// Request: фильтры + page/per_page/sort от App\ParkingApp\Core\Requests\SearchRequest
class IndexRequest extends SearchRequest
{
    protected array $sortAttributes = ['id', 'name'];

    // фильтры — хук filterRules(); rules() собирает базовый SearchRequest подпроекта (parent::rules() + filterRules())
    public function filterRules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'name' => ['nullable', 'string'],
        ];
    }
}

// контроллер: контекст из middleware — в params (../conventions.md п. 19a)
$search = SearchDataObject::make()
    ->setParams([...$request->validated(), 'parking_ids' => RequestHelper::getParkingIds($request)])
    ->setPerPage((int) $request->validated('per_page', 30))
    ->setPage($request->validated('page'));

return response()->json(PaginateResource::make($this->service->search($search)));
```

```php
namespace App\ParkingApp\Infrastructure\Postgres\YourDomain\YourModule\Repositories;   // реализация — в Infrastructure (P-10), интерфейс — в Domains/.../Repositories/

use App\ParkingApp\Domains\YourDomain\Modules\YourModule\Repositories\YourRepositoryInterface;
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
- Не трансформирует данные под потребителя (`keyBy`, скидки, форматирование, сборка ответа); строка → модель/сущность — допустимо (`architecture/layers.md`, «Правила слоёв»)
- Не вызывает другие сервисы или репозитории
- Не бросает бизнес-исключения и «не найдено» — возвращает `null`, решает вызывающий

```php
// ❌ Бизнес-логика в репозитории
public function getActiveWithDiscount(int $id): ?Tariff
{
    $item = $this->oneById($id);
    if ($item->status !== StatusEnum::ACTIVE) {
        throw new BadRequestAppException('Тариф не активен'); // бизнес-правило → в Service
    }
    
    $item->price = $item->price * 0.9; // трансформация → в Service/Command
    
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

/** @return Collection<int, Tariff> */
public function allByParkingId(int $parkingId): Collection
{
    return $this->getQuery()
        ->where('parking_id', $parkingId)
        ->orderBy('id')
        ->get();
}
```
