# Стиль кода (PHP)

Уточняет `core/style.md`. Форматирование проверяется `php-cs-fixer.php` проекта (`composer csfix-validate`) — его правила главнее примеров ниже.

---

## Шапка класса

- `declare(strict_types=1);` в каждом файле.
- Docblock класса: одна строка по-русски, что это, + `@author` автора:
  ```php
  /**
   * CRUD Сервис тарифов. (Только CRUD)
   *
   * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
   */
  ```
  Для workflow — 1–3 строки **почему** он существует, а не что делает построчно:
  ```php
  /**
   * Создаёт тариф с предварительной проверкой существования парковки.
   *
   * Billing-сервис намеренно не знает о Catalog-домене — эта зависимость живёт здесь.
   */
  ```
- Шаблонные docblock-и вида `@package App\…`, «Реализует бизнес-логику управления…», «Обеспечивает типобезопасность…» (стиль части `super_app`) — **[ВНИМАНИЕ]**, это пересказ, а не смысл.
- `@param`/`@return` в docblock — только если добавляют информацию сверх сигнатуры (`array<int, string[]>`, generic, `@throws`). Дублировать `@param int $id` при типизированной сигнатуре не нужно.
- Описание метода, пересказывающее его имя (`findAliveByIds` → «Неудалённые здания по набору id»), не писать — **[ИНФО]** (`core/style.md`, «Комментарии»):

    ```php
    // плохо
    /**
     * Неудалённые здания по набору id.
     *
     * @param  array<int, int>  $ids
     * @return array<int, Building> карта id здания => здание
     */
    public function findAliveByIds(array $ids): array;

    // хорошо
    /**
     * @param  array<int, int>  $ids
     * @return array<int, Building> карта id здания => здание
     */
    public function findAliveByIds(array $ids): array;
    ```

## Классы

- `final` — для Command/Query, workflow, валидаторов, хелперов. Сервисы и репозитории — не `final` (наследуют ядро, подменяются моками).
- Конструктор — promotion: `private readonly Type $name`, по одному на строку, trailing comma.
- Модели: `@property` на каждое поле с комментарием по-русски, `$table` со схемой (`'parking.tariffs'`; таблица в схеме `public` — без префикса, `'organizations'`: как у соседей `super_app`; с префиксом `public.` не работают тесты на временной схеме — AINA-2978), `$fillable`, `$casts` (enum-поля кастуются в enum).

## Форма кода

- Ранний выход: `if (! $item) { throw new NotFoundAppException('…'); }` — сразу, основной сценарий без вложенности.
- Пустая строка перед `return`/`throw`/`try`/`break`/`continue` и после блоков `if`/`foreach` (cs-fixer `ErickSkrauch/line_break_after_statements`).
- `! $value` — с пробелом; конкатенация `$a . $b` — с пробелами; короткие массивы; одинарные кавычки.
- `=` и `=>` не выравниваются по колонке.
- Многострочные аргументы/параметры/массивы — каждый элемент на своей строке, trailing comma.
- Fluent-цепочка из 3+ вызовов — по вызову на строку.
- Импорты по алфавиту, без неиспользуемых; `use OpenApi\Attributes as OA;`.

## Имена

| Что | Как | Пример |
|---|---|---|
| DTO | `{Entity}Dto` | `TariffDto` |
| Сервис CRUD | `{Entity}CrudService` + `Interface` | `TariffCrudService` |
| Сервис с логикой | `{Entity}Service` + `Interface` | `SessionService` |
| Command / Query (в домене) | `{Action}Command` / `{Action}Query`, метод `execute()` | `ActivatePlannedTariffCommand` |
| Workflow (междоменный) | `{Action}Workflow`, метод `execute()` | `CreateTariffWorkflow` |
| Коллекция DTO | `{Entity}ItemsDto` (`DataObjectCollection`, `mps-core/data-objects.md`) | `PermissionItemsDto` |
| Валидатор | `{Entity}DtoValidator` | `TariffDtoValidator` |
| Enum | `{Entity}{Признак}Enum`, кейсы `UPPER_SNAKE` (P-16, как в ядре: `CurrencyEnum::KZT`); всегда `implements Enumerable` + `use EnumerableTrait` из `mps/core` (`mps-core/enums.md`) | `TariffStatusEnum::ACTIVE` |
| Request | `Http/Requests/{Entity}/{Create,Update,Index}Request` | `Requests/Tariffs/CreateRequest` |
| Response | `{Entity}Response`, `{Entity}PaginatedResponse` | `TariffResponse` |
| Job | `{Action}Job` | `ActivatePlannedTariffJob` |
| Метод поиска в репозитории | `get…By…` (один) / `all…By…` (много) | `getDefaultTariffsByParkingId` |

Имена DTO и Request — как в `parking_app` (решения P-5, P-7): `{Entity}Dto`, `Http/Requests/{Entity}/{Create,Update,Index}Request`. Суффикс в PHP — `Dto`, в Go — `DTO` (аббревиатуры заглавными) — различие стеков. Варианты `super_app` (`BuildingDataDTO`, `Requests/CreateRequest` без папки сущности) в новом коде — **[ВНИМАНИЕ]**. Междоменный класс — `{Action}Workflow` с `execute()` (решение P-2); `*Command` в `Workflows/` parking_app — старый нейминг, переименовывается только в рамках задачи.

## Язык

Docblock, сообщения исключений для клиента, OpenAPI `summary`/`description` — по-русски. Идентификаторы, логи — по-английски.
