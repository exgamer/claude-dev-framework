# Возможности mps/core и mps/utils: что уже есть

Перед тем как писать хелпер, клиент, кеш, фильтр или middleware — найти задачу здесь. Есть в ядре → использовать ядро; самописная замена — **[ОШИБКА]** (`../conventions.md`, «Пакеты ядра»).

- Собрано по `reference/*.md` — полному списку классов и public-методов, сгенерированному из исходников:
  - `mps-core-v1.6.0.md`, `mps-utils-v2.6.2.md` — версии superapp-api (`vendor/`);
  - `mps-core-v1.10.2.md` — последний тег `mps/core` (2026-09-25);
  - `mps-utils-v2.8.4.md` — локальный репозиторий `~/MPIProjects/mps/utils` (v2.8.4 + 16 коммитов).
- Официальная документация пакетов — `official/core-v1.10.2/`, `official/utils-v2.8.4/`: как пользоваться компонентом подробно. При расхождении с этим файлом — верна официальная дока нужной версии.
- Версия в проекте — из `composer.lock`; методы сверять с `vendor/mps/*` проекта. Пересобрать справочник под проект: `tools/sdk-ref/php.sh <out> <project-dir>`. Метода нет в справочнике версии проекта — его нет.
- Пометка «utils ≥ 2.6.4» и т.п. — с какой версии есть; в более старом проекте не использовать.

---

## Данные и CRUD (mps/core)

| Задача | Что взять |
|---|---|
| DTO | `MPS\Core\DataObjects\DataObject`: `::make()`, `fromArray()`, `toArray()`, `toArrayWithSnakeKeys()`, `toArrayWithCamelKeys()`, `validated()`; коллекция — `DataObjectCollection` (`pushItem`) |
| Репозиторий БД | `MPS\Core\Components\CRUD\Repositories\Database\Eloquent\CRUDRepository` (+ `CRUDRepositoryInterface`): `create`, `oneById`, `oneByIdOrFail`, `oneByCondition`, `allByCondition`, `countByCondition`, `isUnique`, `search`, `update`, `updateById`, `upsert`, `delete`, `deleteById`; запросы — `getQuery()`; фильтры поиска — override `filterSearch()` |
| Сервис | `MPS\Core\Services\Service`; сложный сервис поверх CRUD — `CrudServiceDecorator` (core ≥ 1.7.0); CRUD целиком — `Components\CRUD\Services\Database\Eloquent\CRUDService` (`create/update/delete(*CommandDataObject)`, `searchById`, `deleteById`), поиск — `SearchAwareTrait` (`search`, `searchById`, `searchByIds`) |
| Параметры поиска | `Components\CRUD\DataObjects\SearchDataObject` (`setParams`, `setPerPage`, `setPage`) |
| События CRUD | `Components\CRUD\Events\{Before,After}{Modify,Delete}Event`, `AfterSearchEvent` |
| Модель | `MPS\Core\Models\Model` |
| Enum с метками | нативный `enum` + `MPS\Core\Enums\Enumerable` + `EnumerableTrait` (класс `MPS\Core\Enums\Enum` — deprecated с core 1.7.0): `values()`, `labels()`, `list()`, `label()`, `getLabel()`; готовые — `CurrencyEnum`, `LocaleEnum`, `EnvEnum`, `StringCaseEnum`, `StatusEnum` |
| Окружение | `EnvEnum::fromAppConfig()`, `MPS\Core\Helpers\EnvHelper::is(EnvEnum::PROD)` |
| JSON | `MPS\Core\Helpers\JsonHelper`: `encode`, `decode`, `isJson`, `getSizeInBytes` |
| Заполнить объект из массива | `MPS\Core\Helpers\InstanceHelper::objectFromArray()` / `dataObjectFromArray()` |
| Сортировка из запроса | `MPS\Core\Helpers\DBSortHelper`, `RequestHelper::getSortParam()/getPageParam()/getPerPageQueryParam()` |
| DI из массива | `definitions.php` (подхватывает `ModuleServiceProvider`): core ≤ 1.6.0 — `Helpers\AppHelper::setDefinitions()`; core ≥ 1.6.1 — `Components\Container\Helpers\DefinitionHelper`; `bindType` enum — core ≥ 1.9.0 |
| Временно подменить биндинг (тесты) | `Components\Container` (core ≥ 1.8.0), см. `official/core-v1.10.2/container.md` |
| Провайдер модуля (routes, definitions, migrations) | `MPS\Core\Modules\ModuleServiceProvider` |
| Кеш схемы БД (ускорение репозиториев) | `php artisan` команда `SchemaPreloadCommand` (core ≥ 1.4) |

## Изменения ядра 1.6 → 1.10 (важно при чтении старого кода)

| Версия | Что |
|---|---|
| 1.6.1 | `AppHelper` удалён → `DefinitionHelper`; `ModuleServiceProvider` — флаг `$withSrc` (по умолчанию `true`) |
| 1.7.0 | `CrudServiceDecorator`; класс `Enum` — deprecated; `UnTrackableAppException` immutable |
| 1.8.0 | компонент `Container` — временная подмена биндингов |
| 1.9.0 | `BindTypeEnum` (`scoped`, `*If`) |
| 1.9.2 | `CRUDRepository`: условие, ставшее пустым после фильтрации колонок, — исключение (раньше молча `UPDATE`/`DELETE` всей таблицы) |
| 1.10.0 | `TransactionManager` в ядре |

## Ошибки (mps/core)

| Задача | Что взять |
|---|---|
| 404 / 422 / 400 / 403 / 401 / 429 / 500-операция / конфиг | `NotFoundAppException`, `ValidationAppException(msg, details)`, `BadRequestAppException`, `AccessDeniedAppException`, `UnAuthorizeAppException`, `TooManyRequestsAppException`, `OperationFailedAppException`, `InvalidConfigurationAppException` |
| Ошибка без отправки в Sentry | `UnTrackableAppException` / `setIsTrackable(false)` |
| Свой код ошибки | `AppException::setAppErrorCode()` + enum `AppErrorCodeEnumInterface` |

## HTTP (mps/core + mps/utils)

| Задача | Что взять |
|---|---|
| Базовый FormRequest | `MPS\Core\Http\Requests\FormRequest`, поиск — `SearchRequest`, по ID-шникам — `ByIdsSearchRequest`, смена статуса — `StatusChangeRequest` |
| Ответ | `MPS\Core\Http\Responses\Response` / `JsonResponse` (JsonResource); обёртка `success/data` — middleware `MPS\Utils\Http\Middleware\ApiResponseMiddleware` |
| Локаль из запроса | `MPS\Utils\Http\Middleware\LocaleMiddleware` |
| Basic Auth на группу маршрутов | `MPS\Utils\Components\Authenticate\Basic\BasicAuthMiddleware` |
| Health-check (liveness/readiness) | `MPS\Utils\Components\HealthCheck` — контроллер, консольные команды, `HealthCheckManagerInterface` |
| Метрики Prometheus | `MPS\Utils\Components\Prometheus` — `MetricMiddleware`, `MetricController`, `MetricManagerInterface` |
| Правило валидации по regex | `MPS\Utils\Validation\RegexRule`, `MultipleRegexRule`; готовые шаблоны — `MPS\Utils\Enums\RegexEnum` (`MOBILE_PHONE`, `EMAIL`, `SLUG`…) |
| Swagger | `MPS\Utils\Components\Documentation\Swagger` (`SwaggerHelper`, команда сканера) |

## Фильтры запросов (mps/utils)

| Задача | Что взять |
|---|---|
| Фильтр по строке / числу / диапазону / null | `MPS\Utils\Components\QueryFilters\V2\{StringFilter, IntFilter, RangeFilter, NullableFilter}` → `setColumn()`, `setValue()`/`setValueFrom/To()`, `setMatchMode(FilterMatchEnum)`, `apply($query)`. V1 (`QueryFilters\StringFilter` без `V2`) — `@deprecated` |

## Очереди RabbitMQ (mps/utils)

| Задача | Что взять |
|---|---|
| Отправить сообщение | `MPS\Utils\Components\Queue\RabbitMQ\V2\Interfaces\QueueManagerInterface`: `putInQueue(QueueConfig, payload)`, `putInExchange(exchange, payload, routingKey)`, `publish(RabbitMessage)`, `batchPublish` |
| Объявить очередь/exchange/bind | тот же `QueueManagerInterface`: `declareQueue`, `declareExchange`, `bindQueue`, `purgeQueue`, `deleteQueue`; консоль — `QueueInitCommand`, `QueueCleanupCommand` |
| Отложенные сообщения | `V2\Interfaces\DelayedQueueManagerInterface` (`init`, `handleJob`) |
| Alternate exchange | `V2\Interfaces\AlternateExchangeManagerInterface` |
| Обработчик сообщения | `MPS\Utils\Components\Queue\RabbitMQ\Consumer\Job::handle(QueueJob, array $payload)`; регистрация — `config/queue.php` |
| Моки для тестов | `V2\Mocks\{QueueManagerMock, DelayedQueueManagerMock, AlternateExchangeManagerMock}` (`mock` в `definitions.php`) |
| ~~`RabbitManager`, фасад `Rabbit`~~ | `@deprecated` — не использовать |

## Исходящий HTTP (mps/utils)

| Задача | Что взять |
|---|---|
| Клиент внешнего сервиса (utils 2.6.x) | `MPS\Utils\Components\Http\Repository\HttpRepository` — база для инфра-репозитория: `request(HttpRequestTypeEnum, url, data)`, `extract()`, `concurrent()`; клиент — `HttpClientInterface` (`get/post/put/patch/delete`, `request(HttpRequest)`), конфиг — `HttpClientConfig` |
| Параллельные запросы | `ConcurrentRequestManagerInterface` / `HttpClient::concurrent()` |
| Fluent-построитель (utils ≥ 2.8) | `MPS\Utils\Components\Http\HttpRequestBuilder\HttpRequestBuilder::post($url)->withHeaders()->withBody()->withConfig()->send()`; параллельно — `HttpConcurrentBuilder::make()->add(alias, req)->send()` |
| Шина (ServiceBus) | `MPS\Utils\Components\Integration\ServiceBus\Repositories\{Repository, CRUDRepository}` |
| Интеграции с сервисами MPS | пакеты `mps/*-service-integration` (auth, notification, payment, fiscalization) из `composer.json` — не писать клиент заново |

## Кеш и Redis (mps/utils)

| Задача | Что взять |
|---|---|
| get/set/forget (utils 2.6.x) | `MPS\Utils\Components\Cache\CacheManagerInterface`: `get`, `set`, `forget`, `getOrCreate(key, closure, ttl)`, ключ — `getCacheKeyByParts()` |
| то же (utils ≥ 2.6.4) | `Cache\Interfaces\CacheManagerInterface`: **`getOrSet`** (вместо `getOrCreate` — `@deprecated`), `getMultiple`, `setMultiple`; ключ — `Redis\Helpers\KeyHelper::generate()` (вместо `getCacheKeyByParts` — `@deprecated`) |
| Кеш с тегами и автообновлением (utils ≥ 2.8) | `Cache\Interfaces\SmartCacheInterface`: `getOrSet(key, CallbackOptions, CacheOptions::make()->ttl()->tags())`, `invalidate(tag)`, `getByTag` |
| Множества / sorted set в Redis (utils ≥ 2.8) | `MPS\Utils\Components\Redis\Structures\{Set, SortedSet}`, теги — `Redis\Tag\TagManager` |

## Файлы (mps/utils)

| Задача | Что взять |
|---|---|
| Загрузить файл | `MPS\Utils\Components\FileSystem\Interfaces\UploadManagerInterface`: **`fromObject(UploadedFile, dir)`**, `fromUrl`, `fromBase64`, `fromContents`, `fromStream` → `UploadInfo`. `FileManager::upload*` — `@deprecated` с 2.3.0. Метода `upload()` у `UploadManager` нет |
| Читать / писать / ссылка / временная ссылка | `FileManagerInterface`: `get`, `has`, `put`, `getUrl`, `getTmpUrl`, `getFileInfo` |
| Временные файлы | `TmpFileManagerInterface` (`put`, `delete`) |
| Проверка, что расширение совпадает с содержимым (utils ≥ 2.8) | `UploadManagerInterface::assertNameExtensionMatchesContent()` |
| Excel / CSV | чтение — `Components\Excel\ExcelReader` (`readChunk`, `readRowsCallback`), запись — `Components\ExcelWriter\ExcelWriter`, CSV — `Helpers\CSVHelper::readByPath()` |

## Логи (mps/utils)

| Задача | Что взять |
|---|---|
| Логгер в классе | `Components\Logger\Interfaces\LoggerAwareInterface` + `Traits\LoggerAwareTrait`; префикс — `$this->getLogger()->for($this)` (`setMessagePrefix` — `@deprecated` с 2.5.4) |
| Исключение в лог | `LoggerInterface::errorException($e)` / `exception($level, $e)` |
| Контекст на весь запрос | `Components\Logger\LogContext::set/push`, `LogContext::clear()` — обязательно в middleware при Octane |
| Медленные запросы | `Components\Database\SlowQueryDetector` |

## Сервисные трейты (mps/utils)

| Задача | Что взять |
|---|---|
| Смена статуса сущности | `Components\StatusChange\Services\StatusChangeServiceTrait` (`statusChange`) + `StatusChangeRequest`; исключения — `EntityAlreadyThisStatusException`, `StatusNotDefinedException` |
| Поиск по UUID | `Components\Uuid\Services\UuidServiceTrait` (`getByUuid`, `getByUuidOrFail`, `getByUuids`) |
| Slug | `Components\Slug\Services\SlugServiceTrait` (`generateSlug`, `getBySlug`, `checkSlugUnique`) |
| Переводы (kk/ru/en) | `Components\MultiLanguage` — `TranslationModelTrait`, `TranslatableServiceTrait`, `TranslatableRequest` |
| Дерево (категории) | `Components\Tree` — `TreeRepositoryTrait`, `TreeServiceTrait`, `TreeCacheService` |
| ElasticSearch | `Components\ElasticSearch` — `Repositories\CRUDRepository`, `Services\CRUDService`, `QueryFilters\*`, `SearchQuery` |

## Мелкие хелперы (mps/utils)

| Задача | Что взять |
|---|---|
| Убрать null из массива | `Helpers\ArrayHelper::removeNullValues` |
| Деньги строкой | `Helpers\NumberHelper::asMoney` |
| Чистка строки, лишние пробелы | `Helpers\StringHelper::clear`, `cleanMultiSpaces` |
| HMAC-подпись (вебхуки) (utils ≥ 2.8) | `Helpers\HmacHelper::compute`, `verify` (сравнение в постоянное время) |
| Base64 URL-safe (utils ≥ 2.8) | `Helpers\Base64Helper` |
| JWT HS256 (utils ≥ 2.8) | `Components\Jwt\JwtManager` (`generate`, `validate`) + `Signers\HS256Signer` |
| Стресс-тесты (utils ≥ 2.8) | `Components\StressTest` — middleware по флагу/токену, троттлинг |

---

## Чего в ядре нет (живёт в `Core/` подпроекта — см. `parking_app/Core`)

- `PaginatedQueryHelper` — `App\ParkingApp\Core\Database\Query\PaginatedQueryHelper` (в ядре нет).
- Менеджер транзакций при `mps/core` < 1.10 — `Core/Database/Managers/TransactionManagerInterface` + `Infrastructure/Postgres/Database/Managers/DatabaseTransactionManager` (+ `NoopTransactionManager` как mock). С **core ≥ 1.10.0** — в ядре: `MPS\Core\Components\Database\Transaction\{TransactionManagerInterface, TransactionManager, NoopTransactionManager}` (`run`, `afterCommit`, `begin/commit/rollBack`, `onConnection`), биндинг регистрирует ядро.
- `Repository::transaction()` в ядре есть, но в сервисах/workflow его не использовать — только `TransactionManagerInterface` (`../conventions.md`).
- Базовые `Request` / `SearchRequest` / `PaginateResource` подпроекта — в `Core/Requests`, `Core/Resources`.

Если нужна общая для нескольких проектов вещь, которой нет в ядре, — не писать «универсальный» класс в проекте молча, а спросить пользователя: «добавить в mps/utils?».
