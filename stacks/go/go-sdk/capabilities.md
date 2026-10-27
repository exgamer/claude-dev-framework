# Возможности Go SDK: что уже есть

Перед тем как писать хелпер, обёртку, клиент или middleware — найти задачу в этой таблице. Есть в SDK → использовать SDK; своя реализация того, что есть в SDK, — тег `sdk-reimplemented` (`conventions.md`, п. 22).

- Таблица собрана по справочнику — полному списку пакетов, типов и функций из исходников. Источника SDK два, версии у них разные (`conventions.md` п. 23), справочник — по источнику из `design.md` («SDK»):
  - `reference/gitlab/*.md` — `git.mpinnovations.kz/mps/go-packages`, версии `go.mod` эталона `stacks/go/examples/tariff` (2026-10-22): core v1.0.10, db-core v1.0.10, http-core v1.1.0, postgres-core v1.0.8, redis-core v1.0.7, rabbit-core v1.0.10, request-builder v1.0.2, sentry-core v1.0.5, console-core v1.0.6; websocket v1.0.4 — прежняя сборка;
  - `reference/github/*.md` — `github.com/exgamer`, версии на 2026-10-22: core v1.0.29, db-core v1.0.22, http-core v1.1.0, postgres-core v1.0.24, redis-core v1.0.15, rabbit-core v1.0.13, request-builder v1.0.16, sentry-core v1.0.5, console-core v1.0.5.
  Номера версий двух источников не соответствуют друг другу; версия в шапке каждого файла справочника. Таблица ниже написана по GitLab; для GitHub вызов сверяется с `reference/github/`.
- Версия на задаче может быть другой (источник и версия — из `go.mod` проекта или ответа пользователя, п. 23 `conventions.md`, раздел «SDK» в `design.md`). Если отличается — пересобрать справочник под неё: `tools/sdk-ref/go.sh <out> --gomod <go.mod проекта>` и сверять с ним. Функции нет в справочнике нужной версии — значит её нет, не выдумывать.
- Пути импорта ниже — без префикса модуля: `core/pkg/x` = `<sdk>/gosdk-core/pkg/x`.

---

## Приложение и сборка

| Задача | Что взять |
|---|---|
| Приложение, kernels, модули, graceful shutdown | `core/pkg/app`: `NewApp()`, `RegisterAndInitKernels`, `RegisterAndInitModules`, `RunAll`, `WaitForShutdown`; свой kernel — `KernelInterface{Name, Init, Start, Stop}`, модуль — `ModuleInterface{Name, Init}` |
| Действие при остановке (закрыть свой ресурс) | `app.AddStopHook(func(ctx) error)` |
| Env-файлы | `app.SetEnvFiles(...)` / `config.LoadEnv(...)` |
| Конфиг из env в структуру | `core/pkg/config.InitConfig[E](&cfg)`; базовый — `config.BaseConfig`, `di.GetBaseConfig`; `time.Duration` из env не разбирает — таймаут в секундах (`../structure.md`, «config.go») |
| Таймзона сервиса | `core/pkg/di.GetLocation(container)` |
| DI | `core/pkg/di`: `Register[T]`, `Resolve[T]`, `MustResolve[T]` (только старт), именованные — `RegisterNamed`/`ResolveNamed`, проверка — `Has[T]`. Где можно — `stacks/go/structure.md`, «Bootstrap / Application Layer» |
| Консольные команды (миграции, ручные операции) | `console-core/pkg/app.NewConsoleKernel("console").AddCommand(cobraCmd...)` — отдельный бинарник `cmd/console`, своё приложение `internal/console/` и bootstrap `internal/console/bootstrap/` (`../structure.md`, «Entrypoint Layer») |

## Ошибки, логи, трекинг

| Задача | Что взять |
|---|---|
| Бизнес-ошибка с HTTP-статусом | `core/pkg/exception`: `NewValidationException(map, track)` (422; и невалидный id пути — `exceptions.md`), `NewForbiddenException(err, track)` (403), `NewNotFoundException(err, track)` (404), `NewAppException(err, ctx, track)` (500); `track` — отправлять ли в Sentry; `ErrorKind*` |
| Отдать ошибку в HTTP | `http-core/pkg/response.ErrorResponse(c, err)` — статус по `ErrorKind`, ручной маппинг не нужен; не-`AppException` — 500 с текстом ошибки в `message` (`exceptions.md`, «Техническая ошибка в ответе»); ожидаемый отказ с заданным статусом без Sentry (401/403/415 из middleware) — `ErrorResponseUntrackableSentry(c, status, err, ctx)` |
| Некритичная ошибка, есть fallback (Redis упал → БД) | `core/pkg/errorreporter.CaptureSoft(ctx, err, tags)` — не прерывает выполнение |
| Отправить в трекер и пробросить одной строкой | `errorreporter.CaptureError(ctx, err, tags)` / `Capture(ctx, err, Options)` |
| Перед выходом one-shot процесса дождаться отправки | `errorreporter.Flush(timeout)` |
| Подключить Sentry | **отдельный модуль** `gosdk-sentry-core` (`go get …/gosdk-sentry-core`) + `&sentryapp.SentryKernel{}` (`sentry-core/pkg/app`) в `RegisterAndInitKernels`, env `SENTRY_DSN`. Без этого ядра `errorreporter`, `NewAppException(…, true)`, `SentryMiddleware` и `SlowSqlSentryMiddleware` молча не отправляют события (пустой `SENTRY_DSN` — так же no-op) — ни ошибки, ни паники: `http-core`/`rabbit-core` Sentry больше не инициализируют (`official/gosdk-sentry-core-v1.0.2-README.md`, «breaking change»). Других вызовов sentry-go в коде нет |
| Лог | `core/pkg/logger`: `Info/Warning/Error/Debug/Trace(ctx, msg)`; уровень — `SetLevel`, `IsDebugLevel` |
| Шаги в debug-блоке ответа | `core/pkg/debug.AddDebugStep(ctx, "…")` |

## HTTP (входящий)

| Задача | Что взять |
|---|---|
| HTTP-сервер | `http-core/pkg/app.HttpKernel`; роутер — `http-core/pkg/di.GetRouter(container)` |
| Swagger UI и схема | маршрут `/<префикс>/*any` ставит `http-core/pkg/gin.InitRouter` (префикс — `SWAGGER_PREFIX`, иначе `APP_NAME`, иначе `swagger`); свой `router.GET` для Swagger не нужен. http-core ≥ v1.1.0 (GitLab и GitHub, номера совпали): только при `SWAGGER_ENABLED=true`/`1` (без учёта регистра, пробелы по краям обрезаются), пусто или другое — выключен, состояние — в лог при старте; на проде не задавать. Маршрут ставится до `router.Use` — middleware сервиса (auth) на него не действует; документация нужна на проде — SDK-маршрут выключен и свой маршрут в группе с auth, или SDK-маршрут закрыт аутентификацией снаружи (ingress, gateway). http-core < v1.1.0: выключателя нет, маршрут есть всегда — на проде закрыть снаружи сервиса (ingress, gateway) (`api-docs-exposed`, `security.md`) |
| Стандартный стек middleware | `http-core/pkg/middleware`: `RequestInfoMiddleware(a)`, `LoggerMiddleware()`, `DebugMiddleware()`, `SentryMiddleware()`, `FormattedResponseMiddleware()` (обязателен: тело любого ответа `response.*`, включая `Raw*`, пишет только он; см. `entrypoint/http.md`), `MetricsMiddleware(a)`, `CorsMiddleware(a)` (`CORS_ALLOWED_ORIGINS`) |
| Валидация тела / query | `http-core/pkg/validators.ValidateRequestBody(c, &req)` / `ValidateRequestQuery(c, &req)`; request эмбедит `http-core/pkg/gin/validation.Request`; свои правила — `CustomValidationRules()` |
| ID из пути | `validators.GetIntQueryParam(c, "id")` (положительное целое; ошибка → `ValidationException`, 422 — `exceptions.md`) |
| Пагинация из запроса | `http-core/pkg/helpers.GetPagerRequest(c)`, `DefaultPaginationPage/PerPage` |
| Ответы | `response.Success / SuccessCreated / SuccessDeleted`; ошибки — `BadRequest, NotFound, Forbidden, Unauthorized, Conflict, UnprocessableEntity, TooManyRequests, InternalServerError`; без обёртки `success/data` — `Raw*`-варианты |
| Swagger-структуры ответов | `http-core/pkg/structures`: `Response[T]`, `BadRequestErrorResponse`, `NotFoundErrorResponse`, `ForbiddenErrorResponse`, `ValidationErrorResponse`, `InternalServerResponse` |
| Данные запроса (request id, язык, город, пользователь) | `http-core/pkg/gin.GetHttpInfoFromContext(ctx)` — вне HTTP-запроса `nil`; имена заголовков — `http-core/pkg/constants.*HeaderName`; Request ID в исходящий запрос — хелпер проекта `pkg/requestid` (`infra/http.md`) |
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
| Пагинированный список | `db-core/pkg/query/helpers.NewGormPaginatedHelper[E](ctx, db).SetTimeout(10).SetPerPage(n).Paginated(page, func(db) *gorm.DB)` — единицы `SetTimeout` и когда звать `SetPerPage` — `infra/postgres.md`, «Пагинация»; `page` 0 → 1 — в маппере точки входа (`regulations/rest-api.md`, «Валидация входа») → `pagination.Paginated[E]{Items []*E, Pagination}` |
| Транзакция на 1–5 репозиториев | `db-core/pkg/transaction.NewManager[R]` … `NewManager5` + `.WithTimeout()`, `Exec(ctx, fn)`; интерфейс `TxManager` объявляет потребитель |
| Миграции | `db-core/pkg/migration.Migration` (= gormigrate), применение — `postgres-core/pkg/app.NewMigrator(db, migrations...).Up()` / `RollbackLast()` / `RollbackTo(id)` из консольной команды; kernel миграции не катит |
| Медленные запросы в Sentry | `db-core/pkg/middleware.SlowSqlSentryMiddleware(threshold, service)` (gorm plugin) |

## Redis

| Задача | Что взять |
|---|---|
| Клиент | `redis-core/pkg/app.RedisKernel`; в модуле — `redis-core/pkg/di.GetRedisClient(container)` |
| Структура / массив / строка в кеше | `redis-core/pkg/redis.NewHelper[M](ctx, client)` (`M` — модель кеша, не доменная сущность, `infra/redis.md`): `SetStruct/GetStruct`, `SetArray/GetArray`, `SetString/GetString`, пакетно — `MSetStruct/MGetStruct`. Промах — `(nil, nil)` |
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
