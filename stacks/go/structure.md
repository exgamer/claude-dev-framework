# Архитектурный контекст

Описывает архитектурные правила, структуру проекта и соглашения.
Используется для проверки: правильно ли расположен код, соблюдены ли слои и зависимости.

---

## Слои и зависимости

Сервис строится по Clean Architecture. Зависимости направлены внутрь — к Domain.

```
┌─────────────────────────────────────────┐
│         Application / Bootstrap         │  ← собирает всё вместе
├─────────────────────────────────────────┤
│             Entrypoint Layer            │  ← HTTP handlers, RabbitMQ consumers
├─────────────────────────────────────────┤
│            Workflow Layer (*)           │  ← cross-domain оркестрация
├─────────────────────────────────────────┤
│               Domain Layer              │  ← бизнес-логика, интерфейсы
├─────────────────────────────────────────┤
│           Infrastructure Layer          │  ← Postgres, Redis, HTTP client
└─────────────────────────────────────────┘

(*) опциональный слой — только для cross-domain сценариев
```

**Зависимости слоёв** — допустимые и запрещённые (нарушение на ревью — тег случая или `layer-dependency-forbidden`, `approaches/process/review-tags.md`) — `architecture/layers.md`, «Направление зависимостей» и «Зависимости» (O-7, workflow). Go-специфика:
- `internal/common` → только стандартная библиотека и сам `common` (`architecture/layers.md`, «Общий бизнес-словарь»; тест границ — `review-checks.md`);
- Infrastructure реализует интерфейсы домена и порт workflow неявно, без импорта workflow;
- `domains/{A}` → `domains/{B}` — запрещено: понятие двух доменов — `internal/common`, сценарий двух доменов — workflow;
- `domains/{domain}/{A}` → `domains/{domain}/{B}` (соседний модуль своего домена) — запрещено: общее модулей — `domains/{domain}/common/` (границы как у `internal/common`, плюс сам `internal/common`), связь — интерфейс в `modules.go` (`architecture/layers.md`, «Зависимости»);
- `pkg/` → не `internal/**` (раздел «pkg/ — общий код без домена»).

---

## Ответственность слоёв

### Domain Layer
- **Что**: бизнес-логика. Не зависит от БД, HTTP, любых внешних систем.
- **Состав**: `entity.go` (доменная модель без ORM/JSON тегов), `repository.go` (интерфейс), `service.go` (бизнес-операции через интерфейс репозитория); по необходимости — `dto.go`, `enum.go` (константы модуля), `errors.go`, `{действие}_command.go`, `{действие}_query.go`, `{entity}_validator.go`, `context.go` (контекст актора в `ctx`), `tx_manager.go`, `modules.go`, `system.go` (`conventions.md` п. 12, 13, 16).
- **Правило**: domain объявляет интерфейс Repository сам; infrastructure его реализует.

### Infrastructure Layer
- **Что**: реализации интеграций с внешними системами.
- **Состав**: `model.go` (GORM/JSON модель), `repository.go` (реализует доменный интерфейс), `mapper.go` (Model ↔ Domain entity); адаптер порта не-хранилища — файл по роли порта (`publisher.go`, `publisher_definition.go`, `notifier.go`, `verifier.go`; `architecture/integrations.md`).
- **Правило**: mapper обязателен на каждом слое; прямых зависимостей структур между слоями нет.

### Entrypoint Layer
- **Что**: взаимодействие с внешним миром: HTTP, RabbitMQ, консольные команды.
- **Состав**: `handler.go`, `routes.go`, `request.go`, `response.go`, `mapper.go`, `{module}_consumer.go`, `consumer_registry.go`, `session.go` (WebSocket), `{module}_command.go` (консоль); middleware контекста — `http/middleware/{назначение}.go` («Middleware» ниже).
- **Путь**: HTTP — `internal/entrypoints/{context}/http/{domain}/{module}/`, RabbitMQ — `internal/entrypoints/{context}/rabbit/{domain}/{module}/`, WebSocket — `internal/entrypoints/{context}/ws/{domain}/{module}/`, другой транспорт (pub/sub, тикер) — `internal/entrypoints/{context}/{транспорт}/{domain}/{module}/`, консоль — `internal/entrypoints/system/console/{domain}/{module}/` (`entrypoints/admin/http/billing/tariff/`, `entrypoints/consumer/rabbit/billing/tariff/`, `entrypoints/system/console/billing/outbox_tariff_event/` в `examples/tariff`).
- **WebSocket** — свой транспорт: файлы как у HTTP (`handler.go` — открытие соединения и разбор сообщений, `routes.go`, `request.go`, `response.go`, `mapper.go`) плюс `session.go` — жизненный цикл соединения (открытие, чтение и запись, закрытие). ctx соединения создаётся при открытии, хранится в сессии и отменяется при закрытии; сообщение обрабатывается с ctx сессии, потому что колбэки SDK приходят без ctx. Это единственное исключение из `conventions.md` п. 2 (`context-not-propagated`): в остальных структурах ctx не хранится.
- **Маршруты с телом** (`POST`/`PUT`/`PATCH` с телом запроса) принимают только JSON — `RequireJSONMiddleware` (иное — 415), тело — строго один объект с пределом размера (теги `request-body-not-strict`, `missing-body-limit`; `architecture/layers.md`, «Принадлежность записи скоупу — два случая»).
- **Порядок в handler**: валидация → маппинг Request→Domain → вызов сервиса/workflow → маппинг Domain→Response → HTTP ответ через `response.*`. Бизнес-логики и запросов к хранилищам нет; маппинг Request ↔ Domain ↔ Response — только в `mapper.go`; каждый эндпойнт — со Swagger-аннотациями. HTTP — `go-sdk/entrypoint/http.md`, RabbitMQ — `go-sdk/entrypoint/rabbit.md`.
- **`{context}`** — аудитория или точка входа API (пример: `admin`, `client`, `public`, `integration`, `partner`, `consumer` — для consumer событий). Каталог `internal` в контексте нельзя: `internal/` в пути Go закрывает пакет от `app/bootstrap` (`entrypoints/internal/rabbit/...` не компилируется) — consumer событий брокера — контекст `consumer` (`entrypoints/consumer/rabbit/{domain}/{module}/`), вебхуки и межсервисный HTTP — `integration`. Служебные эндпойнты (health, ready, metrics) — контекст `system`, с транспортом в пути: `entrypoints/system/http/healthcheck` (решение G-4); `entrypoints/system/healthcheck` без `http/` — старая форма. Определяется тем, *для кого* предназначен эндпойнт. Один эндпойнт для разных аудиторий — разные директории.
- **Консоль** (cron, ручные операции, миграции) — своё приложение и свой bootstrap: отдельный бинарник `cmd/console` — `PostgresKernel`, при отправке в брокер `RabbitKernel` с `EnablePublisher()`, `ConsoleKernel` (`console-core/pkg/app.NewConsoleKernel`, `go-sdk/capabilities.md`). Модуль консоли — `internal/console/bootstrap/{domain}/{module}/`, по подобию серверного `app/bootstrap/`: `module.go` с полем `Console *consoleapp.ConsoleKernel`, свои фабрики и `config.go` по необходимости, `commands_factory.go` (cobra-команды из Command/сервисов домена) → `m.Console.AddCommand(...)` в `Init` до `Start` ядра. Серверный `app/bootstrap/{domain}/{module}/module.go` — без `Console` и `commands_factory.go`. Команде нужен сервис соседнего модуля — его собирает консольный bootstrap этого модуля и отдаёт через DI («Зависимости модулей bootstrap» ниже), серверный bootstrap в консоль не подключается. Команда — `New{Action}Command(...) *cobra.Command`, `Use: "{module-kebab}:{action}"` (`outbox-tariff-event:retry`), тонкая: один вызов Command домена, итог — в лог. Сборка консольного приложения — `internal/console/app.go` (`cmd/console/main.go` вызывает `console.NewApp()`, `RunAll()`, `Close()`): ядра, `migrate`, модули консоли из `internal/console/bootstrap/`; у разовой команды `WaitForShutdown` нет — соединения ядер закрывает `Close()` в обратном порядке. Команда `migrate` (`up`, `down`, `down-to <id>`) — `internal/console/migrate.go`, добавляется в `ConsoleKernel` прямо в `console/app.go`, без модуля и `commands_factory`: миграции общие для сервиса, не принадлежат модулю. Эталон — `examples/tariff`: `cmd/console/main.go`, `internal/console/{app,migrate}.go`, `internal/console/bootstrap/billing/outbox_tariff_event/{module,commands_factory}.go`, `entrypoints/system/console/billing/outbox_tariff_event/outbox_tariff_event_command.go`, README эталона.
- **Миграции** — `internal/migrations/`: файл `{YYYYMMDDHHMMSS}_{что_делает}.go` (`20261020100100_create_outbox_tariff_events_table.go`) с функцией `migration{YYYYMMDDHHMMSS}() *migration.Migration` (`db-core/pkg/migration`), `ID` — имя файла без `.go` (`20261020100000_add_is_active_to_tariffs_table`), `Migrate` и `Rollback`; `registry.go` — `All()` в порядке применения, катит консольная команда через `postgres-core/pkg/app.NewMigrator(db, migrations.All()...)` (kernel миграции не катит). Эталон — `examples/tariff/internal/migrations/`.
- **Middleware** — `internal/entrypoints/{context}/http/middleware/`, один на контекст (`admin`, `integration`): файл по назначению в snake_case (`jwt_auth.go`, `require_permission.go`, `resolve_admin_parkings.go`, `signature_auth.go`, `api_key.go`), функция — с суффиксом `Middleware`, как в SDK (`middleware.CorsMiddleware`): `JWTAuthMiddleware(...) gin.HandlerFunc`, `RequirePermissionMiddleware(permission string)`. Значение, которое middleware кладёт в контекст, читает экспортированная функция того же пакета (`ParkingScopeFromContext(ctx)`): нет значения — ошибка, а не «без ограничения». Зависимости (доменный сервис, настройки) — аргументами; ставит middleware на маршруты `routes.go` модуля, собирает — `module.go` (сервис — из DI) и передаёт в `SetRoutes` аргументом («Bootstrap / Application Layer», «Маршруты модуля»). Эталон — `examples/tariff/internal/entrypoints/admin/http/middleware/`, `examples/tariff/internal/entrypoints/integration/http/middleware/`.

### Workflow Layer *(опциональный)*
- **Что**: оркестрирует несколько доменных сервисов для бизнес-процесса, не принадлежащего ни одному домену.
- **Состав**: `{конкретное_действие}_workflow.go` (оркестратор), `dto.go` (Input/Result), `tx_manager.go` (интерфейс транзакции, если workflow делает больше одного модифицирующего запроса — O-8); по необходимости — `repository.go` / `system.go` (порт внешней системы, которого нет в домене, и системное — по критерию `conventions.md` п. 12, `go-sdk/workflow.md`).
- **Правила**: зависит только от Domain — берёт репозитории доменов интерфейсами, сервисы и Command доменов — конкретным типом или интерфейсом, который объявил сам workflow, когда он нужен (O-7, `go-sdk/workflow.md`); не знает про HTTP/Gin/очереди и инфраструктуру. Самостоятельность, логика агрегата, таблицы — домену — `architecture/layers.md`, «Зависимости»; свои интерфейсы workflow объявляет только для того, чего у доменов нет (`TxManager`, системное, порт внешней системы — `go-sdk/workflow.md`; `go-sdk/domain.md`, «Cross-domain зависимость»).
- **Когда использовать**: только когда операция охватывает 2+ доменов как равноправных участников. Однодоменная операция — даже многошаговая или вызываемая из нескольких точек входа (HTTP + Consumer) — Command/Query домена (`architecture/layers.md`, «Как выбирать»; G-1).

### Bootstrap / Application Layer
- **Что**: собирает приложение: создаёт зависимости, регистрирует маршруты и consumers.
- **Состав**: `app.go`, `kernel/{name}/` (своё ядро — когда нужного нет в ядрах SDK; регистрируется в `app.go` рядом с ядрами SDK), `{module}/module.go`, `{module}/config.go` (конфиг модуля из env — внешние системы, лимиты), `{module}/repositories_factory.go`, `{module}/services_factory.go`, `{module}/workflows_factory.go` (если есть workflow), `{module}/handlers_factory.go`, `{module}/consumers_factory.go`; модуль консоли — `internal/console/bootstrap/` («Консоль» в «Entrypoint Layer»).
- **Контейнер DI — только в сборке** (`architecture/layers.md`, «Правила слоёв, общие для стеков»): `a.Container` и пакеты `gosdk-*/pkg/di` — только в `internal/app/kernel/` (свои ядра), `internal/app/app.go`, `internal/console/app.go` и `module.go` модулей bootstrap, серверных и консольных; фабрики, `config.go`, `routes.go`, хендлеры, middleware и остальные слои получают зависимость аргументом. Тег `di-resolve-in-business-code`, проверяет `gocheck` (`tools/check/lint-proof/RULES.md`).
- **Маршруты модуля:** `module.go` берёт роутер из DI (`httpDI.GetRouter(a.Container)`), настраивает его, собирает auth-middleware с доменными сервисами (`adminmiddleware.JWTAuthMiddleware(adminService)`) и передаёт всё в `SetRoutes(a, router, handler, auth…)` точки входа; `a` в `SetRoutes` — только для middleware SDK (`RequestInfoMiddleware(a)`, `MetricsMiddleware(a)`). Middleware на весь роутер (CORS) — в `app.go` до регистрации модулей, один раз; настройка общего роутера (`router.SetTrustedProxies(...)`, `router.RemoteIPHeaders`) — в `module.go` каждого модуля с маршрутами, явно; значение — `Load{Name}()` модуля-владельца настройки («Одна настройка — одно место» ниже). Группа, цепочка middleware и маршруты — `go-sdk/entrypoint/http.md`, «routes.go». Эталон — `examples/tariff/internal/app/bootstrap/billing/tariff/module.go`.
- **Одна настройка — одно место:** переменную env, которую читают несколько процессов или модулей (сервис и консоль, два модуля), объявляет и читает `config.go` модуля-владельца настройки; остальные bootstrap берут значение у него, а не читают ту же переменную сами (имя, обязательность и умолчание не расходятся). Эталон — `OUTBOX_TARIFF_EVENT_MAX_ATTEMPTS` в `stacks/go/examples/tariff/internal/app/bootstrap/billing/outbox_tariff_event/config.go`: консольный bootstrap читает её через `LoadMaxAttempts()` (зависимость консоли от серверного пакета — `core/backlog.md`, D-7).
- **`config.go`** — unexported структура с тегами `mapstructure`/`json`, `load{Name}Config()` через `config.InitConfig`; настройку, которую берут другие модули, владелец отдаёт экспортированной `Load{Name}() ({тип}, error)` (`outbox_tariff_event.LoadMaxAttempts()`), структура остаётся unexported; обязательное значение пусто — ошибка, модуль не стартует; URL проверяется и нормализуется здесь (`regulations/service-requirements.md`, «Конфиг»). Таймаут в env — целые секунды (`*_TIMEOUT_SECONDS uint`), в `time.Duration` переводит метод конфига: `InitConfig` `time.Duration` из env не разбирает (значение остаётся строкой, сверено по `gosdk-core` v1.0.9, `pkg/config/env_value.go`). Эталон — `examples/tariff/internal/app/bootstrap/billing/tariff/config.go`.
- **Цепочка**: `DB Client → Repository (infra) → Service (domain) → Handler (entrypoint) → Routes`.
- **Путь**: `bootstrap/{module}/` или `bootstrap/{domain}/{module}/` — выбирается по необходимости группировки.

### pkg/ — общий код без домена
- **Что**: код, который не знает ни одного домена и нужен в нескольких местах (`core/principles.md`, «Инфраструктурный код — один раз»): преобразование формата (раздел «Тесты», строка хелпера), хелперы точек входа одного транспорта (ответ 401/503, отдача файла, middleware без бизнес-логики — `pkg/requestid`, `pkg/signature`, `pkg/requestbody` эталона), доменно-нейтральные реализации портов (часы, генератор id, хеш секрета).
- **Зависимости**: стандартная библиотека, транспорт (gin) и SDK ответа (`response.*`) — можно; `internal/**` — нет, в том числе `internal/common`. Нужен доменный тип — код принадлежит слою этого домена, не `pkg/`; middleware с доменным сервисом — `entrypoints/{context}/http/middleware/` («Middleware» выше).
- **Путь**: `pkg/{thing}/{thing}.go`, тест — рядом.

---

## Структура директорий

```
internal/
├── app/
│   ├── app.go                              ← RegisterAndInitKernels + RegisterAndInitModules
│   ├── kernel/{name}/                      ← своё ядро, если нужного нет в ядрах SDK (обычно нет)
│   └── bootstrap/
│       └── {module}/                       ← только модуль, если домен не нужен
│           ├── module.go                   ← или {domain}/{module}/, если нужна группировка
│           ├── repositories_factory.go
│           ├── services_factory.go
│           ├── workflows_factory.go        ← опционально, только сборка workflow
│           ├── config.go                   ← опционально: конфиг модуля из env
│           ├── handlers_factory.go
│           └── consumers_factory.go        ← опционально
│
├── common/                                 ← общий бизнес-словарь двух и более доменов (`architecture/layers.md`): `{понятие}_enum.go`, value-объекты, чистые функции, их ошибки; без зависимостей, I/O, DI
│
├── domains/
│   └── {domain}/
│       ├── common/                         ← общее двух и более модулей домена (`architecture/layers.md`, «Зависимости»): `{понятие}_enum.go`, value-объекты, чистые функции; импортируют только модули домена
│       └── {module}/
│           ├── entity.go
│           ├── dto.go
│           ├── repository.go               ← данные: хранилища и внешние системы как источник данных
│           ├── tx_manager.go               ← опционально: интерфейсы транзакций
│           ├── modules.go                  ← опционально: интерфейсы соседних модулей домена (O-6)
│           ├── system.go                   ← опционально: Clock, IDGenerator, ChangeNotifier…
│           ├── service.go
│           ├── enum.go                     ← опционально: константы модуля (`go-sdk/domain.md`)
│           ├── errors.go                   ← опционально: sentinel-ошибки домена (`ErrDefaultTariffTaken`)
│           ├── context.go                  ← опционально: значение домена в `context.Context` (`admin.WithAdmin`/`FromContext`)
│           ├── {действие}_command.go       ← опционально: сложное изменение (`go-sdk/domain.md`)
│           ├── {действие}_query.go         ← опционально: сложное чтение (`GetDefaultTariffQuery`, `go-sdk/domain.md`)
│           └── {entity}_validator.go       ← опционально: доменные инварианты DTO (O-18)
│
├── entrypoints/
│   └── {context}/
│       ├── http/middleware/                ← middleware контекста: `{назначение}.go`, функции `{Name}Middleware`
│       ├── http/{domain}/{module}/
│       │   ├── handler.go
│       │   ├── routes.go
│       │   ├── request.go
│       │   ├── response.go
│       │   └── mapper.go
│       ├── rabbit/{domain}/{module}/
│       │   ├── consumer_registry.go
│       │   └── {module}_consumer.go
│       ├── ws/{domain}/{module}/           ← WebSocket: файлы как у HTTP + `session.go`
│       ├── {транспорт}/{domain}/{module}/  ← другой транспорт (pub/sub, тикер)
│       └── console/{domain}/{module}/      ← только {context} = system
│           └── {module}_command.go
│
├── console/                                ← консольное приложение: `app.go` (ядра, `Close`), `migrate.go` (команда `migrate`)
│   └── bootstrap/{domain}/{module}/        ← модуль консоли: `module.go` (поле `Console`), `commands_factory.go`, фабрики
│
├── migrations/
│   ├── registry.go                         ← All()
│   └── {YYYYMMDDHHMMSS}_{что_делает}.go
│
├── infrastructure/
│   ├── postgres/{domain}/{module}/
│   │   ├── model.go
│   │   ├── repository.go
│   │   └── mapper.go
│   ├── redis/{domain}/{module}/              ← кеш хранит модель, сущность собирает маппер (состояние сущности неэкспортируемое)
│   │   ├── model.go
│   │   ├── repository.go
│   │   └── mapper.go
│   ├── http/{provider}/{product}/            ← внешний провайдер (kaspi/autopay, webkassa)
│   │   ├── model.go
│   │   ├── repository.go                     ← HTTPRepository (источник данных)
│   │   ├── notifier.go                       ← HTTPNotifier (порт отправки; architecture/integrations.md)
│   │   └── mapper.go
│   ├── http/{service}/{module}/              ← наш другой микросервис
│   ├── rabbit/{domain}/{module}/             ← публикатор: `publisher.go` (`RabbitPublisher`, `go-sdk/entrypoint/rabbit.md`), `publisher_definition.go`, `model.go`, `mapper.go`
│   └── {tech}/{domain}/{module}/             ← другая технология: `jwt/identity/admin/verifier.go` (проверка токена — порт домена)
│
└── workflows/                                  ← опционально, отдельный слой (не внутри infrastructure)
    └── {domain}/
        └── {module}/
            ├── {конкретное_действие}_workflow.go
            ├── tx_manager.go                   ← если workflow пишет больше чем одним запросом (O-8)
            ├── repository.go                   ← опционально: порт внешней системы (данные), которого нет в домене
            ├── system.go                       ← опционально: Clock, IDGenerator, порт отправки…
            └── dto.go
```

**Bootstrap путь**: `bootstrap/{module}/` если модулей мало или домен не нужен; `bootstrap/{domain}/{module}/` если нужна группировка по домену. Выбор определяется удобством навигации, не правилом. Все остальные слои всегда используют `{domain}/{module}/`.

---

## Структура путей

| Слой | Путь |
|---|---|
| Domain | `internal/domains/{domain}/{module}/` |
| Common домена (общее модулей одного домена) | `internal/domains/{domain}/common/` |
| Common (общий бизнес-словарь) | `internal/common/` — `{понятие}_enum.go`, value-объекты, чистые функции |
| Workflow | `internal/workflows/{domain}/{module}/` |
| Entrypoint HTTP | `internal/entrypoints/{context}/http/{domain}/{module}/` |
| Entrypoint служебный (health, ready, metrics) | `internal/entrypoints/system/http/{name}/` (решение G-4) |
| Entrypoint RabbitMQ | `internal/entrypoints/{context}/rabbit/{domain}/{module}/` (`{context}` ≠ `internal`) |
| Entrypoint WebSocket | `internal/entrypoints/{context}/ws/{domain}/{module}/` |
| Entrypoint другого транспорта | `internal/entrypoints/{context}/{транспорт}/{domain}/{module}/` |
| Entrypoint консоль | `internal/entrypoints/system/console/{domain}/{module}/` |
| Консоль: сборка и модули | `internal/console/` (`app.go`, `migrate.go`), `internal/console/bootstrap/{domain}/{module}/` |
| Миграции | `internal/migrations/` |
| Infrastructure Postgres | `internal/infrastructure/postgres/{domain}/{module}/` |
| Infrastructure Redis | `internal/infrastructure/redis/{domain}/{module}/` |
| Infrastructure HTTP (провайдер) | `internal/infrastructure/http/{provider}/{product}/` |
| Infrastructure HTTP (наш сервис) | `internal/infrastructure/http/{service}/{module}/` |
| Infrastructure другой технологии | `internal/infrastructure/{tech}/{domain}/{module}/` |
| Bootstrap | `internal/app/bootstrap/{module}/` или `internal/app/bootstrap/{domain}/{module}/` |
| Общий код без домена | `pkg/{thing}/` |

---

## App жизненный цикл

```
main()
  └── NewApp()
        ├── RegisterAndInitKernels(...)
        │     ├── PostgresKernel.Init()  → подключение к БД, регистрация в DI
        │     ├── HttpKernel.Init()      → создание Gin роутера, регистрация в DI
        │     └── RabbitKernel.Init()    → AMQP соединение, регистрация registry в DI
        └── RegisterAndInitModules(...)
              └── Module.Init()
                    ├── Создание factory цепочки
                    ├── Регистрация HTTP маршрутов
                    └── Регистрация RabbitMQ consumers
  └── RunAll()
  └── WaitForShutdown()
        └── Ожидание SIGINT/SIGTERM → graceful shutdown всех Kernels
```

**Зависимости модулей bootstrap.** Модуль может взять у другого модуля только готовое и только одним из двух способов:
- **объект через DI** — модуль-владелец в `Init` регистрирует сервис или настройки (`di.Register(a.Container, servicesFactory.AdminService)`), модуль-потребитель в своём `Init` берёт их `di.Resolve[*admin.Service](a.Container)` и передаёт в фабрики аргументами. Владелец стоит в `RegisterAndInitModules` в `app.go` **раньше** потребителя (`&healthcheck.Module{}, &auth.Module{}, &tariff.Module{}`), иначе `Resolve` падает при старте;
- **значение настройки** — экспортированная `Load{Name}()` из `config.go` владельца (импорт пакета bootstrap владельца, только ради неё).

Фабрики, конфиг-структуры и внутренности чужого модуля не импортируются, взаимных зависимостей (A берёт у B, B — у A) нет. Модуль, который только раздаёт сервисы (без маршрутов), — нормальная форма: `bootstrap/identity/auth/module.go` эталона. Эталон потребителя — `examples/tariff/internal/app/bootstrap/billing/tariff/module.go`.

---

## Cross-domain: правила выбора подхода

Таблица выбора подхода — `architecture/cross-domain.md`, «Выбор подхода»; однодоменная многошаговая операция — Command/Query домена (G-1, «Workflow Layer» выше).

Прямой зависимости домена от чужого домена нет (O-7, `architecture/cross-domain.md`): сценарий на 2+ домена — workflow, он берёт репозитории доменов интерфейсами, сервисы — конкретным типом или своим интерфейсом (`go-sdk/workflow.md`; `go-sdk/domain.md`, «Cross-domain зависимость»).

---

## Checklist нового модуля

**Обязательно:**
- [ ] `domains/{domain}/{module}/entity.go`
- [ ] `domains/{domain}/{module}/repository.go`
- [ ] `domains/{domain}/{module}/service.go`
- [ ] `infrastructure/postgres/{domain}/{module}/model.go`
- [ ] `infrastructure/postgres/{domain}/{module}/repository.go`
- [ ] `infrastructure/postgres/{domain}/{module}/mapper.go`
- [ ] `entrypoints/{context}/http/{domain}/{module}/handler.go`
- [ ] `entrypoints/{context}/http/{domain}/{module}/routes.go`
- [ ] `entrypoints/{context}/http/{domain}/{module}/request.go`
- [ ] `entrypoints/{context}/http/{domain}/{module}/response.go`
- [ ] `entrypoints/{context}/http/{domain}/{module}/mapper.go`
- [ ] `app/bootstrap/{module}/repositories_factory.go`
- [ ] `app/bootstrap/{module}/services_factory.go`
- [ ] `app/bootstrap/{module}/handlers_factory.go`
- [ ] `app/bootstrap/{module}/module.go`
- [ ] Зарегистрировать `Module` в `internal/app/app.go`

**Опционально:**
- [ ] `domains/{domain}/{module}/{entity}_validator.go` — если есть проверки значений входа сверх формата запроса (O-18); правила состояния и переходов — методы сущности в `entity.go` (`regulations/entity.md`, O-26)
- [ ] `domains/{domain}/{module}/modules.go`, `system.go` — если модулю нужны соседние модули домена или системные зависимости (`conventions.md` п. 12); транзакции — `tx_manager.go`
- [ ] `infrastructure/redis/{domain}/{module}/model.go`, `repository.go`, `mapper.go`
- [ ] `infrastructure/http/{provider}/{product}/model.go` (или `http/{service}/{module}/`)
- [ ] `infrastructure/http/{provider}/{product}/repository.go`
- [ ] `infrastructure/http/{provider}/{product}/mapper.go`
- [ ] `entrypoints/{context}/rabbit/{domain}/{module}/{module}_consumer.go`
- [ ] `entrypoints/{context}/rabbit/{domain}/{module}/consumer_registry.go`
- [ ] `app/bootstrap/{module}/consumers_factory.go`

**Cross-domain Workflow:**
- [ ] `internal/workflows/{domain}/{module}/{конкретное_действие}_workflow.go`
- [ ] `internal/workflows/{domain}/{module}/dto.go`
- [ ] `internal/workflows/{domain}/{module}/tx_manager.go` — если workflow делает больше одного модифицирующего запроса (O-8); реализация `dbtransaction.NewManager*` в `repositories_factory.go`
- [ ] `internal/workflows/{domain}/{module}/repository.go`, `system.go` — если нужен порт внешней системы, которого нет в домене, или системные зависимости (`go-sdk/workflow.md`)
- [ ] `app/bootstrap/{module}/workflows_factory.go` (не `services_factory.go`)
- [ ] Зарегистрировать workflow через DI в `module.go`

---

## Тесты (решение O-3)

Что и как тестировать — `approaches/process/testing.md`; здесь — где лежит.

| Что | Где | Пример |
|---|---|---|
| unit | рядом с кодом: `{file}_test.go` | `domains/billing/tariff/service_test.go` |
| фикстуры | `testdata/` рядом с тестом | `infrastructure/jwt/identity/admin/testdata/` |
| in-memory фейки репозиториев | рядом с тестом; общие для нескольких пакетов — `internal/testsupport/{domain}fakes/` | — |
| хелпер преобразования формата (телефон, ИИН, номер карты, деньги, маска) — чистые функции над примитивами, без доменных типов и зависимостей | сначала SDK (`core/pkg/validation`: `NormalizePhoneNumber`, `CheckValidPhone`); нет в SDK — `pkg/{thing}/{thing}.go`, `{thing}_test.go` рядом; вызов — из маппера хендлера/consumer'а и Response-маппера, не из сервиса и репозитория (`core/principles.md`, «Повторяющаяся обработка — один хелпер»). Это про преобразование формата; транспортный хелпер `pkg/` (`pkg/requestid` — заголовок Request ID из `ctx`; `pkg/signature` — разбор и проверка HMAC-подписи заголовка `X-Signature`, `examples/tariff/pkg/signature/signature.go`; `pkg/requestbody` — чтение тела не больше предела с возвратом в запрос, больше — 413, `examples/tariff/pkg/requestbody/requestbody.go`) — чистые функции над байтами и заголовками, без доменных типов; вызывают middleware точек входа (`integration/http/middleware/signature_auth.go`) и адаптеры Infrastructure, не сервис | `duplicated-format-logic` |
| тест границ `Common` | `internal/common/boundary_test.go`: разбирает файлы `internal/common/...` (`go/parser`, без новых зависимостей) и падает на импорте не из стандартной библиотеки и не из `internal/common/` (`approaches/process/testing.md`); `Common` домена — тот же тест в `internal/domains/{domain}/common/boundary_test.go`, разрешён ещё сам пакет | — |
| интеграционные (живая БД/Redis) | рядом с кодом, с `//go:build integration` в первой строке | запуск: `go test -tags integration ./...` |
| e2e (сервис целиком по HTTP) | `tests/<сценарий>/` в корне | `tests/kaspi/`, `tests/halyk/` |

Интеграционный тест без build tag — `integration-test-without-build-tag`: иначе `go test ./...` падает без поднятой инфраструктуры.
