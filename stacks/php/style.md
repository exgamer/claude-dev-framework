# Стиль кода (PHP)

Уточняет `core/style.md`. Форматирование проверяется `php-cs-fixer.php` проекта (`composer csfix-validate`) — его правила главнее примеров ниже.

---

## Шапка класса

- `declare(strict_types=1);` в каждом файле.
- Docblock класса: одна строка по-русски (язык — `core/style.md`, «Язык»), что это, + `@author` автора; строка говорит больше имени — правило, границу или участников, а не пересказ (`comment-restates-name`); эталон — `examples/parking_app/Domains/Billing/Modules/Tariffs/Commands/SetDefaultTariffCommand.php`:
  ```php
  // плохо: пересказ имени SetDefaultTariffCommand
  /**
   * Команда установки тарифа по умолчанию.
   */

  // хорошо
  /**
   * Назначение тарифа по умолчанию: у парковки он один, флаг прежнего снимается.
   *
   * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
   */
  ```
  Для workflow — так же одна строка о назначении сценария (что делает, с какими участниками), без объяснения архитектуры и связей (`core/style.md`, «Не пишутся»); эталон — `examples/parking_app/Workflows/Billing/Tariffs/CreateTariffWorkflow.php`:
  ```php
  /**
   * Создание тарифа парковки: лимит цены реестра, существование парковки (Catalog), уведомление партнёрского биллинга.
   *
   * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
   */
  ```
- Шаблонные docblock-и вида `@package App\…`, «Реализует бизнес-логику управления…», «Обеспечивает типобезопасность…» (встречается в существующем коде) — `comment-restates-name` / `ai-style-comment`, это пересказ, а не смысл.
- `@param`/`@return` в docblock — только если добавляют информацию сверх сигнатуры (`array<int, string[]>`, generic, `@throws`). Дублировать `@param int $id` при типизированной сигнатуре не нужно.
- Что писать в комментариях и docblock, чего не писать, уровни и теги — `core/style.md`, «Комментарии». PHP-пример про описание, повторяющее имя (`comment-restates-name`):

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
- Модели: `@property` на каждое поле, описание по-русски — у неочевидных (единицы, формат, смысл `null`; `core/style.md`, «Комментарии» и «Язык»), `$table` — схема как в проекте: таблица в своей схеме — со схемой (в эталоне `'parking.tariffs'`); в `public` — без префикса (`'organizations'`): префикс `public.` обходит подмену схемы в тестах на временной схеме (AINA-2978), `$fillable`, `$casts` (enum-поля кастуются в enum).

## Форма кода

- Ранний выход вместо `else` (`core/style.md`, «Форма кода»; `else-after-return`): `if (! $item) { throw new NotFoundAppException('…'); }` — сразу, основной сценарий без вложенности; ветка завершилась `return`/`throw`/`continue` — `else`/`elseif` после неё не пишется; выбор по значению — `match`.

  ```php
  // плохо
  if ($session === null) {
      throw new NotFoundAppException('сессия не найдена');
  } else {
      if ($session->isClosed()) {
          return $session;
      } else {
          $this->close($session);

          return $session;
      }
  }

  // хорошо
  if ($session === null) {
      throw new NotFoundAppException('сессия не найдена');
  }

  if ($session->isClosed()) {
      return $session;
  }

  $this->close($session);

  return $session;
  ```
- Читается сверху вниз (`core/style.md`, «Форма кода»; `nested-call-chain`): замыкание не передаёт поведение в свой метод, вызов не вкладывается в вызов, лямбда — одна строка только там, где её требует библиотека, параметр замыкания не затеняет внешнюю переменную.

  ```php
  // плохо: поведение уезжает в хелпер, два уровня скобок, $query затеняет $query
  $query = $this->getQuery()
      ->where('orders.user_id', $userId)
      ->whereExists($this->service(
          static fn (Builder $query): Builder => $query->whereNotIn('services.type', self::QUANTITY_BASED_SERVICE_TYPES),
      ));

  // хорошо: подзапрос написан на месте, замыкание — только там, где Laravel без него не умеет
  $query = $this->getQuery()
      ->where('orders.user_id', $userId)
      ->whereExists(static function (Builder $service): void {
          $service->selectRaw('1')
              ->from('services')
              ->whereColumn('services.id', 'orders.service_id')
              ->whereNull('services.deleted_at')
              ->whereNotIn('services.type', self::QUANTITY_BASED_SERVICE_TYPES);
      });

  // плохо: вызов в вызове и индекс по результату
  return $this->buildSettingCardsWorkflow->execute([$this->settingService->getByKey($key)])[0];

  // хорошо: последовательно
  $setting = $this->settingService->getByKey($key);
  $cards = $this->buildSettingCardsWorkflow->execute([$setting]);

  return $cards[0];

  // плохо: четыре вызова один в другом
  $ids = array_values(array_unique(array_filter(array_map(fn ($s) => $s->buildingId, $settings))));

  // хорошо
  $buildingIds = [];
  foreach ($settings as $setting) {
      if ($setting->buildingId !== null) {
          $buildingIds[$setting->buildingId] = $setting->buildingId;
      }
  }
  $buildingIds = array_values($buildingIds);
  ```

  Один уровень с коротким аргументом — допустим; вызов внутри аргумента или индекс по результату — разворачивать:

  ```php
  // допустимо: аргумент — один вызов маппера/хелпера от входных данных
  $this->requestOtpWorkflow->execute(PhoneRequestMapper::toDto($request));
  $paginator = ContractPagination::restore($items, Mapper::perPage($request), $request->page());

  // плохо: вызов в аргументе и индекс по результату
  return $this->withMedia([$this->repository->findById($id)])[0];

  // хорошо
  $entity = $this->repository->findById($id);
  $entities = $this->withMedia([$entity]);

  return $entities[0];
  ```
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
| Command / Query (в домене) | `{Action}Command` / `{Action}Query`, `final` без базового класса ядра, метод `execute(...)` (`mps-core/commands.md`, «Форма Command и Query») | `ActivatePlannedTariffCommand` |
| Workflow (междоменный) | `{Action}Workflow`, метод `execute()` | `CreateTariffWorkflow` |
| Коллекция DTO | `{Entity}ItemsDto` (`DataObjectCollection`, `mps-core/data-objects.md`) | `PermissionItemsDto` |
| Валидатор | `{Entity}DtoValidator` | `TariffDtoValidator` |
| Enum | `{Entity}{Признак}Enum`, кейсы `UPPER_SNAKE` (P-16, как в ядре: `CurrencyEnum::KZT`); всегда `implements Enumerable` + `use EnumerableTrait` из `mps/core` (`mps-core/enums.md`) | `TariffStatusEnum::ACTIVE` |
| Request | `Http/Requests/{Entity}/{Create,Update,Index}Request`; действие над записью по id пути — `{View,{Action}}Request` (`structure.md`, дерево `Entrypoints/`) | `Requests/Tariffs/CreateRequest`, `Requests/Tariffs/SetDefaultRequest` |
| Response | `{Entity}Response`, `{Entity}PaginatedResponse` | `TariffResponse` |
| Job | `{Action}Job` | `ActivatePlannedTariffJob` |
| Метод репозитория | один — `get…By…`/`oneBy…` → `?Model`; много — `all…By…` → `Collection`; список — `paginate…({E}FilterDto, Page)`/`list…` (форма (2) P-19; в форме (1) — `search()` ядра); запись — `createFromDto`/`updateFromDto`/типизированный метод (п. 11a); `*OrFail` — нет (п. 12a) | `getDefaultByParkingId`, `allByParkingId`, `paginateByFilter` |
| Публичный метод сервиса для точки входа | `findById(int $id, ?array $scope): ?Model` — `null` переводит в 404 контроллер (п. 12a) | `findById` |

Имена DTO и Request — как в эталоне `stacks/php/examples/parking_app` (решения P-5, P-7): `{Entity}Dto`, `Http/Requests/{Entity}/{Create,Update,Index}Request`, по id пути — `{View,{Action}}Request`. Суффикс в PHP — `Dto`, в Go — `DTO` (аббревиатуры заглавными) — различие стеков. В новом коде суффикс `DTO` (`BuildingDataDTO`) — `dto-suffix-uppercase`; `Requests/CreateRequest` без папки сущности — `file-wrong-location`. Междоменный класс — `{Action}Workflow` с `execute()` (решение P-2).

## Язык

Docblock, сообщения исключений для клиента, OpenAPI `summary`/`description` — по-русски (исключение для комментариев — `core/style.md`, «Язык»). Идентификаторы, логи — по-английски. Как писать любой из этих текстов (по делу, без канцелярита и «ИИ-воды») — `core/style.md`, «Любой текст для людей».
