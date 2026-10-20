# Workflow слой

Путь: `internal/workflows/{domain}/{module}/` (как все слои, кроме Bootstrap — `structure.md`, «Структура директорий»; эталон — `../examples/tariff/internal/workflows/billing/tariff/`).

Примеры: `internal/workflows/billing/tariff/` (эталон `stacks/go/examples/tariff`); проект без доменов — `internal/workflows/tariff/`

## Правила

- Workflow оркестрирует несколько доменов — если логика в рамках одного домена, использовать Command/Query в домене
- Может содержать сложную бизнес-логику если она требует координации нескольких доменов
- Получает через конструктор интерфейсы репозиториев (Repository/CacheRepository) и сервисов доменов напрямую — что нужно сценарию (O-7); внутри `txManager.Exec` — только репозитории из аргументов, сервис пишет мимо транзакции (O-8, `infra/postgres.md`)
- **Самостоятелен** — не вызывает другие workflow (`workflow-calls-workflow`), содержит свою логику агрегата, общая логика двух workflow — по доменам: `architecture/layers.md`, «Service, Command/Query, Workflow» → «Зависимости»; хелперы — `../review-findings.md`, «Workflow самодостаточен»
- Таблицы БД — всегда домену (`architecture/layers.md`, «Зависимости»); в Go тег нарушения — `service-direct-db-query` (`../review-checks.md`)
- **Свои интерфейсы-зависимости — как у сервиса домена**, в файлах групп (`../conventions.md` п. 12): `tx_manager.go` — транзакции, `repository.go` — порт внешней системы как источник данных, `system.go` — системное и порт отправки (SMS, события) — по критерию п. 12. Условия (не дублировать домен, перенос при втором потребителе, типы порта, вызов вне транзакции, `workflow-duplicates-domain-port`) — `architecture/layers.md`, «Зависимости»; в Go: перенос — в `repository.go` или `system.go` домена по п. 12, типы — из домена или `internal/common`, не из `infrastructure/` и не из `dto.go` workflow
- Не знает о HTTP, entrypoint и инфраструктуре: внешняя система — только через интерфейс-порт, реализация — адаптер в Infrastructure (`architecture/integrations.md`)
- Один workflow — один сценарий использования; несколько операций не совмещать
- Точка входа всегда называется `Exec`
- Инфраструктура — только через интерфейсы (репозитории, порты внешних систем, транзакции): конкретные реализации (`*PostgresRepository`, `*HTTPRepository`) не принимать. Сервисы и Command доменов можно принимать конкретным типом (`*tariff.Service`) — интерфейс объявляет потребитель, только когда он нужен (подмена в тесте, развязка): несколько реализаций бизнес-логики бывают редко, интерфейс «на всякий случай» не заводится, `conventions.md` п. 10–11
- Бизнес-ошибки через `AppException` (см. `exceptions.md`)
- Тест рядом с файлом: `{конкретное_действие}_workflow_test.go`
- **Транзакция — когда сценарий делает больше одного модифицирующего запроса**, один запрос — без неё (кроме транзакции ради блокировки `FOR UPDATE`/`FOR SHARE` — основание «Конкурентность» `design.md`); проверки до записи в транзакцию не переносятся, гонку закрывает `approaches/patterns/concurrency.md`. `TxManager` — в `tx_manager.go` рядом с workflow; правило — `approaches/patterns/transactions.md` (O-8), механика — `infra/postgres.md`, пример — `examples/tariff/internal/workflows/billing/tariff/`

## Именование файлов

| Что | Файл |
|-----|------|
| Оркестратор | `{конкретное_действие}_workflow.go` |
| Входные/выходные данные | `dto.go` |
| Интерфейс транзакции | `tx_manager.go` |
| Порт внешней системы как источник данных (если его нет в домене) | `repository.go` |
| Системные зависимости и порт отправки (`Clock`, `IDGenerator`, SMS) | `system.go` |

Имя файла — конкретное действие + суффикс `_workflow`: `calculate_session_debt_workflow.go`, не `workflow.go` и не `calculate_session_debt.go` (регламент `BACKEND_ARCHITECT-GO`, решение 2026-10-07 в `core/decisions-log.md`).

Суффикс `_command`/`_query` в слое workflow не используется (решение G-1): междоменный сценарий — `*_workflow.go` (`create_tariff_command.go` → `create_tariff_workflow.go`), однодоменная большая операция — это Command/Query домена (`go-sdk/domain.md`), её место не в `workflows/`; доставка outbox — Command домена outbox (`approaches/patterns/outbox.md`).

Собирается в `internal/app/bootstrap/{module}/workflows_factory.go`, не в `services_factory.go`.

## dto.go

```go
package tariff

type CalculateSessionDebtParams struct {
	ParkingID    uint
	LicensePlate string
}
```

## Шаблон workflow

Тип — конкретное действие + суффикс `Workflow`, конструктор — `New{Действие}Workflow` (эталон — `CreateTariffWorkflow`, `../examples/tariff/internal/workflows/billing/tariff/create_tariff_workflow.go`).

```go
package tariff

type CalculateSessionDebtWorkflow struct {
	tariffRepository       tariffdomain.Repository
	sessionRepository      sessiondomain.Repository
	tariffCacheRepository  tariffdomain.CacheRepository
	sessionCacheRepository sessiondomain.CacheRepository
}

func NewCalculateSessionDebtWorkflow(
	tariffRepository tariffdomain.Repository,
	sessionRepository sessiondomain.Repository,
	tariffCacheRepository tariffdomain.CacheRepository,
	sessionCacheRepository sessiondomain.CacheRepository,
) *CalculateSessionDebtWorkflow {
	return &CalculateSessionDebtWorkflow{
		tariffRepository:       tariffRepository,
		sessionRepository:      sessionRepository,
		tariffCacheRepository:  tariffCacheRepository,
		sessionCacheRepository: sessionCacheRepository,
	}
}

func (w *CalculateSessionDebtWorkflow) Exec(ctx context.Context, params *CalculateSessionDebtParams) (*int, error) {
	// координация между доменами
}
```

## Отличие от Command/Query

| | Command/Query | Workflow |
|---|---|---|
| Расположение | `internal/domains/{domain}/` | `workflows/{domain}/{module}/` |
| Область | один домен | несколько доменов |
| Метод | `Exec` | `Exec` |
