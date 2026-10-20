# Правила написания кода (PHP)

Обязательные правила при написании и ревью. Как пользоваться конкретными классами ядра — `mps-core/*.md`; замечания из реальных MR — `review-findings.md`.

---

## Слои

1. **Контроллер тонкий** — Request → `{E}Dto::make()->fromArray($request->validated())` → сервис/workflow → `Response`. Никаких запросов к БД, ветвлений по бизнес-правилам, сборки массивов.
2. **Бизнес-логика — в сервисе или workflow** (контракт репозитория и правило «хранилища только через репозитории» — `architecture/layers.md`, «Правила слоёв»). Модель и репозиторий бизнес-логики не содержат: репозиторий = SELECT/INSERT/UPDATE/DELETE и фильтры поиска. Обратное тоже верно: **запросы к БД — только в репозитории** (`$this->getQuery()`); сервис, Command/Query и workflow ходят в БД исключительно через интерфейс репозитория — `DB::`, `Model::where`/`query()`/`find`, `->get()` по модели в них — **[ОШИБКА]**, тег `service-direct-db-query`. Workflow берёт репозитории нескольких доменов (п. 3), а не пишет запрос сам «потому что данные из двух таблиц»: нужен метод — он добавляется в репозиторий того домена, чья таблица.
2a. **Запрос виден целиком** (**[ВНИМАНИЕ]**, тег `query-split-into-pieces`; правило, лимиты и «почему» — `architecture/layers.md`, «Правила слоёв»; здесь — Laravel). Порядок в публичном методе: `getQuery()` → условия → подзапросы на месте → сортировка → `select` → завершающая операция (`get`/`first`/`paginate`; при `list<{E}Entity>` — сборка сущности из строки). Кусок запроса в Laravel — приватный метод, возвращающий или дополняющий Builder (`service(Closure)`, `activeQuery()`, `ownQuery($userId)`, `withBuilding($query)`); таких нет.
    - **Форма пагинации списка — вопрос пользователю на задаче (решение P-19).** Две формы, обе допустимы; какую писать — решает пользователь. Вопрос — **только для нового списка**. Правка существующего списка (новый фильтр, поле, сортировка) — его форма сохраняется, без вопроса и без раздела «Список»; переписать существующий список на другую форму — только если это и есть задача (тогда — вопрос). Архитектор при задаче с новым списком выносит вопрос в «Открытые вопросы» `design.md` (оба варианта, рекомендация — (2)); ответ записывается в `docs/tasks/<ключ>/design.md`, раздел **«Список»**, один раз на задачу; дальше все роли пишут и проверяют по нему — не переопределяют и не переспрашивают. Раздела «Список» нет, а список новый (мелкая правка без архитектора, прямой запуск разработчика) — спросить пользователя, варианты и рекомендация (2); субагент — вопрос в итог, код списка не начинает. Почему вопрос: (1) — форма ядра, на ней написан существующий код; кроме ядра ей нужны три класса `Core/` подпроекта (`Requests/SearchRequest`, `Resources/PaginateResource`, `Database/Query/PaginatedQueryHelper`); (2) — запрос виден целиком и типизирован, но `Page`/`PaginatedResource` в ядре нет (живут в `Core/` подпроекта, P-22). Эталон `examples/parking_app` — на форме (2).
      - **(1) через `mps/core`:** репозиторий — `CRUDRepository::search(array $params, $id, int $perPage = 30, ?int $page)` + хук `filterSearch(Builder $query, array &$params): void` (фильтры — `MPS\Utils\Components\QueryFilters\V2`, `select` — явно), сервис — `search(SearchDataObject $dto)` (`SearchAwareInterface`; `setParams`, `setPerPage`, `setPage`), запрос — наследник `Core/Requests/SearchRequest` подпроекта (`page`, `per_page`, `sort` по `$sortAttributes`), ответ — `Core/Resources/PaginateResource` подпроекта над результатом `search()`, `Core/Database/Query/PaginatedQueryHelper` — над `search()`. API этих классов — по эталону: `examples/parking_app/Core/Requests/SearchRequest.php`, `examples/parking_app/Core/Resources/PaginateResource.php`, `examples/parking_app/Core/Database/Query/PaginatedQueryHelper.php`. Описание и пример — `mps-core/repository.md`, «Форма (1)».
      - **(2) через `Page`** (рекомендация — чище): явный метод репозитория `paginate{Что}({E}FilterDto $filter, Page $page): LengthAwarePaginator` (`Page` — `Core/Database/Query/Page` подпроекта, ответ — наследник `Core/Resources/PaginatedResource`; P-22 — тестовый вариант API `Page`) (или `list…(): list<{E}Entity>`), запрос собирается в нём целиком, фильтры подряд: `if ($filter->getServiceId() !== null) { $query->where('orders.service_id', $filter->getServiceId()); }`. `QueryFilters V2` нужны только форме (1). Типизированный фильтр-DTO делает проверку значений ненужной: нет-строка в `StringFilter` не «пропадает», а не проходит Request.
      - Новый список не по форме из «Список» `design.md` — **[ВНИМАНИЕ]** тег `list-form-not-as-designed`; существующий код на форме (1) — не нарушение и не переписывается, в т. ч. когда задача его правит (форма правимого списка сохраняется). В форме (1) фильтры в `filterSearch()` — часть контракта ядра, не «метод-кусок».
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
3. **Service, Command/Query, Workflow — разные вещи** (`architecture/layers.md`). Workflow берёт репозитории и сервисы любых доменов напрямую; Command/Query/Service — только своего домена (O-7). Операция слишком большая для сервиса → `Commands/{Action}Command` (изменение) / `Queries/{Action}Query` (чтение) в том же модуле домена — `final` класс без базового класса ядра, метод `execute(...)` с типизированными аргументами, зависимости конструктором, логгер — только если нужен (форма — `mps-core/commands.md`, «Форма Command и Query», тег `command-query-form`). Нужен другой домен → workflow в `Workflows/`. Пример workflow: создание тарифа проверяет парковку (Catalog) и вызывает `TariffCrudService` (Billing) — Billing-сервис о Catalog не знает. В существующем коде такие классы бывают названы `*Command` и лежат в `Workflows/` — это старый нейминг, не образец.
4. **Репозиторий возвращает модель.** `{E}RepositoryInterface` может возвращать Eloquent-модель `Infrastructure/Postgres/{D}/{M}/Models/{E}` (решение P-4) — сервисы и workflow работают с ней. Модель не уходит в ответ напрямую: только через `{E}Response`.
5. **Интерфейсы:** `RepositoryInterface` — всегда; `ServiceInterface` — всегда; Command/Query — без интерфейса, пока его не подменяют в тестах и не инжектируют через границу домена; базовые `MPS\Core\Commands\Command`/`MPS\Core\Queries\Query` ядра тоже не наследуются (п. 3).
6. **Зависимости — через конструктор**, `private readonly`, тип — интерфейс. `app()`/`resolve()` внутри бизнес-кода запрещены. Исключение — места без конструктора: миграции и замыкания `Artisan::command()` в `commands.php` (`mps-core/cli.md`); там `app(...)`.
7. **DI — только через `definitions.php`** (домен — `Domains/{D}/definitions.php`, репозитории — `Infrastructure/Postgres/{D}/Providers/definitions.php`). `$this->app->bind()` в провайдерах — только для подпроектных портов с переопределением извне (как `ParkingAccessResolverInterface`). Сервис со списком реализаций (фабрика драйверов) — значение-замыкание, внутри которого собирается список: `AppHelper` вызывает callable при загрузке, поэтому `fn () => new Factory(new ZipDriver())` создаст объекты сразу — нужен вложенный `fn () => fn () => …` или фабрика через `get()` (AINA-2744).
8. **Контексты не пересекаются.** Логика для Admin и Mobile отличается → отдельный `{Context}{Entity}Service` (при необходимости и репозиторий) со своим интерфейсом **в том же модуле домена** (раздел «Логика, отличающаяся по контексту», P-13), не в `Entrypoints/`; `if ($isAdmin)` и «универсальный» сервис с флагами запрещены (регламент проекта, п. 5).

## Логика, отличающаяся по контексту

- Если для разных контекстов (Admin, Mobile…) логика различается, это отдельные классы в **том же модуле домена**, с контекстом в имени (решение P-13): `Services/{Context}{Entity}Service.php` + `{Context}{Entity}ServiceInterface.php`. Если отличается выборка, так же называется репозиторий: `{Context}{Entity}RepositoryInterface` и реализация в Infrastructure.
- Общий сервис модуля не меняется. Контекстный может его использовать через интерфейс, но не наследует и не переопределяет (регламент проекта, п. 5).
- Сервисы и репозитории в `Entrypoints/` — **[ОШИБКА]**: точка входа только вызывает нужный контекстный сервис.
- Подпапки контекстов внутри модуля (`Admin/Services/…`) не заводятся. Если у модуля набирается 3+ класса для одного контекста, это повод для нового решения: строка в `rules-log.md` задачи.

## Модели и внешние системы

- **Eloquent-связей в моделях нет** (`BelongsTo`, `HasMany`…): связанные данные собираются через репозитории (тег `model-has-eloquent-relations`).
- **Параметра `expanded` нет** (тег `expanded-param-used`): состав ответа фиксирован эндпойнтом; связанные данные сервис дочитывает всегда методом своего репозитория пачкой по id, другой домен — workflow, другой состав — отдельный эндпойнт (`mps-core/service.md`).
- **Порт и адаптер внешней системы — по роли, не по поставщику** (`architecture/integrations.md`, «Имена порта и адаптера»): источник данных — `…RepositoryInterface` в домене (`PriceLimitRepositoryInterface`) и `{Provider}{Product}Repository` в `Infrastructure/Http/{Provider}/{Product}/`; отправка без данных в ответ — `…NotifierInterface`/`…PublisherInterface`/`…SenderInterface` и `{Provider}{Product}Notifier` (…). `*Gateway`, `*Service`, `*Client` и имя системы в имени порта — нет (тег `adapter-named-not-by-role`). Так же в Go (`stacks/go/conventions.md`, п. 14).
- Конфиг адаптера — через конструктор; отсутствует обязательный — `InvalidConfigurationAppException`, не молчаливый дефолт.

## Данные и валидация

9. **Форма запроса — в FormRequest** (`rules()`): типы, обязательность, `gt:0` для id, `max:` для строк/массивов, `Rule::enum()`. Без проверки доступа (правило «здание доступно админу» и т. п. — в middleware, п. 19a): FormRequest отвечает на «данные в правильном формате?», а не «можно ли этому пользователю?».
9a. **В Request нет SQL-запросов** (решение P-17, **[ОШИБКА]**, тег `request-queries-db`). Запрещены правила, которые ходят в БД: `exists:`, `unique:`, `Rule::exists()`, `Rule::unique()`, свои `Rule`/замыкания с `Model::`/`DB::`/репозиторием/сервисом. То же в `prepareForValidation()`, `withValidator()`, `after()`. Причины: валидация зависит от схемы БД и обходит репозиторий и домен; при вызове из консоли или очереди проверки нет; между проверкой и записью данные могут измениться. Куда переносить:
   - **существование** связанной записи — сервис/workflow через репозиторий: `NotFoundAppException` или `ValidationAppException` с ошибкой по полю (как `CreateTariffWorkflow`);
   - **уникальность** — уникальный индекс в БД (защищает от гонки, `approaches/patterns/concurrency.md`) плюс проверка в сервисе через репозиторий для понятной ошибки (валидатор в БД не ходит, п. 10);
   - **принадлежность** записи пользователю — скоуп в запросе репозитория (`security.md`; схема — `architecture/layers.md`, «Принадлежность записи скоупу — два случая»).
10. **Доменные инварианты — в `{E}DtoValidator::validate($dto): array`** (решение P-8; inline-проверки в сервисе/workflow — **[ВНИМАНИЕ]**) (статический, `final`, `Domains/{D}/Modules/{M}/Validators/`; не сервис — без зависимостей и БД, в DI не регистрируется, `architecture/layers.md`), сервис бросает `ValidationAppException('VALIDATION ERROR', $errors)`. Формат ошибок — `['field' => ['сообщение']]`.
11. **Между слоями — DTO, не массив.** `DataObject` с private-свойствами, геттерами и fluent-сеттерами (`return $this`). В репозиторий уходит DTO (п. 11a).
11a. **Данные на запись — DTO по всей цепочке, не массив** (решение P-18, **[ОШИБКА]**, тег `write-array-instead-of-dto`). Контроллер → сервис/workflow: `{E}Dto::make()->fromArray($request->validated())` (или фабрика DTO), не `$request->validated()`/`->all()`/массив аргументом. Сервис/Command/workflow → репозиторий: DTO, ни `$dto->toArray…()`, ни литерал `['col' => $v]` в вызове, ни `create()`/`update()`/`updateById()` ядра с массивом. Методы записи репозитория — с DTO (`createFromDto({E}Dto $dto)`, `updateFromDto({E} $model, {E}Dto $dto)` — модель, которую вызывающий уже нашёл и проверил (п. 12a: репозиторий «не найдено» не бросает); имена `create`/`update` заняты ядром с `array`); DTO → колонки — внутри репозитория явным списком, модель с casts — `fill()->save()` (`mps-core/rules.md`, «Запись»). Значения, которые решает сервис (статус при создании), — типизированными аргументами рядом с DTO; одно-два поля (флаг) — отдельный типизированный метод (`setDefault(int $id, bool $isDefault)`). Почему: массив обходит `$fillable` и casts, состав данных не виден в сигнатуре (AINA-2978). Эталон — `TariffController` → `TariffCrudService` → `TariffRepository`.

    ```php
    // плохо
    $this->service->update($id, RequestHelper::getParkingIds($request), $request->validated());
    $this->repository->updateById($id, $dto->toArrayWithSnakeKeys());
    $this->repository->updateById($tariff->id, ['is_default' => true]);

    // хорошо
    $dto = TariffDto::make()->fromArray($request->validated());
    $this->service->update($id, RequestHelper::getParkingIds($request), $dto);   // скоуп — аргументом (п. 19a)
    $this->repository->updateFromDto($tariff, $dto);
    $this->repository->setDefault($tariff->id, true);
    ```
11b. **Элемент массива — только после проверки ключа** (**[ОШИБКА]**, тег `array-key-unchecked`). `$arr['key']` и `$arr[0]` по массиву, который пришёл не из этой же функции, — классический `Undefined array key`: PHP 8 даёт Warning и `null`, дальше `null` уезжает в типизированный параметр — `TypeError` и 500 на проде, а локально работало, потому что ключ был. Чей массив не доверенный: `json_decode` ответа внешнего API, payload очереди (`Job::handle(..., array $payload)`), `config()`/`env`, `$request->all()`/`input()`, строки CSV/Excel, результат `explode()`/`preg_match()`, `->toArray()` чужого DTO, карта, собранная в сервисе по результату репозитория (`$byId[$id]` — id могло не найтись).

    Как правильно, по убыванию предпочтения:
    - **DTO вместо массива**: `{E}Dto::make()->fromArray($payload)` + `{E}DtoValidator` — проверка ключей одна и в одном месте, дальше по коду типизированные геттеры (п. 11, 11a). Для payload очереди и ответов интеграций — так же, DTO интеграции в `Infrastructure/{Type}/{System}/DTO/`.
    - Обязательный ключ: проверить и **бросить осмысленное исключение** — `InvalidConfigurationAppException` для конфига, `BadRequestAppException`/`ValidationAppException` с именем поля для входа. Payload очереди — исключение не бросается: это постоянная ошибка, лог/статус и выход (`mps-core/jobs.md`). Не `?? ''`/`?? 0` молча (`review-findings.md`, «Ошибки»): дефолт скрывает, что данных нет.
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
    // плохо: запрос без grace_minutes обнулит grace_minutes
    'grace_minutes' => ['nullable', 'integer', 'min:0', 'max:' . TimeUnitEnum::DAY->minutes()],

    // хорошо: очищаемое — present|nullable, остальные — required
    'grace_minutes' => ['present', 'nullable', 'integer', 'min:0', 'max:' . TimeUnitEnum::DAY->minutes()],
    'currency' => ['required', 'string', Rule::enum(CurrencyEnum::class)],
    ```

12a. **Без методов-обёрток над одной проверкой или одним вызовом** (**[ВНИМАНИЕ]**). `findByIdOrFail()` и любые `*OrFail` (в сервисе и в репозитории — нарушение SRP: поиск и реакция на отсутствие — разные ответственности; ядровые `oneByIdOrFail`/`getByUuidOrFail` тоже не вызываются — `oneById` + явная проверка), `assertValid()`, `getById()`, который только вызывает метод своего репозитория **для внутреннего использования**, — не заводятся (публичный метод сервиса, через который точка входа получает запись, — `findById` → `?Model` — допустим: контроллеру репозиторий недоступен; `null` → `NotFoundAppException` бросает контроллер, `mps-core/exceptions.md`; без скоупа — `findById(int $id)` — только для данных без владельца: справочники, общие настройки; данные с владельцем — `findById(int $id, ?array $parkingIds)` со скоупом, фильтр — в запросе репозитория, `architecture/layers.md`, «Принадлежность записи скоупу — два случая», п. 19a): в месте использования — `get`, возвращающий `?Model`, и явная проверка с исключением; вызов валидатора — там, где нужен; внутри сервиса — репозиторий напрямую, workflow берёт репозиторий напрямую (O-7).

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
12b. **Значения-ключи — enum, не константы** (**[ВНИМАНИЕ]**). Имена атрибутов и заголовков запроса, теги кэша, ключи параметров — кейсы enum (`RequestHeaderEnum::BUILDING_ID`, с `Enumerable`). Константы в интерфейсах (репозитория, сервиса) — **[ОШИБКА]**. **Значения домена** — типы, статусы, виды, провайдеры, действия (`'COWORKING'`, `'PAID'`, `'SUCCESS'`, `'dss'`), в том числе списки таких значений, — кейсы enum своего домена или `Common/` (`OrderServiceTypeEnum::COWORKING->value`, список — метод enum: `quantityBased()`), в любом слое, включая репозиторий и маппер; enum домена из инфраструктуры импортировать можно; enum чужого домена не импортируется — понятие двух и более доменов переезжает в `Common/` (`architecture/layers.md`, «Общий бизнес-словарь»). Значение, которое уже есть в enum, константой класса не повторяется — **[ОШИБКА]** (две копии одного значения расходятся при правке); значение домена без enum — сначала enum, потом использование. **Подмножество значений enum** («временные услуги», «состояния, которые можно оплатить») — метод самого enum (`ServiceTypeEnum::timeBased()`, `OrderStateEnum::payable()`), а не константа-список кейсов в другом классе (`private const array PAYABLE_STATES = [OrderStateEnum::PENDING, …]`) — **[ВНИМАНИЕ]**; то же подмножество уже есть методом enum или константой в другом месте — **[ОШИБКА]** (копия). Имя обычного поля/фильтра (`'building_id'`, `'name'`) — строкой, как остальные поля. Приватная константа класса допустима только для настройки, которую enum не выразит (число, массив, значение по умолчанию: `DEFAULT_SORT = ['-id']`, `SLOT_SEARCH_EXTRA_DAYS = 2`), для **технической константы** — имени индекса или ограничения БД, кода SQLSTATE, размера буфера (`TariffRepository::DEFAULT_TARIFF_UNIQUE_INDEX`, `UNIQUE_VIOLATION_CODE = '23505'`): это не значение домена и не ключ, enum не нужен, — и для **morph-типа легаси** (`model_type = 'App\Models\Organization'` — значение данных, а не ключ нового кода): `private const` в репозитории с комментарием-источником (`/** Morph-тип объекта в легаси-таблицах */`), например `CategoryRepository::BUILDING_MORPH_TYPE`, не enum (AINA-2978).
12c. **Список: сортировка явная** (**[ВНИМАНИЕ]**). Метод списка репозитория (форма (2), п. 2a) всегда заканчивается явным `orderBy` (по умолчанию — ключ или дата + `id` для стабильности): без `ORDER BY` порядок страниц в Postgres не гарантирован. Сортировка по выбору клиента — поле-enum в `{E}FilterDto` (`{E}SortEnum`, `Rule::enum()` в `IndexRequest`), `match` по нему в том же методе; параметр описан в Swagger. В форме (1) сортировка — `$sortAttributes` наследника `SearchRequest` (`?sort=name,-id`), разрешённые поля перечислены явно, сортировка по умолчанию — в `filterSearch()`.
12d. **Фильтры `IndexRequest` = поля `{E}FilterDto` = условия метода списка** (**[ВНИМАНИЕ]**). Каждое поле `IndexRequest` попадает в `{E}FilterDto` (маппер точки входа) и применяется в методе списка репозитория, и наоборот (в форме (1) п. 2a — поле `SearchRequest` → `params` → условие в `filterSearch()`); поле, которое валидируется, но не фильтрует, — убрать (а не переносить из легаси «как было»). Тег `request-filter-not-applied`.

## Ошибки

13. **Только исключения ядра:** `NotFoundAppException`, `ValidationAppException`, `BadRequestAppException`, `AccessDeniedAppException`, `OperationFailedAppException`, `UnAuthorizeAppException`, `TooManyRequestsAppException`, `InvalidConfigurationAppException` (полный список и когда какое — `mps-core/exceptions.md`). `\Exception`, `\RuntimeException` — **[ОШИБКА]**; `new AppException(..., AppErrorTypeEnum::X)` при наличии готового класса — **[ВНИМАНИЕ]** (решение P-12). Технический компонент, которому нужны различимые категории ошибок (архивы, файлы, интеграция), заводит `final`-наследников **готовых** классов ядра (`ArchiveNotFoundException extends NotFoundAppException`) и общий маркер-интерфейс компонента; прямой наследник `AppException` — нет (AINA-2744). Валидатор, отдающий результат вместо исключений, ловит категории компонента и `\ErrorException` (warning под Laravel), но не `\Throwable` — ошибки кода не скрываются.
14. Сообщения для клиента — по-русски (`'Тариф не найден'`).
15. `@throws` у каждого метода, который бросает.

## Транзакции и побочные эффекты

16. **Транзакции — `TransactionManagerInterface::run()`**: в workflow, Service и Command — когда сценарий делает больше одного модифицирующего запроса (в разные таблицы или в одну); один модифицирующий запрос — без своей транзакции (кроме транзакции ради блокировки `FOR UPDATE`/`FOR SHARE` — основание «Конкурентность» `design.md`); проверки перед записью в транзакцию не тянутся — гонку закрывает `approaches/patterns/concurrency.md`. Не `DB::transaction`, не `$repo->transaction`. Правило — `approaches/patterns/transactions.md` (O-8), механика — `mps-core/transactions.md`.
17. **Побочные эффекты** (очередь, `Job::dispatch`, HTTP, файлы) — после фиксации записи и вне транзакции. Отложенное действие — `Job::dispatch(...)->delay(...)`, как `ActivatePlannedTariffJob`.
17a. **Ошибка, которую намеренно не пробрасывают, — в `ExceptionHandler::report($e)`** (аналог Go `errorreporter.CaptureSoft`): запись уже сохранена, сбой побочного эффекта (отправка партнёру, сброс кэша) сценарий не отменяет. `Illuminate\Contracts\Debug\ExceptionHandler` — зависимостью через конструктор, `$this->exceptionHandler->report($e)` в `catch` конкретного исключения (`OperationFailedAppException`); исключение уходит в Sentry по правилам трекинга (`mps-core/exceptions.md`, «Sentry / трекинг»). Только `Log::warning`/`Log::error` или пустой `catch` в таком месте — **[ВНИМАНИЕ]**. Эталон — `examples/parking_app/Workflows/Billing/Tariffs/CreateTariffWorkflow.php`.

## HTTP и документация

18. Каждый эндпойнт описан OpenAPI-атрибутами: `#[OA\Get|Post|Put|Delete]` на методе контроллера, `#[OA\Schema]` на Request и Response, `#[OA\Parameter]` на `IndexRequest` для query-параметров. Тег — `'{Context} {Подпроект}: {Сущность}'`. В ответах — `500` у каждого; `404` — только у эндпойнтов, ищущих запись по ID (`view`/`update`/`delete` и действия над записью), не у `index`/`create`.
19. Ответ — `response()->json(new {E}Response($item), 201|200)`, удаление — `response()->json(null, 204)`, список — `response()->json(new {E}PaginatedResponse($page))` над `LengthAwarePaginator` сущностей (форма (2) п. 2a: форма пагинации — одним классом подпроекта в `Core/Resources`, P-22; в форме (1) — `PaginateResource::make($service->search($search))`). Обёртку `success/data` добавляет `ApiResponseMiddleware`; Response-классы и контроллер обёртку **не собирают** — маршрут обязан стоять под `ApiResponseMiddleware` (алиас — тот, под которым он зарегистрирован в проекте, например `api.response`); в Swagger-атрибутах метода контроллера `success`/`data` описываются, т.к. это фактический формат ответа. **Макросы и mixin на фасадах** (`Response::ok()`, `Response::created()`, `Response::deleted()` — `Response::mixin(...)`/`macro(...)` в провайдере проекта) — **[ОШИБКА]**: скрытая глобальная зависимость без `use`, существует только при загруженном провайдере, формат ответа спрятан вне точки входа. Только `response()->json(...)` с явным кодом (`Symfony\Component\HttpFoundation\Response::HTTP_*`).
19a. **Права и контекст запроса — в middleware, не в сервисе** (**[ОШИБКА]**). Роль/право на операцию — middleware на маршруте строковым алиасом (`->middleware('permission:…')`, `'role:…'`, алиасы — в `bootstrap/app.php`); `hasRole`/`hasPermission`/`$this->authorize()`/`Gate::` в контроллере, сервисе, Command, workflow — нет (`architecture/layers.md`, «Роли и права на операцию»). Middleware своего контекста (Admin, CRM, Mobile — у каждого свой, без `if` по гвардам) проверяет доступ (нет пользователя — 401, чужая парковка/организация из заголовка **или из параметров запроса** — `parking_id` в теле или фильтре — 403) и кладёт готовый контекст в `$request->attributes` — не в query/тело: клиент атрибуты не подменит, поля тела не перезаписываются. Контроллер читает контекст через статический `RequestHelper` подпроекта: методы `get*`, запрос — явным аргументом `Request $request`, например `RequestHelper::getParkingIds($request)` и передаёт в сервис **как обычный фильтр**: в списке — полем `{E}FilterDto`, которое выставляет контроллер (`TariffFilterDto::make()->fromArray($request->validated())->setParkingIds(RequestHelper::getParkingIds($request))`), сервис получает только DTO (в форме (1) п. 2a — параметром `SearchDataObject`, который собирает контроллер). Поле скоупа в `{E}FilterDto` — **без умолчания «без ограничения»**: не выставлено (`set…Ids()` не вызван) — геттер бросает исключение ядра (`InvalidConfigurationAppException`), `null` («без ограничения», super-admin/консоль) — только явным `set…Ids(null)`; форма — эталон `examples/parking_app/Domains/Billing/Modules/Tariffs/DTO/TariffFilterDto.php` (флаг `parkingIdsSet`). Поле `?array $parkingIds = null`, которое без сеттера читается как «все», — **[ОШИБКА]** `scope-param-defaults-unrestricted`; в операциях по id — аргументом (`?array $parkingIds`, null — без ограничения, передаётся явно — `scope-param-defaults-unrestricted`). Сервис и репозиторий про права и пользователей не знают, фильтр применяется в запросе (`whereIn`), чужая запись по id — 404. `RequestHelper` без выставленного атрибута бросает исключение, а не возвращает null («нет middleware» ≠ «без ограничения»).

    ```php
    // плохо: сервис/контроллер сами вычисляют доступ
    $ids = $this->resolver->resolveForCurrentAdmin();
    $this->service->search($dto, accessibleParkingIds: $ids);

    // хорошо: маршрут под ResolveAdminParkings, в сервис — фильтр
    $this->service->search($dto, RequestHelper::getParkingIds($request));
    ```
20. Маршруты: `Route::middleware([...])->prefix('{подпроект}/{context}')->as('{подпроект}.{context}.')->whereNumber('id')->group(...)`; имя — `{ресурс-во-мн.-числе}.{index|view|create|update|delete}`, действие над ресурсом — `{ресурсы}.{действие}` в kebab-case (`tariffs.set-default`); URL в kebab-case, мн. число (`tariff-periods`). Middleware в маршрутах — **строковым алиасом** (`'resolve.admin.parkings'`, `'api.response'`), не `::class`: алиас регистрируется в `bootstrap/app.php` → `$middleware->alias([...])` рядом с остальными; имя — в стиле существующих (`resolve.building`, `auth.admin.permission`).

## Пакеты ядра

- Ядро одно: `mps/core` + `mps/utils`, версия — из `composer.lock` проекта; версию у пользователя **не спрашивать** (решение O-4). API сверять с исходниками в `vendor/mps/core` и `vendor/mps/utils` этого проекта, а не с примерами из `mps-core/*.md`.

21. Не писать то, что есть в `mps/core` / `mps/utils` (полный список — `mps-core/capabilities.md`) и в `Core/` подпроекта (базовый `Request`, форма пагинации, `TransactionManagerInterface` при ядре < 1.10): `CRUDRepository`, `QueueManagerInterface` (RabbitMQ V2), `SmartCache`/`getOrSet`, `UploadManager`, логгер `for($this)`. `SearchDataObject`, `filterSearch()`, `QueryFilters\V2`, `PaginatedQueryHelper` — форма (1) списка (п. 2a): в коде на ней — использовать их, а не самописные замены; в форме (2) их нет. Исключение: базовые Command/Query ядра (`prepare()`/`get()`) не используются — п. 3. Подробно — `mps-core/rules.md`.
22. Устаревшее API ядра (`RabbitManager::putIn`, `QueryFilters` V1, `FileManager::upload`, `setMessagePrefix`) в новом коде — **[ОШИБКА]**. `getOrCreate`/`getCacheKeyByParts` — устаревшие только с utils ≥ 2.6.4; при utils < 2.6.4 другого API нет, это не находка (`mps-core/rules.md`, «Cache»).
