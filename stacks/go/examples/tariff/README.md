# Эталонный модуль Go: тариф

Один модуль целиком, написанный строго по правилам фреймворка. Это **образец формы**: при сомнении, как оформить файл, смотреть сюда, а детали (имена полей, бизнес-правила) брать из соседнего модуля проекта.

- Модуль Go — заглушка `example.com/parking-service`. Пути SDK — `git.mpinnovations.kz/mps/go-packages/gosdk-*`; **версию SDK на задаче всё равно спросить** (`conventions.md`, п. 23).
- Проверено 2026-10-07: `go build`, `go vet`, `go test` против gosdk-core v1.0.3, gosdk-db-core v1.0.3, gosdk-http-core v1.0.7, gosdk-postgres-core / gosdk-redis-core из `go.mod` parking-session-service; домены `handbook/parking`, `identity/admin` и middleware `JwtAuth` подставлялись заглушками (в примере их нет — считаются существующими).
- При изменении правила или SDK — поправить пример и перепроверить сборку.

## Что где и какое правило показывает

| Файл | Что показывает |
|---|---|
| `domains/billing/tariff/entity.go` | сущность без тегов, doc-комментарий на каждом поле |
| `domains/billing/tariff/dto.go` | `Search` для списка; `Patch` с указателями — частичное обновление через DTO, не map колонок |
| `domains/billing/tariff/repository.go` | `Repository` + `CacheRepository` в одном файле, `nil, nil` при отсутствии |
| `domains/billing/tariff/service.go` | Service: cache-aside inline, ошибки кеша → `errorreporter.CaptureSoft`, `NewNotFoundException` |
| `domains/billing/tariff/set_default_tariff_command.go` | **Command** — та же логика домена, вынесенная из сервиса из-за размера: 2 записи в транзакции + сброс кеша |
| `domains/billing/tariff/tx_manager.go` | интерфейс транзакции объявляет потребитель, в своём файле |
| `workflows/billing/tariff/create_tariff_workflow.go` | **Workflow** — междоменная логика (billing + handbook), `*_workflow.go`, метод `Exec` |
| `workflows/billing/tariff/create_tariff_workflow_test.go` | unit-тест рядом с кодом, in-memory фейки |
| `testsupport/billingfakes`, `testsupport/handbookfakes` | общие фейки репозиториев для тестов нескольких пакетов |
| `infrastructure/postgres/billing/tariff/` | `model.go` (unexported, `TableName`), `mapper.go` (nil-check, `patchToColumns`), `repository.go` (timeout на каждый запрос, `NewGormPaginatedHelper`, обёртка ошибки с ID) |
| `infrastructure/redis/billing/tariff/repository.go` | `rediscore.NewHelper`, TTL константой, `Unlink` для инвалидации |
| `entrypoints/admin/http/billing/tariff/` | контекст `admin`; `request.go` (embed `validation.Request`, лимиты), `response.go` (embed `structures.Response[T]`), `mapper.go`, тонкий `handler.go` со Swagger, `routes.go` с middleware SDK |
| `app/bootstrap/billing/tariff/` | `repositories_factory.go` (+ `dbtransaction.NewManager`), `services_factory.go` (сервисы и Command домена), `workflows_factory.go` (только workflow), `handlers_factory.go`, `module.go` |
