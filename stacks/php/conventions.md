# Правила написания кода (PHP)

Обязательные правила при написании и ревью. Как пользоваться конкретными классами ядра — `mps-core/*.md`; замечания из реальных MR — `review-findings.md`.

---

## Слои

1. **Контроллер тонкий** — Request → `{E}Dto::make()->fromArray($request->validated())` → сервис/workflow → `Response`. Никаких запросов к БД, ветвлений по бизнес-правилам, сборки массивов.
2. **Бизнес-логика — в сервисе или workflow** (контракт репозитория и правило «хранилища только через репозитории» — `architecture/layers.md`, «Правила слоёв»). Модель и репозиторий бизнес-логики не содержат: репозиторий = SELECT/INSERT/UPDATE/DELETE и фильтры поиска. Обратное тоже верно: **запросы к БД — только в репозитории** (`$this->getQuery()`); сервис, Command/Query и workflow ходят в БД исключительно через интерфейс репозитория — `DB::`, `Model::where`/`query()`/`find`, `->get()` по модели в них — **[ОШИБКА]**, тег `service-direct-db-query`. Workflow берёт репозитории нескольких доменов (п. 3), а не пишет запрос сам «потому что данные из двух таблиц»: нужен метод — он добавляется в репозиторий того домена, чья таблица.
2a. **Запрос виден целиком** (**[ВНИМАНИЕ]**, тег `query-split-into-pieces`; правило, лимиты и «почему» — `architecture/layers.md`, «Правила слоёв»; здесь — Laravel). Порядок в публичном методе: `getQuery()` → условия → подзапросы на месте → сортировка → `select` → маппер. Кусок запроса в Laravel — приватный метод, возвращающий или дополняющий Builder (`service(Closure)`, `activeQuery()`, `ownQuery($userId)`, `withBuilding($query)`); таких нет.
    - **Список — как в Go (решение P-19):** явный метод репозитория `paginate{Что}({E}FilterDto $filter, Page $page): LengthAwarePaginator` (или `list…(): list<{E}Entity>`), фильтры применяются в нём подряд: `if ($filter->getServiceId() !== null) { $query->where('orders.service_id', $filter->getServiceId()); }`. `CRUDRepository::search()`, хук `filterSearch()`, `SearchDataObject` с массивом `params`, `PaginatedQueryHelper` над `search()` в новом коде **не используются** — это форма ядра, оставленная легаси и `parking_app`/`super_app`; их `QueryFilters V2` нужны только ей (`mps-core/repository.md`). Типизированный фильтр-DTO делает проверку значений ненужной: нет-строка в `StringFilter` не «пропадает», а не проходит Request.
    - Подзапрос (лимит 2–3 метода) в Laravel — `whereExists`/`whereNotExists`, `selectSub`, `fromSub`, `joinSub`, подзапрос в `whereIn`. `join` и простые `where` (владелец, активность по датам, получатель и канал, `withTrashed()`) повторяются в каждом методе без лимита — в приватный метод, scope или константу не выносятся.

      ```php
      // плохо: условие владельца вынесено ради экономии строк
      private function ownQuery(int $userId): Builder
      {
          return $this->getQuery()->withTrashed()->where('user_id', $userId);
      }

      // хорошо: условие повторено в каждом методе, SQL виден целиком
      $query = $this->getQuery()
          ->withTrashed()
          ->where('community_cards.user_id', $userId)
          ->where('community_cards.id', $id);
      ```
    - Большой запрос (ориентир ~40 строк, 3+ подзапроса по чужим модулям) — несколько методов репозиториев, каждый в своём модуле; сервис/workflow вызывает их через интерфейсы и собирает **готовые данные** пачкой по id (`layers.md`, п. 3). Запросов в сервисе/workflow при этом нет — ни `DB::`, ни `getQuery()`, ни Builder, возвращённый из репозитория (п. 2, `service-direct-db-query`, **[ОШИБКА]**). Подзапрос `EXISTS`/`JOIN` по чужой таблице внутри метода репозитория — только на чтение и с комментарием, чья таблица (`cross-domain.md`); предпочтение — метод репозитория того модуля.
    - **Констант колонок нет** (`private const COLUMNS = [...]`): список — явно в каждом `select`/`paginate` (тот же тег). Константы значений (`QUANTITY_BASED_SERVICE_TYPES`, имена статусов) остаются.
    - Scope на моделях (`scopeActive`) не используются — запрос живёт только в репозитории (`mps-core/model.md`).
2b. **Метод репозитория — один запрос** (**[ОШИБКА]**, тег `repository-multi-query`; правило и «почему» — `architecture/layers.md`, «Правила слоёв»; здесь — Laravel). В публичном методе одна завершающая операция: `get`/`first`/`pluck`/`exists`/`count`/`paginate`/`update`/`delete`/`insert`/`upsert`/`save`. Две подряд — разделить на два метода, а порядок и `TransactionManagerInterface::run()` — в сервис, Command или workflow.

    ```php
    // плохо: блокировка и удаление в одном методе репозитория
    public function deleteForOwner(MediaOwnerDto $owner, string $collectionName): void
    {
        $lockedUuids = $this->getQuery()
            ->where('model_type', $owner->getModelType())
            ->where('model_id', $owner->getModelId())
            ->where('collection_name', $collectionName)
            ->lockForUpdate()
            ->pluck('uuid')
            ->all();

        $this->getQuery()
            ->whereIn('uuid', $lockedUuids)
            ->delete();
    }

    // хорошо: два метода репозитория, порядок и транзакция — в сервисе
    public function lockUuidsForOwner(MediaOwnerDto $owner, string $collectionName): array { /* один SELECT … FOR UPDATE */ }

    public function deleteByUuids(array $uuids): int { /* один DELETE */ }

    // сервис
    $this->transactionManager->run(function () use ($owner, $collectionName): void {
        $uuids = $this->repository->lockUuidsForOwner($owner, $collectionName);
        $this->repository->deleteByUuids($uuids);
    });
    ```

    Обновление — `$this->getQuery()->where('id', $id)->update($columns)`: Eloquent Builder сам ставит `updated_at`, возвращает число строк. Не `first()` → `fill()` → `save()`: это два запроса.
3. **Service, Command/Query, Workflow — разные вещи** (`architecture/layers.md`). Workflow берёт репозитории и сервисы любых доменов напрямую; Command/Query/Service — только своего домена (O-7). Операция слишком большая для сервиса → `Commands/{Action}Command` (изменение) / `Queries/{Action}Query` (чтение) в том же модуле домена. Нужен другой домен → workflow в `Workflows/`. Пример workflow: создание тарифа проверяет парковку (Catalog) и вызывает `TariffCrudService` (Billing) — Billing-сервис о Catalog не знает. В `parking_app` такие классы пока называются `*Command` и лежат в `Workflows/` — это старый нейминг, не образец.
4. **Репозиторий возвращает модель.** `{E}RepositoryInterface` может возвращать Eloquent-модель `Infrastructure/Postgres/{D}/{M}/Models/{E}` (решение P-4) — сервисы и workflow работают с ней. Модель не уходит в ответ напрямую: только через `{E}Response`.
5. **Интерфейсы:** `RepositoryInterface` — всегда; `ServiceInterface` — всегда; Command/Query — без интерфейса, пока его не подменяют в тестах и не инжектируют через границу домена.
6. **Зависимости — через конструктор**, `private readonly`, тип — интерфейс. `app()`/`resolve()` внутри бизнес-кода запрещены.
7. **DI — только через `definitions.php`** (домен — `Domains/{D}/definitions.php`, репозитории — `Infrastructure/Postgres/{D}/Providers/definitions.php`). `$this->app->bind()` в провайдерах — только для подпроектных портов с переопределением извне (как `ParkingAccessResolverInterface`). Сервис со списком реализаций (фабрика драйверов) — значение-замыкание, внутри которого собирается список: `AppHelper` вызывает callable при загрузке, поэтому `fn () => new Factory(new ZipDriver())` создаст объекты сразу — нужен вложенный `fn () => fn () => …` или фабрика через `get()` (AINA-2744).
8. **Контексты не пересекаются.** Логика для Admin и Mobile отличается → отдельный `{Context}{Entity}Service` (при необходимости и репозиторий) со своим интерфейсом **в том же модуле домена** (раздел «Логика, отличающаяся по контексту», P-13), не в `Entrypoints/`; `if ($isAdmin)` и «универсальный» сервис с флагами запрещены (регламент проекта, п. 5).

## Логика, отличающаяся по контексту

- Если для разных контекстов (Admin, Mobile…) логика различается, это отдельные классы в **том же модуле домена**, с контекстом в имени (решение P-13): `Services/{Context}{Entity}Service.php` + `{Context}{Entity}ServiceInterface.php`. Если отличается выборка, так же называется репозиторий: `{Context}{Entity}RepositoryInterface` и реализация в Infrastructure.
- Общий сервис модуля не меняется. Контекстный может его использовать через интерфейс, но не наследует и не переопределяет (регламент проекта, п. 5).
- Сервисы и репозитории в `Entrypoints/` — **[ОШИБКА]**: точка входа только вызывает нужный контекстный сервис.
- Подпапки контекстов внутри модуля (`Admin/Services/…`) не заводятся. Если у модуля набирается 3+ класса для одного контекста, это повод для нового решения: строка в `rules-log.md` задачи.

## Модели и внешние системы

- **Eloquent-связей в моделях нет** (`BelongsTo`, `HasMany`…): связанные данные собираются через репозитории (тег `model-has-eloquent-relations`). Так во всех моделях `parking_app` и `super_app`.
- **Параметра `expanded` нет** (тег `expanded-param-used`): состав ответа фиксирован эндпойнтом; связанные данные сервис дочитывает всегда методом своего репозитория пачкой по id, другой домен — workflow, другой состав — отдельный эндпойнт (`mps-core/service.md`).
- **Клиент внешней системы — `{System}{Purpose}Gateway`** в `Infrastructure/Http/…` (`LpmIntercomGateway`, `LpmCameraGateway`), не `*Service` (тег `gateway-named-as-service`). В Go тот же слой называется `Repository` (`stacks/go/conventions.md`, п. 14) — различие стеков, не ошибка.
- Конфиг Gateway — через конструктор; отсутствует обязательный — `InvalidConfigurationAppException`, не молчаливый дефолт.

## Данные и валидация

9. **Форма запроса — в FormRequest** (`rules()`): типы, обязательность, `gt:0` для id, `max:` для строк/массивов, `Rule::enum()`. Без проверки доступа (правило «здание доступно админу» и т. п. — в middleware, п. 19a): FormRequest отвечает на «данные в правильном формате?», а не «можно ли этому пользователю?».
9a. **В Request нет SQL-запросов** (решение P-17, **[ОШИБКА]**, тег `request-queries-db`). Запрещены правила, которые ходят в БД: `exists:`, `unique:`, `Rule::exists()`, `Rule::unique()`, свои `Rule`/замыкания с `Model::`/`DB::`/репозиторием/сервисом. То же в `prepareForValidation()`, `withValidator()`, `after()`. Причины: валидация зависит от схемы БД и обходит репозиторий и домен; при вызове из консоли или очереди проверки нет; между проверкой и записью данные могут измениться. Куда переносить:
   - **существование** связанной записи — сервис/workflow через репозиторий: `NotFoundAppException` или `ValidationAppException` с ошибкой по полю (как `CreateTariffWorkflow`);
   - **уникальность** — уникальный индекс в БД (защищает от гонки, `approaches/patterns/concurrency.md`) плюс проверка в сервисе через репозиторий для понятной ошибки (валидатор в БД не ходит, п. 10);
   - **принадлежность** записи пользователю — скоуп в запросе репозитория (`security.md`).
10. **Доменные инварианты — в `{E}DtoValidator::validate($dto): array`** (решение P-8; inline-проверки в сервисе/workflow, как в части `super_app`, — **[ВНИМАНИЕ]**) (статический, `final`, `Domains/{D}/Modules/{M}/Validators/`; не сервис — без зависимостей и БД, в DI не регистрируется, `architecture/layers.md`), сервис бросает `ValidationAppException('VALIDATION ERROR', $errors)`. Формат ошибок — `['field' => ['сообщение']]`.
11. **Между слоями — DTO, не массив.** `DataObject` с private-свойствами, геттерами и fluent-сеттерами (`return $this`). В репозиторий уходит DTO (п. 11a).
11a. **Данные на запись — DTO по всей цепочке, не массив** (решение P-18, **[ОШИБКА]**, тег `write-array-instead-of-dto`). Контроллер → сервис/workflow: `{E}Dto::make()->fromArray($request->validated())` (или фабрика DTO), не `$request->validated()`/`->all()`/массив аргументом. Сервис/Command/workflow → репозиторий: DTO, ни `$dto->toArray…()`, ни литерал `['col' => $v]` в вызове, ни `create()`/`update()`/`updateById()` ядра с массивом. Методы записи репозитория — с DTO (`createFromDto({E}Dto $dto)`, `updateFromDto({E} $model, {E}Dto $dto)` — модель, которую вызывающий уже нашёл и проверил (п. 12a: репозиторий «не найдено» не бросает); имена `create`/`update` заняты ядром с `array`); DTO → колонки — внутри репозитория явным списком, модель с casts — `fill()->save()` (`mps-core/rules.md`, «Запись»). Значения, которые решает сервис (статус при создании), — типизированными аргументами рядом с DTO; одно-два поля (флаг) — отдельный типизированный метод (`setDefault(int $id, bool $isDefault)`). Почему: массив обходит `$fillable` и casts, состав данных не виден в сигнатуре (AINA-2978). Эталон — `TariffController` → `TariffCrudService` → `TariffRepository`.

    ```php
    // плохо
    $this->service->update($id, $request->validated());
    $this->repository->updateById($id, $dto->toArrayWithSnakeKeys());
    $this->repository->updateById($tariff->id, ['is_default' => true]);

    // хорошо
    $this->service->update($id, TariffDto::make()->fromArray($request->validated()));
    $this->repository->updateFromDto($tariff, $dto);
    $this->repository->setDefault($tariff->id, true);
    ```
11b. **Элемент массива — только после проверки ключа** (**[ОШИБКА]**, тег `array-key-unchecked`). `$arr['key']` и `$arr[0]` по массиву, который пришёл не из этой же функции, — классический `Undefined array key`: PHP 8 даёт Warning и `null`, дальше `null` уезжает в типизированный параметр — `TypeError` и 500 на проде, а локально работало, потому что ключ был. Чей массив не доверенный: `json_decode` ответа внешнего API, payload очереди (`Job::handle(..., array $payload)`), `config()`/`env`, `$request->all()`/`input()`, строки CSV/Excel, результат `explode()`/`preg_match()`, `->toArray()` чужого DTO, `keyBy()`-карта из репозитория (`$byId[$id]` — id могло не найтись).

    Как правильно, по убыванию предпочтения:
    - **DTO вместо массива**: `{E}Dto::make()->fromArray($payload)` + `{E}DtoValidator` — проверка ключей одна и в одном месте, дальше по коду типизированные геттеры (п. 11, 11a). Для payload очереди и ответов интеграций — так же, DTO интеграции в `Infrastructure/{Type}/{System}/DTO/`.
    - Обязательный ключ: проверить и **бросить осмысленное исключение** — `InvalidConfigurationAppException` для конфига, `BadRequestAppException`/`ValidationAppException` с именем поля для входа, `UnprocessableAppException` для payload очереди. Не `?? ''`/`?? 0` молча (`review-findings.md`, «Ошибки»): дефолт скрывает, что данных нет.
    - Необязательный ключ: `$arr['key'] ?? null` и явная обработка `null` следующей строкой. `null` — допустимое значение → `array_key_exists`, не `isset` (тег `request-missing-vs-null-not-distinguished`).
    - Разбор строк: `explode()` — сначала `count()`/`str_contains`, или `explode(':', $s, 2) + [1 => null]`; `preg_match` — проверить результат (`=== 1`) до обращения к `$m[1]`; первый/последний элемент — `$arr[0] ?? null`, `array_key_first()`, `$collection->first()`, не `$arr[count($arr) - 1]`.
    - Карта по id: `$byId[$id] ?? throw new NotFoundAppException(...)` или `isset($byId[$id])` с ветвлением.

    Проверка не нужна для массива, собранного здесь же (`$row = ['id' => …]; $row['id']`), для `$request->validated()` по ключу, который есть в `rules()` как `required`/`present`, и для `match`/`array_map` по уже проверенным данным. PHPStan ловит часть (`offsetAccess.notFound` на известной форме массива) — остальное смотрит ревью.

    ```php
    // плохо
    $phone = $payload['user']['phone'];                // Undefined array key → null → TypeError в сервисе
    [$prefix, $number] = explode(':', $raw);           // нет ':' → Warning, $number = null
    $tariff = $tariffsById[$session->tariff_id];       // тариф удалён — Warning

    // хорошо
    $dto = QueueUserDto::make()->fromArray($payload);  // ключи проверяет fromArray + валидатор
    if (! str_contains($raw, ':')) {
        throw new BadRequestAppException('ожидался формат prefix:number');
    }
    [$prefix, $number] = explode(':', $raw, 2);
    $tariff = $tariffsById[$session->tariff_id] ?? throw new NotFoundAppException('тариф сессии не найден');
    ```

12. **PUT — полные валидные данные** (O-10, **[ОШИБКА]**). В `UpdateRequest` каждое поле `required`, а очищаемое — `present|nullable`; сервис пишет DTO целиком (`updateFromDto`, п. 11a). `DataObject` отдаёт `null` и за неприсланные свойства, поэтому необязательное поле в `UpdateRequest` (`nullable`/`sometimes` без `present`) при записи DTO целиком затирает данные `null`. Обходы — список пришедших полей рядом с DTO, отслеживание пришедших полей в DTO, `array_filter(!is_null)` — не применять.

    ```php
    // плохо: запрос {name} обнулит currency и grace_minutes
    'currency' => ['nullable', Rule::enum(CurrencyEnum::class)],

    // хорошо
    'currency' => ['present', 'nullable', Rule::enum(CurrencyEnum::class)],
    ```

12a. **Без методов-обёрток над одной проверкой или одним вызовом** (**[ВНИМАНИЕ]**). `findByIdOrFail()` и любые `*OrFail` (в сервисе и в репозитории — нарушение SRP: поиск и реакция на отсутствие — разные ответственности; ядровые `oneByIdOrFail`/`getByUuidOrFail` тоже не вызываются — `oneById` + явная проверка), `assertValid()`, `getById()`, который только вызывает метод своего репозитория **для внутреннего использования**, — не заводятся (публичный метод сервиса, через который точка входа получает запись, — `findById(int $id): ?Model` — допустим: контроллеру репозиторий недоступен; `null` → `NotFoundAppException` бросает контроллер, `mps-core/exceptions.md`): в месте использования — `get`, возвращающий `?Model`, и явная проверка с исключением; вызов валидатора — там, где нужен; внутри сервиса — репозиторий напрямую, workflow берёт репозиторий напрямую (O-7).

    ```php
    // плохо
    $dictionary = $this->service->findByIdOrFail($id, $buildingIds);
    $this->assertValid($dto);

    // хорошо
    $dictionary = $this->repository->getByIdInBuildings($id, $buildingIds);
    if (! $dictionary) {
        throw new NotFoundAppException('Запись справочника 1С не найдена');
    }

    $errors = OneCDictionaryDtoValidator::validate($dto);
    if ($errors) {
        throw new ValidationAppException('VALIDATION ERROR', $errors);
    }
    ```
12b. **Значения-ключи — enum, не константы** (**[ВНИМАНИЕ]**). Имена атрибутов и заголовков запроса, теги кэша, ключи параметров — кейсы enum (`RequestHeaderEnum::BUILDING_ID`, с `Enumerable`). Константы в интерфейсах (репозитория, сервиса) — **[ОШИБКА]**. **Значения домена** — типы, статусы, виды, провайдеры, действия (`'COWORKING'`, `'PAID'`, `'SUCCESS'`, `'dss'`), в том числе списки таких значений, — кейсы enum своего домена или `Common/` (`OrderServiceTypeEnum::COWORKING->value`, список — метод enum: `quantityBased()`), в любом слое, включая репозиторий и маппер; enum домена из инфраструктуры импортировать можно; enum чужого домена не импортируется — понятие двух и более доменов переезжает в `Common/` (`architecture/layers.md`, «Общий бизнес-словарь»). Значение, которое уже есть в enum, константой класса не повторяется — **[ОШИБКА]** (две копии одного значения расходятся при правке); значение домена без enum — сначала enum, потом использование. **Подмножество значений enum** («временные услуги», «состояния, которые можно оплатить») — метод самого enum (`ServiceTypeEnum::timeBased()`, `OrderStateEnum::payable()`), а не константа-список кейсов в другом классе (`private const array PAYABLE_STATES = [OrderStateEnum::PENDING, …]`) — **[ВНИМАНИЕ]**; то же подмножество уже есть методом enum или константой в другом месте — **[ОШИБКА]** (копия). Имя обычного поля/фильтра (`'building_id'`, `'name'`) — строкой, как остальные поля. Приватная константа класса допустима только для настройки, которую enum не выразит (число, массив, значение по умолчанию: `DEFAULT_SORT = ['-id']`, `SLOT_SEARCH_EXTRA_DAYS = 2`), и для **morph-типа легаси** (`model_type = 'App\Models\Organization'` — значение данных, а не ключ нового кода): `private const` в репозитории с комментарием-источником (`/** Morph-тип объекта в легаси-таблицах */`), как `CategoryRepository::BUILDING_MORPH_TYPE` в `super_app`, не enum (AINA-2978).
12c. **Список: сортировка явная** (**[ВНИМАНИЕ]**). Метод списка репозитория всегда заканчивается явным `orderBy` (по умолчанию — ключ или дата + `id` для стабильности): без `ORDER BY` порядок страниц в Postgres не гарантирован. Сортировка по выбору клиента — поле-enum в `{E}FilterDto` (`{E}SortEnum`, `Rule::enum()` в `IndexRequest`), `match` по нему в том же методе; параметр описан в Swagger. `$sortAttributes` ядра (`?sort=name,-id`) — только в старой форме `search()` (P-19).
12d. **Фильтры `IndexRequest` = поля `{E}FilterDto` = условия метода списка** (**[ВНИМАНИЕ]**). Каждое поле `IndexRequest` попадает в `{E}FilterDto` (маппер точки входа) и применяется в методе списка репозитория, и наоборот; поле, которое валидируется, но не фильтрует, — убрать (а не переносить из легаси «как было»). Тег `request-filter-not-applied`.

## Ошибки

13. **Только исключения ядра:** `NotFoundAppException`, `ValidationAppException`, `BadRequestAppException`, `AccessDeniedAppException`, `OperationFailedAppException`, `UnAuthorizeAppException`, `TooManyRequestsAppException`, `InvalidConfigurationAppException` (полный список и когда какое — `mps-core/exceptions.md`). `\Exception`, `\RuntimeException` — **[ОШИБКА]**; `new AppException(..., AppErrorTypeEnum::X)` при наличии готового класса — **[ВНИМАНИЕ]** (решение P-12). Технический компонент, которому нужны различимые категории ошибок (архивы, файлы, интеграция), заводит `final`-наследников **готовых** классов ядра (`ArchiveNotFoundException extends NotFoundAppException`) и общий маркер-интерфейс компонента; прямой наследник `AppException` — нет (AINA-2744). Валидатор, отдающий результат вместо исключений, ловит категории компонента и `\ErrorException` (warning под Laravel), но не `\Throwable` — ошибки кода не скрываются.
14. Сообщения для клиента — по-русски (`'Тариф не найден'`).
15. `@throws` у каждого метода, который бросает.

## Транзакции и побочные эффекты

16. **Транзакции — `TransactionManagerInterface::run()`**: workflow с записью — по умолчанию, Service/Command — при 2+ связанных записях. Не `DB::transaction`, не `$repo->transaction`. Правило — `approaches/patterns/transactions.md` (O-8), механика — `mps-core/transactions.md`.
17. **Побочные эффекты** (очередь, `Job::dispatch`, HTTP, файлы) — после фиксации записи и вне транзакции. Отложенное действие — `Job::dispatch(...)->delay(...)`, как `ActivatePlannedTariffJob`.

## HTTP и документация

18. Каждый эндпойнт описан OpenAPI-атрибутами: `#[OA\Get|Post|Put|Delete]` на методе контроллера, `#[OA\Schema]` на Request и Response, `#[OA\Parameter]` на `IndexRequest` для query-параметров. Тег — `'{Context} {Подпроект}: {Сущность}'`.
19. Ответ — `response()->json(new {E}Response($item), 201|200)`, удаление — `response()->json(null, 204)`, список — `response()->json(new {E}PaginatedResponse($page))` над `LengthAwarePaginator` сущностей (форма пагинации — одним классом подпроекта в `Core/Resources`, P-19; `PaginateResource::make($service->search($search))` — старая форма `search()`). Обёртку `success/data` добавляет `ApiResponseMiddleware`; Response-классы и контроллер обёртку **не собирают** — маршрут обязан стоять под `ApiResponseMiddleware` (в `superapp-api` алиас `superAppApi`); в Swagger-атрибутах метода контроллера `success`/`data` описываются, т.к. это фактический формат ответа. **Макросы и mixin на фасадах** (`Response::ok()`, `Response::created()`, `Response::deleted()` — `Response::mixin(...)`/`macro(...)` в провайдере проекта) — **[ОШИБКА]**: скрытая глобальная зависимость без `use`, существует только при загруженном провайдере, формат ответа спрятан вне точки входа. Только `response()->json(...)` с явным кодом (`Symfony\Component\HttpFoundation\Response::HTTP_*`).
19a. **Права и контекст запроса — в middleware, не в сервисе** (**[ОШИБКА]**). Роль/право на операцию — middleware на маршруте строковым алиасом (`->middleware('permission:…')`, `'role:…'`, алиасы — в `bootstrap/app.php`); `hasRole`/`hasPermission`/`$this->authorize()`/`Gate::` в контроллере, сервисе, Command, workflow — нет (`architecture/layers.md`, «Роли и права на операцию»). Middleware своего контекста (Admin, CRM, Mobile — у каждого свой, без `if` по гвардам) проверяет доступ (нет пользователя — 401, чужое здание/организация из заголовка **или из параметров запроса** — `building_id` в теле или фильтре — 403) и кладёт готовый контекст в `$request->attributes` — не в query/тело: клиент атрибуты не подменит, поля тела не перезаписываются. Контроллер читает контекст через статический `RequestHelper` подпроекта: методы `get*`, запрос — явным аргументом `Request $request` (как `RequestHelper::getCurrentCompanyId($request)` в catalog-service), например `RequestHelper::getBuildingIds($request)` и передаёт в сервис **как обычный фильтр**: в списке — полем `{E}FilterDto`, которое выставляет контроллер (`TariffFilterDto::make()->fromArray($request->validated())->setBuildingIds(RequestHelper::getBuildingIds($request))`), сервис получает только DTO (в старой форме `search()` — параметром `SearchDataObject`, P-19); в операциях по id — аргументом (`?array $buildingIds`, null — без ограничения, передаётся явно — `scope-param-defaults-unrestricted`). Сервис и репозиторий про права и пользователей не знают, фильтр применяется в запросе (`whereIn`), чужая запись по id — 404. `RequestHelper` без выставленного атрибута бросает исключение, а не возвращает null («нет middleware» ≠ «без ограничения»).

    ```php
    // плохо: сервис/контроллер сами вычисляют доступ
    $ids = $this->resolver->resolveForCurrentAdmin();
    $this->service->search($dto, accessibleBuildingIds: $ids);

    // хорошо: маршрут под ResolveAdminBuildings, в сервис — фильтр
    $this->service->search($dto, RequestHelper::getBuildingIds($request));
    ```
20. Маршруты: `Route::middleware([...])->prefix('{подпроект}/{context}')->as('{подпроект}.{context}.')->whereNumber('id')->group(...)`; имя — `{ресурс-во-мн.-числе}.{index|view|create|update|delete}`; URL в kebab-case, мн. число (`tariff-periods`). Middleware в маршрутах — **строковым алиасом** (`'resolve.admin.buildings'`, `'superAppApi'`), не `::class`: алиас регистрируется в `bootstrap/app.php` → `$middleware->alias([...])` рядом с остальными (так же зарегистрирован middleware `parking_app`); имя — в стиле существующих (`resolve.building`, `auth.admin.permission`).

## Пакеты ядра

- Ядро одно: `mps/core` + `mps/utils`, версия — из `composer.lock` проекта; версию у пользователя **не спрашивать** (в отличие от Go SDK, решение O-4). API сверять с исходниками в `vendor/mps/core` и `vendor/mps/utils` этого проекта, а не с примерами из `mps-core/*.md`.

21. Не писать то, что есть в `mps/core` / `mps/utils` (полный список — `mps-core/capabilities.md`) и в `Core/` подпроекта (базовые `Request`/`SearchRequest`, форма пагинации, `TransactionManagerInterface` при ядре < 1.10): `CRUDRepository`, `QueueManagerInterface` (RabbitMQ V2), `SmartCache`/`getOrSet`, `UploadManager`, логгер `for($this)`. `SearchDataObject`, `filterSearch()`, `QueryFilters\V2`, `PaginatedQueryHelper` — старая форма списка (P-19): в существующем коде на `search()` использовать их, в новом коде списка нет и их. Подробно — `mps-core/rules.md`.
22. Устаревшее API ядра (`RabbitManager::putIn`, `QueryFilters` V1, `FileManager::upload`, `setMessagePrefix`) в новом коде — **[ОШИБКА]**. `getOrCreate`/`getCacheKeyByParts` — устаревшие только с utils ≥ 2.6.4; при utils 2.6.x (superapp-api) другого API нет, это не находка (`mps-core/rules.md`, «Cache»).
