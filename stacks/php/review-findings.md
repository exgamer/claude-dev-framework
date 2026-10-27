# Замечания из реальных MR-тредов (PHP)

Эмпирический слой поверх архитектурных правил: каждое замечание ниже ревьюеры MPS уже оставляли в реальных MR
(superapp-api, parking_system).
Применять **вместе** с `conventions.md`, `mps-core/rules.md` и тематическими файлами — здесь только то, чего там нет
или что команда ловит особенно часто.

Формат: `что смотреть → почему реджектят`. Уровень находки — по тегу в каталоге (`approaches/process/review-tags.md`, `review-tags.md`; O-24), здесь — только тег.

---

## Requests (`Http/Requests/*`)

- Границы входа (`gt:0` для id, `max:`/`between:` для чисел и массивов, enum, `list` для коллекций) — общее правило `regulations/rest-api.md`, «Валидация входа»; ревьюеры ловят это чаще всего: `['integer', 'gt:0']`, `array|max:100`.
- В Request нет SQL-запросов и вызовов сервисов (P-17, `conventions.md` п. 9a): ни `exists:`/`unique:`/`Rule::exists()`/`Rule::unique()`, ни своих правил и замыканий с моделью, `DB::` или репозиторием, ни запросов в `prepareForValidation()`/`withValidator()`/`after()`. Существование — в сервисе/workflow через репозиторий, уникальность — уникальный индекс + проверка в сервисе.
- Бизнес-правила («нельзя менять статус, если оплачен») — не в Request. Но **допустимые варианты значений** (enum) — как раз в реквесте: `Rule::enum(Enum::class)` (`checklist.md` п. 12).
- PUT — полные данные (`conventions.md`, п. 12): в `UpdateRequest` поля `required` / `present|nullable`. Необязательное поле при записи DTO целиком (`updateFromDto($tariff->id, $dto)`, п. 11a) затирается `null` (происхождение — AINA-2628). Тег `update-request-optional-fields`.

## Repository

- Наследование от ядра (`CRUDRepository`), запросы через `$this->getQuery()`, не `Model::query()` / `DB::table()`; сырой SQL — только исключение `conventions.md` п. 2.
- Нет своих `*OrFail`-методов (ни в репозитории, ни в сервисе — п. 12a, SRP): `get…` возвращает `?Model`, `NotFoundAppException` бросает вызывающий. Тег `service-thin-wrapper`.
- Нет `keyBy` / `map` / сборки структур — репозиторий отдаёт сырые данные, маппинг в Service.
- **Метод репозитория — один запрос** (происхождение — mobile-api-rewrite, пользователь: «две выборки — это бизнес-логика»). Типичные формы: `lockForUpdate()->pluck()` + `delete()`, `delete()` + `upsert()`, `save()` + `first()`, «`first()` → `fill()->save()`». Тег `repository-multi-query`.
- **Запрос виден целиком** (mobile-api-rewrite MR !3773, пользователь): запрос метода не собран из приватных кусков (`service(Closure)`, `activeQuery()`), колонки — явным списком в `select`, не константой; фильтры списка — в том же методе (в форме (1) P-19 — в `filterSearch()`); список не по форме из «Список» `design.md` — отдельный тег `list-form-not-as-designed`; слишком большой запрос — сборка из нескольких репозиториев в сервисе/workflow (`conventions.md` п. 2a). Тег `query-split-into-pieces`.
- **Запись массивом** (AINA-2978, пользователь: «всегда DTO, массивы плохо») — `updateById($id, $dto->toArrayWithSnakeKeys())`, `updateById($id, ['is_default' => true])`, `create($data)` из сервиса/Command; то же в контроллере: `$this->service->update($id, $request->validated())`. По всей цепочке записи — DTO или типизированные аргументы (`conventions.md` п. 11a). Тег `write-array-instead-of-dto`.
- Нет обращения к таблице чужого домена: репозиторий пишет только в свои таблицы; данные двух доменов собирает workflow. Допустим read-only подзапрос (`EXISTS`/`JOIN`) к чужой таблице с комментарием, чья она (`architecture/cross-domain.md`).
- Локальные phpstan-обёртки над `getQuery()` (типизированные прокси ради generics) не плодить — либо в `mps/core`, либо в ignore.
- До самописа — посмотреть `mps-core/capabilities.md` (ядро: `QueryFilters\V2\StringFilter`, `LoggerAwareTrait`, …) и `Core/` подпроекта (`Page`/`PaginatedResource` для формы (2), `PaginatedQueryHelper` для формы (1) списка). «Уже есть в core» — частый реджект.

## Service

- Нет прямых запросов к БД (`DB::`, `Model::where`) — только через репозиторий.
- Чужой домен не трогает ни через репозиторий, ни через сервис (O-7); нужны 2+ домена → workflow в `Workflows/`.
- Транзакции через `TransactionManagerInterface` (см. `transactions.md`), не `DB::transaction` и не `$repo->transaction`.
- Каждый публичный метод объявлен в интерфейсе сервиса.
- **Имя метода = его действие.** `update()`, который делает select + create + restore — реджект: либо переименовать, либо разбить.
- Сравнение статусов inline (`if ($tariff->status === …)`) — проверка допустимости перехода в метод модели (`canArchive()`), тег `invariants-inline` (`regulations/entity.md`, «Переход состояния»). Похожие шаги в разных Command/методах — не находка, в state machine / стратегию не выносятся (`core/style.md`, «Форма кода», O-25).
- Побочные эффекты (`Storage::`, `addMedia*`, HTTP-вызовы, очередь) — **снаружи** транзакции, но и не после точки невозврата: сначала запись, затем эффект, а не наоборот.

## Controller

- Тонкий. Имена методов — `index / view / create / update / delete`.
- **Обёртка `success/data` в Response-классе или контроллере при `api.response`/`ApiResponseMiddleware` на маршруте** (AINA-2628: «не надо оборачивать, это делает мидлвар») — Response отдаёт только данные; в Swagger-описании метода контроллера обёртка `success`/`data` при этом описывается (это реальный формат ответа). Тег `response-double-wrap`.
- **Ответ через макрос фасада** (AINA-2628, blocking: «mixin для Response не юзаем») — `Response::ok($data)` / `Response::created()` / `Response::deleted()` из `Response::mixin(new JsonResponseMixin)` провайдера проекта. Grep по `use` такую зависимость не видит. Замена — `response()->json(new {E}Response(...), Response::HTTP_CREATED)` (`conventions.md`, п. 19). Тег `controller-response-macro`.
- Swagger на каждый эндпоинт, с `500` в ответах; `404` — по `conventions.md` п. 18.
- `per_page` в пагинации задаётся явно (в `IndexRequest`/`SearchRequest` — по форме списка, и в аннотации), не «по умолчанию где-то в ядре».
- Текущий пользователь — `request()->user` / `$request->user()`, не `Auth::id()` в глубине сервиса.
- **Middleware в маршруте через `::class`** (AINA-2628: «давай регать как остальные мидлвары») — свой middleware подключается строковым алиасом из `bootstrap/app.php` (`$middleware->alias`), как остальные (`conventions.md`, п. 20). Тег `route-middleware-not-aliased` (памятка п. 26).
- **Метод-обёртка над одной проверкой/вызовом** (AINA-2628: «`findByIdOrFail` не делай таких функций», «`assertValid` — то же самое», «какой смысл от `getById`, когда внутри вызов своей же репы») — `conventions.md`, п. 12a. Тег `service-thin-wrapper`.
- **Константа вместо enum / константа в интерфейсе** (AINA-2628: «зачем константы в интерфейсе репы, константы в enum»; mobile-api-rewrite, волна 9: `private const SERVICE_TYPE_COWORKING = 'COWORKING'` в репозитории при готовом `OrderServiceTypeEnum`, ревью пропустило — смотрели только ключи) — `conventions.md`, п. 12b. Тег `constant-not-enum` (повтор значения существующего enum и константа в интерфейсе — уровень выше, каталог). Ищется механически — `review-checks.md`, «Проверки за пределами дифа», п. 0c.
- **Контекст-фильтр отдельным аргументом поиска** (AINA-2628: «что мешает сразу DTO засетить `building_ids` в контроллере?») — `search($dto, $buildingIds)` с вписыванием в параметры внутри сервиса; фильтр выставляет контроллер полем `{E}FilterDto` (в форме (1) — параметром `SearchDataObject`) (`conventions.md`, п. 19a). Тег `search-filter-outside-dto`.
- **Список без сортировки** (AINA-2628: «не задан `orderBy()`, читать как параметр + дефолт») — `conventions.md`, п. 12c. Тег `list-without-default-sort`.
- **Фильтр валидируется, но не применяется** (AINA-2628: «IndexRequest валидирует `description` и `id_one_c`, но `filterSearch` их не применяет»; в форме (2) — поле `IndexRequest` не доходит до `{E}FilterDto` или метода списка) — `conventions.md`, п. 12d. Тег `request-filter-not-applied`.
- **Проверка доступа в FormRequest** (AINA-2628, blocking: «запрос в Request»; «валидация входных данных не место для проверки доступов») — правило вида `new AccessibleBuilding()` на `building_id`: ходит в БД за правами и дублирует middleware. Доступ к `building_id` из тела/фильтра проверяет middleware контекста (403), в Request — только `['required', 'integer', 'gt:0']`. Тег `request-access-check`.
- **Проверка прав в сервисе/контроллере** (AINA-2628: «проверка прав не совсем зона сервиса») — резолвер доступа в контроллере, параметр `$accessibleBuildingIds` в сервисе. Права и контекст — middleware контекста → `$request->attributes` → `RequestHelper` → сервис получает фильтр (`conventions.md`, п. 19a). Тег `access-check-outside-middleware`.

## Workflows / Commands

- **Читается сверху вниз** (mobile-api-rewrite MR !3773, пользователь): замыкание не передаёт поведение в свой метод, цепочки `array_values(array_unique(array_filter(array_map(…))))` → `foreach`, лямбда длиннее строки → метод или first-class callable, параметр замыкания не затеняет внешнюю переменную (`core/style.md`, «Форма кода»). Тег `nested-call-chain`; касается всех слоёв, не только workflow.

- **Форма Command/Query** (`command-query-form`, решение пользователя 2026-10-20): `final` без базового класса ядра — не `extends MPS\Core\Commands\Command`/`MPS\Core\Queries\Query`, без `prepare()`/`get()`/`setQueryParams()` и fluent-сеттеров с массивом; публичный `execute(...)` с нужными аргументами, зависимости — конструктором, `LoggerAware` — только если логировать есть что (`mps-core/commands.md`, «Форма Command и Query»).
- Workflow берёт репозитории и сервисы любых доменов напрямую (O-7); Command/Query домена — только своего домена (`domain-cross-domain-access`).
- **Workflow не вызывает другой workflow** (`workflow-calls-workflow`; решение пользователя 2026-10-20) — общая логика уходит в сервисы/Command/Query доменов, `architecture/layers.md`, «Зависимости».
- **Запросов к БД в workflow нет** (`service-direct-db-query`): ни `DB::`, ни `Model::where`/`query()`/`find`. Кейс 2026-10-14: фреймворк написал запросы прямо в workflow — памятка говорила только «в сервисе», и workflow прочитали как исключение. Нужна выборка из таблицы домена — метод в репозитории этого домена, workflow его вызывает; выборка по двум таблицам — два метода двух репозиториев и сборка в workflow, либо read-only `EXISTS`/`JOIN` внутри репозитория владельца с комментарием (раздел Repository).
- Workflow и Command не доверяют транспортному Request: доменные инварианты проверяет `{E}DtoValidator` в сервисе/workflow (P-8), потому что тот же сценарий вызывается из консоли и очереди, где Request нет.

## Domains / Infrastructure

- В `Domains/` — только интерфейсы репозиториев; реализация в `Infrastructure/`.
- Домен (Service, Command, Query) не обращается к соседнему домену вообще — ни к модели, ни к репозиторию, ни к сервису; сценарий на 2+ домена — workflow (O-7).
- Консольные команды и HTTP — только в `Entrypoints/`; один контекст не лезет в соседний.

## DataObjects

- Наследование `DataObject`, аргумент сеттера — `$value`, сборка через `fromArray()`, даты — `Carbon`, без логики.
- **Enum-поле DTO строкой** (AINA-2628, question: «а почему бы в DTO сразу не сложить `OneCDictionaryTypeEnum`?») — `private ?string $type` + `getType() === TypeEnum::FIXED->value`. Свойство и сеттер — enum, ядро приводит строку само (`mps-core/data-objects.md`). Тег `dto-enum-as-string`.
- **Тип сеттера шире свойства** (AINA-2628, blocking: «эти все типы прям нужны?») — `setQuantity(int|float|string|null $value)` с ручным `(float)` при свойстве `?float`. Строки из формы `fromArray()` приводит сам (`mps-core/data-objects.md`), сеттер — `float|null`. Тег `dto-setter-widened-type`.
- Сложная сборка DTO (из нескольких источников) — в фабрику, не в конструктор DTO и не в контроллер. Вложенная коллекция — `{E}ItemsDto` (`DataObjectCollection`), сеттер без сборки элементов; Request — `list` + `{поле}.*: array` (`mps-core/data-objects.md`, AINA-2978).
- Суффикс — `Dto` (`{Entity}Dto`, P-5/P-7); `DTO` в новом PHP-коде — `dto-suffix-uppercase`. В Go — `DTO` по конвенции аббревиатур языка (`stacks/go/checklist.md` п. 34): различие стеков, не ошибка.

## Enums / Models

- Docblock класса и `@property` на каждое поле модели обязательны; описание поля — у неочевидных (`style.md`, «Классы»).
- Логика внутри enum (`match` с бизнес-решениями, обращения к сервисам) — реджект; допустимы только `labels()`/`values()`-подобные вещи. Сюда же предикаты-классификаторы легаси (`RentTypeEnum::addressOnlyRent()`, `isCoworking()`): при переносе в enum не идут — нужны, когда появится потребитель, — в сервис/workflow (AINA-2978, тег `enum-contains-business-logic`). Граница: подмножество значений (`TariffStatusEnum::notArchived()`) — метод enum (`conventions.md` п. 12b); вопрос «можно ли с этой записью сделать X» (`isDeliverable()`, `isArchivable()`) — не в enum, а `can*()` модели (`mps-core/model.md`, O-26).
- **Enum без `Enumerable`** (AINA-2628, nit: «Имплементить Enumerable из mps/core») — каждый enum: `implements \MPS\Core\Enums\Enumerable` и `use EnumerableTrait;` первой строкой тела (`mps-core/enums.md`). Тег `enum-not-enumerable`.
- Eloquent-связи (`BelongsTo`, `HasMany`) в моделях не используются — данные собираются через репозитории; параметр `expanded` не используется (`mps-core/service.md`, «Связанные данные — без `expanded`»), тег `expanded-param-used`.
- Модель лежит в домене/инфраструктуре **своей** сущности, не рядом с тем, кто её первым использовал.

## Внешние интеграции (`Infrastructure/*/Integrations`, `Infrastructure/Http/*`)

- Порт и адаптер внешней системы — по роли: данные — `…Repository`, отправка — `…Notifier`/`…Publisher`/`…Sender`; не `*Gateway`, не `*Service`, без имени системы в имени порта (`architecture/integrations.md`).
- Зависимости (HTTP-клиент, конфиг) — в конструктор, не `app()`/`config()` внутри методов.
- Отсутствующий обязательный конфиг → свой наследник `InvalidConfigurationAppException` с 500 (`mps-core/exceptions.md`), не молчаливый дефолт `?? ''`.
- **Индекс массива без проверки** (`array-key-unchecked`, `conventions.md` п. 11b) — `$payload['x']`, `$response['data'][0]`, `[$a, $b] = explode(...)`, `$byId[$id]` по внешним или динамическим данным: локально ключ есть, на проде нет → Warning, `null`, `TypeError`. Внешний массив — в DTO через `fromArray()` на границе; обязательный ключ без значения — исключение с именем поля, не дефолт.

## Ошибки, логи, Sentry

- `catch` не проглатывает: либо rethrow, либо `AppException` с контекстом. `catch (\Throwable) { return null; }` — реджект.
- Исключения из ядра (`*AppException`), не `\RuntimeException`/`\Exception`.
- Контекст — в исключение (`details`), а не «залогировал + бросил без контекста».
- Консольные команды и кроны: fail должен уйти в Sentry, а не только в `$this->error()`.
- В логах нет секретов, токенов, полного тела запроса.

## Производительность

- Нет запросов внутри циклов (N+1) — выборка пачкой по `ids`, затем маппинг в памяти.
- Большие выборки — `chunk()` / `cursor()`; массовые операции — батчами (`upsert`, `whereIn`).

## Безопасность (дополнение к чек-листу ревьюера)

- Предзагрузка / привязка чужих ресурсов по `id` из запроса → проверка владельца, иначе IDOR.
- Состояние в памяти процесса (кэш, дедупликатор, счётчик) → вопрос «а если поднимут два инстанса?». Ответ «один инстанс» не принимается — через Redis/БД.

### Мульти-тенантный скоуп, fail-open, вебхуки — самые частые классы находок

Из security-аудита `superapp-api` (2026-09): мульти-тенантный скоуп повторился ~10 раз в разных доменах. Признаки, реальные кейсы и что проверять — `security.md`, теги `tenant-scope-missing`, `fail-open-authorization`, `unverified-webhook`; схема скоупа — `architecture/layers.md`, «Принадлежность записи скоупу — два случая» (id владельца из запроса — 403 в middleware, по ID записи — фильтр в репозитории, 404).

### Копировать поведение с соседней реализации, не считать его образцовым

Если в проекте есть две параллельные реализации одного и того же (два платёжных шлюза, два вендора внешней
интеграции, два модуля с похожей админкой) — сверять их между собой; это явная эвристика: если один
правильно скоупит по владельцу/маскирует чувствительное поле/проверяет подпись, а другой (более новый либо
просто скопированный) — нет, это почти всегда баг копирования, а не осознанное решение. Спрашивать «а как
это сделано в соседнем файле» — быстрее, чем гадать, является ли расхождение осознанным.

## За пределами файла — что ревьюеры проверяют по репозиторию

Правила — `approaches/process/review.md`, «За пределами дифа»; здесь — что искать в PHP.

- **Разъезд захардкоженных списков** (`enum-list-drift`): `in:a,b` в других реквестах, `enum: [...]` в `@OA`-аннотациях, словари во фронтовых эндпоинтах; мапперы, allowlist и `match` по enum (`MediaCollectionMapper::MAP` — кейс в enum есть, в маппере нет, AINA-2978).
- **Данные под миграцию поведения**: новый каст модели (`'col' => SomeEnum::class`) — значение вне enum → `ValueError` при гидрации и 500 на чтении.
- **Гигиена дифа**: `composer.lock` вместе с `composer.json`; `.php-cs-fixer.cache`, сгенерированный `storage/api-docs/*.json` и другие сгенерированные файлы не уезжают в MR; файл может отслеживаться git вопреки `.gitignore` (закоммичен до того, как попал в игнор) — смотреть `git status --short`, не только `.gitignore`.
- **Комментарии-пересказы кода**, сгенерированные ИИ (`// получаем пользователя`), — снимать до ревью; за них бьют прицельно. Сюда же — комментарии, объясняющие архитектуру/ответственность слоёв и устройство связей («проверяется здесь, а не в Request», «X о Y не знает — связь здесь») — `core/style.md`, «Комментарии». Тег `ai-style-comment`.
- **Описание метода пересказывает имя** (AINA-2628, nit: «лишний коммент — читается из метода») — «Неудалённые здания по набору id» над `findAliveByIds`. Убрать описание, `@param`/`@return` с типами оставить. Тег `comment-restates-name`.
- **Коммиты** — по `regulations/git.md`, «Коммиты» (`тип: описание #КЛЮЧ-задачи`, типы и требования к описанию — там); несоблюдение просят исправить прямо в тредах.
- «Поправим потом» как ответ на замечание — `regulations/code-review.md`, «Замечания на ревью».
