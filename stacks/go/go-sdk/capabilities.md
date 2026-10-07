# Возможности Go SDK: что уже есть

Перед тем как писать хелпер, обёртку, клиент или middleware — найти задачу в этой таблице. Есть в SDK → использовать SDK; своя реализация того, что есть в SDK, — **[ОШИБКА]** (`conventions.md`, п. 22).

- Таблица собрана по `reference/*.md` — полному списку пакетов, типов и функций, сгенерированному из исходников (внутренний SDK `git.mpinnovations.kz/mps/go-packages`, свежие версии из кэша на 2026-10-07: core v1.0.5, db-core v1.0.7, http-core v1.0.11, postgres-core v1.0.6, redis-core v1.0.4, rabbit-core v1.0.6, request-builder v1.0.1, sentry-core v1.0.2, websocket v1.0.4, console-core v1.0.3).
- Версия на задаче может быть другой (спрашивается по п. 23 `conventions.md`). Если отличается — пересобрать справочник под неё: `tools/sdk-ref/go.sh <out> --gomod <go.mod проекта>` и сверять с ним. Функции нет в справочнике нужной версии — значит её нет, не выдумывать.
- Пути импорта ниже — без префикса модуля: `core/pkg/x` = `<sdk>/gosdk-core/pkg/x`.

---

## Приложение и сборка

| Задача | Что взять |
|---|---|
| Приложение, kernels, модули, graceful shutdown | `core/pkg/app`: `NewApp()`, `RegisterAndInitKernels`, `RegisterAndInitModules`, `RunAll`, `WaitForShutdown`; свой kernel — `KernelInterface{Name, Init, Start, Stop}`, модуль — `ModuleInterface{Name, Init}` |
| Действие при остановке (закрыть свой ресурс) | `app.AddStopHook(func(ctx) error)` |
| Env-файлы | `app.SetEnvFiles(...)` / `config.LoadEnv(...)` |
| Конфиг из env в структуру | `core/pkg/config.InitConfig[E](&cfg)`; базовый — `config.BaseConfig`, `di.GetBaseConfig` |
| Таймзона сервиса | `core/pkg/di.GetLocation(container)` |
| DI | `core/pkg/di`: `Register[T]`, `Resolve[T]`, `MustResolve[T]` (только старт), именованные — `RegisterNamed`/`ResolveNamed`, проверка — `Has[T]`. Только в app/bootstrap |
| Консольные команды (миграции, ручные операции) | `console-core/pkg/app.NewConsoleKernel("console").AddCommand(cobraCmd...)` — отдельный бинарник `cmd/console` |

## Ошибки, логи, трекинг

| Задача | Что взять |
|---|---|
| Бизнес-ошибка с HTTP-статусом | `core/pkg/exception`: `NewNotFoundException` (404), `NewValidationException(map)` (422), `NewForbiddenException` (403), `NewAppException(err, ctx, track)` (500); `ErrorKind*` |
| Отдать ошибку в HTTP | `http-core/pkg/response.ErrorResponse(c, err)` — статус по `ErrorKind`, ручной маппинг не нужен |
| Некритичная ошибка, есть fallback (Redis упал → БД) | `core/pkg/errorreporter.CaptureSoft(ctx, err, tags)` — не прерывает выполнение |
| Отправить в трекер и пробросить одной строкой | `errorreporter.CaptureError(ctx, err, tags)` / `Capture(ctx, err, Options)` |
| Перед выходом one-shot процесса дождаться отправки | `errorreporter.Flush(timeout)` |
| Подключить Sentry | `sentry-core/pkg/app.SentryKernel` в `RegisterAndInitKernels` — других вызовов sentry-go в коде нет |
| Лог | `core/pkg/logger`: `Info/Warning/Error/Debug/Trace(ctx, msg)`; уровень — `SetLevel`, `IsDebugLevel` |
| Шаги в debug-блоке ответа | `core/pkg/debug.AddDebugStep(ctx, "…")` |

## HTTP (входящий)

| Задача | Что взять |
|---|---|
| HTTP-сервер | `http-core/pkg/app.HttpKernel`; роутер — `http-core/pkg/di.GetRouter(container)` |
| Стандартный стек middleware | `http-core/pkg/middleware`: `RequestInfoMiddleware(a)`, `LoggerMiddleware()`, `DebugMiddleware()`, `SentryMiddleware()`, `FormattedResponseMiddleware()` (обязателен и для `Raw*`), `MetricsMiddleware(a)`, `CorsMiddleware(a)` (`CORS_ALLOWED_ORIGINS`) |
| Валидация тела / query | `http-core/pkg/validators.ValidateRequestBody(c, &req)` / `ValidateRequestQuery(c, &req)`; request эмбедит `http-core/pkg/gin/validation.Request`; свои правила — `CustomValidationRules()` |
| ID из пути | `validators.GetIntQueryParam(c, "id")` (положительное целое) |
| Пагинация из запроса | `http-core/pkg/helpers.GetPagerRequest(c)`, `DefaultPaginationPage/PerPage` |
| Ответы | `response.Success / SuccessCreated / SuccessDeleted`; ошибки — `BadRequest, NotFound, Forbidden, Unauthorized, Conflict, UnprocessableEntity, TooManyRequests, InternalServerError`; без обёртки `success/data` — `Raw*`-варианты |
| Swagger-структуры ответов | `http-core/pkg/structures`: `Response[T]`, `BadRequestErrorResponse`, `NotFoundErrorResponse`, `ForbiddenErrorResponse`, `ValidationErrorResponse`, `InternalServerResponse` |
| Данные запроса (request id, язык, город, пользователь) | `http-core/pkg/gin.GetHttpInfoFromContext(ctx)`; имена заголовков — `http-core/pkg/constants.*HeaderName` |
| Метрики Prometheus | `MetricsMiddleware` / `di.GetMetricsCollector` |
| WebSocket | `gosdk-websocket`: `NewServer(routerGroup, cfg)`, `NewHub(ctx)`, `Server.Serve(path, hub)` (push-only) / `ServeFunc(hub, onConnect)`; рассылка — `Hub.Publish(eventType, data)` |

## HTTP (исходящий)

| Задача | Что взять |
|---|---|
| Запрос во внешний сервис | `http-request-builder/pkg/builder.New{Get,Post,Put,Patch,Delete}HttpRequestBuilder[E](ctx, url)` → `SetRequestHeaders`, `SetQueryParams`, `SetJSONBody`/`SetXMLBody`, `SetRequestTimeout` → `GetResult()` → `IsSuccess()/IsClientError()/IsServerError()` |

## Postgres

| Задача | Что взять |
|---|---|
| Подключение | `postgres-core/pkg/app.PostgresKernel`; в модуле — `postgres-core/pkg/di.GetDefaultPostgresConnection(container)`; второе соединение — `AddPostgresConnection` / `GetPostgresConnection(name)` |
| Пагинированный список | `db-core/pkg/query/helpers.NewGormPaginatedHelper[E](ctx, db).SetTimeout(..).SetPerPage(..).Paginated(page, func(db) *gorm.DB)` → `pagination.Paginated[E]{Items []*E, Pagination}` |
| Транзакция на 1–5 репозиториев | `db-core/pkg/transaction.NewManager[R]` … `NewManager5` + `.WithTimeout()`, `Exec(ctx, fn)`; интерфейс `TxManager` объявляет потребитель |
| Миграции | `db-core/pkg/migration.Migration` (= gormigrate), применение — `postgres-core/pkg/app.NewMigrator(db, migrations...).Up()` / `RollbackLast()` / `RollbackTo(id)` из консольной команды; kernel миграции не катит |
| Медленные запросы в Sentry | `db-core/pkg/middleware.SlowSqlSentryMiddleware(threshold, service)` (gorm plugin) |

## Redis

| Задача | Что взять |
|---|---|
| Клиент | `redis-core/pkg/app.RedisKernel`; в модуле — `redis-core/pkg/di.GetRedisClient(container)` |
| Структура / массив / строка в кеше | `redis-core/pkg/redis.NewHelper[E](ctx, client)`: `SetStruct/GetStruct`, `SetArray/GetArray`, `SetString/GetString`, пакетно — `MSetStruct/MGetStruct`. Промах — `(nil, nil)` |
| Удаление ключей | `client.Unlink(ctx, keys...)` (go-redis напрямую — в SDK обёртки нет) |

## RabbitMQ

| Задача | Что взять |
|---|---|
| Подключение | `rabbit-core/pkg/app.NewRabbitKernel().EnableConsumer().EnablePublisher()` |
| Consumer | регистрация в модуле: `rabbit-core/pkg/di.GetRabbitConsumersRegistry(c)` → `RegisterHandler(config.HandlerRegister{...})`; конфиги — `config.NewConsumer{Direct,Fanout,Topic}DurableConfig(tag, routingKey, exchange, queue, prefetch)`; политика ошибок — `Consumer.WithErrorAction/WithPanicAction(ActionAck|ActionNack)` |
| Publisher | `di.GetRabbitPublishersRegistry(c)` → `Register(config.NewPublisherDefinition(name, config.NewPublisher{Direct,Fanout,Topic}DurableConfig(exchange)...))` → `Get(name).Publish(topic, payload)` / `PublishWithMetaData` / `PublishBatch` |
| Декларация exchange/queue без consumer-а | `rabbit-core/pkg/rabbitmq.DeclareTopology(di.GetRabbitConnection(c), func(ch) error)` — в bootstrap, своё `amqp.Dial` запрещено |
| Нестроковые заголовки (x-death) ломают разбор | `rabbit-core/pkg/marshaler.DeathTolerantMarshaler` |

## Мелкие утилиты (не писать свои)

| Задача | Что взять |
|---|---|
| Filter / Map / удалить дубли / удалить по индексу | `core/pkg/slice`: `Filter`, `Map`, `RemoveDuplicates`, `RemoveAt`, `RemoveAtOrderly`, `RemoveMultiple` |
| Нормализовать телефон, проверить email/телефон | `core/pkg/validation`: `NormalizePhoneNumber`, `CheckValidPhone`, `CheckValidEmail` |
| Языки kz/ru/en | `core/pkg/constants`: `GetLanguageByCode`, `GetLanguageCode`, `LangCode*` |
| Duration строкой для логов | `core/pkg/helpers.GetDurationAsString` |

---

## Чего в SDK нет (пишется в сервисе по правилам фреймворка)

- JWT-аутентификация, проверка прав, подписи вебхуков — middleware сервиса (`entrypoints/{context}/http/middleware/`), именованные функции.
- Outbox, ретраи доставки, расписание — по `approaches/patterns/outbox.md`, `idempotency.md`.
- Инвалидация кеша по шаблону ключей, теги кеша.
- Обёртка над `Unlink` и прочими командами go-redis — не нужна, вызывать клиент напрямую в Redis-репозитории.

Если в задаче нужно что-то общее для нескольких сервисов и этого нет в SDK — не писать «универсальный» пакет в сервисе молча, а вынести вопрос «добавить в SDK?» пользователю.
