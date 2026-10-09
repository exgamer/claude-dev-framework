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

**Допустимые зависимости:**
```
Entrypoint     ──→  Workflow / Domain
Workflow       ──→  Domain / Infrastructure
Infrastructure ──→  Domain
Bootstrap      ──→  Entrypoint + Workflow + Infrastructure + Domain
```

**Запрещённые зависимости (нарушение = ошибка ревью):**
```
Domain         → Infrastructure    ✗
Domain         → Entrypoint        ✗
Domain         → Workflow          ✗
Workflow       → Entrypoint        ✗
Entrypoint     → Bootstrap         ✗
Infrastructure → Entrypoint        ✗
```

---

## Ответственность слоёв

### Domain Layer
- **Что**: бизнес-логика. Не зависит от БД, HTTP, любых внешних систем.
- **Состав**: `entity.go` (доменная модель без ORM/JSON тегов), `repository.go` (интерфейс), `service.go` (бизнес-операции через интерфейс репозитория).
- **Правило**: domain объявляет интерфейс Repository сам; infrastructure его реализует.

### Infrastructure Layer
- **Что**: реализации интеграций с внешними системами.
- **Состав**: `model.go` (GORM/JSON модель), `repository.go` (реализует доменный интерфейс), `mapper.go` (Model ↔ Domain entity).
- **Правило**: mapper обязателен на каждом слое; прямых зависимостей структур между слоями нет.

### Entrypoint Layer
- **Что**: взаимодействие с внешним миром: HTTP и RabbitMQ.
- **Состав**: `handler.go`, `routes.go`, `request.go`, `response.go`, `mapper.go`, `{module}_consumer.go`, `consumer_registry.go`.
- **Порядок в handler**: валидация → маппинг Request→Domain → вызов сервиса → маппинг Domain→Response → HTTP ответ через `response.*`.
- **`{context}`** — аудитория или точка входа API (пример: `admin`, `client`, `public`, `internal`, `partner`). Служебные эндпойнты (health, ready, metrics) — контекст `system`, с транспортом в пути: `entrypoints/system/http/healthcheck` (решение G-4); `entrypoints/system/healthcheck` без `http/` — старая форма. Определяется тем, *для кого* предназначен эндпойнт. Один эндпойнт для разных аудиторий — разные директории.

### Workflow Layer *(опциональный)*
- **Что**: оркестрирует несколько доменных сервисов для бизнес-процесса, не принадлежащего ни одному домену.
- **Состав**: `{конкретное_действие}_workflow.go` (оркестратор), `dto.go` (Input/Result), `repository.go` (опционально — если workflow требует специфических запросов).
- **Правила**: зависит от Domain; не знает про HTTP/Gin/очереди; может иметь собственный репозиторий если логика принадлежит процессу.
- **Когда использовать**: операция охватывает 2+ доменов как равноправных участников; бизнес-процесс из нескольких шагов; нужно переиспользовать из разных точек входа (HTTP + Consumer).

### Bootstrap / Application Layer
- **Что**: собирает приложение: создаёт зависимости, регистрирует маршруты и consumers.
- **Состав**: `app.go`, `{module}/module.go`, `{module}/repositories_factory.go`, `{module}/services_factory.go`, `{module}/workflows_factory.go` (если есть workflow), `{module}/handlers_factory.go`, `{module}/consumers_factory.go`.
- **Цепочка**: `DB Client → Repository (infra) → Service (domain) → Handler (entrypoint) → Routes`.
- **Путь**: `bootstrap/{module}/` или `bootstrap/{domain}/{module}/` — выбирается по необходимости группировки.

---

## Структура директорий

```
internal/
├── app/
│   ├── app.go                              ← RegisterAndInitKernels + RegisterAndInitModules
│   └── bootstrap/
│       └── {module}/                       ← только модуль, если домен не нужен
│           ├── module.go                   ← или {domain}/{module}/, если нужна группировка
│           ├── repositories_factory.go
│           ├── services_factory.go
│           ├── workflows_factory.go        ← опционально, только сборка workflow
│           ├── handlers_factory.go
│           └── consumers_factory.go        ← опционально
│
├── domains/
│   └── {domain}/
│       └── {module}/
│           ├── entity.go
│           ├── dto.go
│           ├── repository.go
│           ├── service.go
│           └── {entity}_validator.go       ← опционально: доменные инварианты DTO (O-18)
│
├── entrypoints/
│   └── {context}/
│       ├── http/{domain}/{module}/
│       │   ├── handler.go
│       │   ├── routes.go
│       │   ├── request.go
│       │   ├── response.go
│       │   └── mapper.go
│       └── rabbit/{domain}/{module}/
│           ├── consumer_registry.go
│           └── {module}_consumer.go
│
└── infrastructure/
    ├── postgres/{domain}/{module}/
    │   ├── model.go
    │   ├── repository.go
    │   └── mapper.go
    ├── redis/{domain}/{module}/
    │   └── repository.go
    ├── http/{provider}/{product}/            ← внешний провайдер (kaspi/autopay, webkassa)
    │   ├── model.go
    │   ├── repository.go
    │   └── mapper.go
    ├── http/{service}/{module}/              ← наш другой микросервис
    └── {tech}/{domain}/{module}/             ← другая технология (jwt/identity/admin)

    └── workflows/                              ← опционально
        └── {domain}/
            └── {module}/
                ├── {конкретное_действие}_workflow.go
                ├── tx_manager.go               ← если workflow пишет (O-8)
                └── dto.go
```

**Bootstrap путь**: `bootstrap/{module}/` если модулей мало или домен не нужен; `bootstrap/{domain}/{module}/` если нужна группировка по домену. Выбор определяется удобством навигации, не правилом. Все остальные слои всегда используют `{domain}/{module}/`.

---

## Структура путей

| Слой | Путь |
|---|---|
| Domain | `internal/domains/{domain}/{module}/` |
| Workflow | `internal/workflows/{domain}/{module}/` или `internal/workflows/{module}/` |
| Entrypoint HTTP | `internal/entrypoints/{context}/http/{domain}/{module}/` |
| Entrypoint служебный (health, ready, metrics) | `internal/entrypoints/system/http/{name}/` (решение G-4) |
| Entrypoint RabbitMQ | `internal/entrypoints/{context}/rabbit/{domain}/{module}/` |
| Infrastructure Postgres | `internal/infrastructure/postgres/{domain}/{module}/` |
| Infrastructure Redis | `internal/infrastructure/redis/{domain}/{module}/` |
| Infrastructure HTTP (провайдер) | `internal/infrastructure/http/{provider}/{product}/` |
| Infrastructure HTTP (наш сервис) | `internal/infrastructure/http/{service}/{module}/` |
| Infrastructure другой технологии | `internal/infrastructure/{tech}/{domain}/{module}/` |
| Bootstrap | `internal/app/bootstrap/{module}/` или `internal/app/bootstrap/{domain}/{module}/` |

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

**Порядок регистрации модулей важен**: модули не должны зависеть друг от друга.

---

## Cross-domain: правила выбора подхода

| Ситуация                           | Подход                             |
|------------------------------------|------------------------------------|
| Сервису A нужны данные из домена B | Workflow (берёт репозитории/сервисы B, передаёт данные сервису A) |
| Валидация по данным другого домена | Workflow                           |
| Операция охватывает 2+ доменов     | Workflow                           |
| Бизнес-процесс из нескольких шагов | Workflow                           |

Прямой зависимости домена от чужого домена нет (O-7, `architecture/cross-domain.md`): сценарий на 2+ домена — workflow, он берёт интерфейсы репозиториев/сервисов доменов напрямую (`go-sdk/domain.md`, «Cross-domain зависимость»).

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
- [ ] `domains/{domain}/{module}/{entity}_validator.go` — если есть доменные инварианты сверх формата запроса (O-18)
- [ ] `infrastructure/redis/{domain}/{module}/repository.go`
- [ ] `infrastructure/http/{provider}/{product}/model.go` (или `http/{service}/{module}/`)
- [ ] `infrastructure/http/{provider}/{product}/repository.go`
- [ ] `infrastructure/http/{provider}/{product}/mapper.go`
- [ ] `entrypoints/{context}/rabbit/{domain}/{module}/{module}_consumer.go`
- [ ] `entrypoints/{context}/rabbit/{domain}/{module}/consumer_registry.go`
- [ ] `app/bootstrap/{module}/consumers_factory.go`

**Cross-domain Workflow:**
- [ ] `internal/workflows/{domain}/{module}/{конкретное_действие}_workflow.go`
- [ ] `internal/workflows/{domain}/{module}/dto.go`
- [ ] `internal/workflows/{domain}/{module}/tx_manager.go` — если workflow пишет (O-8); реализация `dbtransaction.NewManager*` в `repositories_factory.go`
- [ ] `app/bootstrap/{module}/workflows_factory.go` (не `services_factory.go`)
- [ ] Зарегистрировать workflow через DI в `module.go`

---

## Тесты (решение O-3)

| Что | Где | Пример |
|---|---|---|
| unit | рядом с кодом: `{file}_test.go` | `domains/billing/tariff/service_test.go` |
| фикстуры | `testdata/` рядом с тестом | `infrastructure/jwt/identity/admin/testdata/` |
| in-memory фейки репозиториев | рядом с тестом; общие для нескольких пакетов — `internal/testsupport/{domain}fakes/` | — |
| интеграционные (живая БД/Redis) | рядом с кодом, с `//go:build integration` в первой строке | запуск: `go test -tags integration ./...` |
| e2e (сервис целиком по HTTP) | `tests/<сценарий>/` в корне | `tests/kaspi/`, `tests/halyk/` |

Интеграционный тест без build tag — **[ВНИМАНИЕ]**: иначе `go test ./...` падает без поднятой инфраструктуры.
