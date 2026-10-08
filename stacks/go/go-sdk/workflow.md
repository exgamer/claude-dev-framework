# Workflow слой

Путь: `internal/workflows/{domain}/{module}/` или `internal/workflows/{module}/` — зависит от ситуации.

Примеры: `internal/workflows/billing/tariff/`, `internal/workflows/billing/session/`, `workflows/tariff/`

## Правила

- Workflow оркестрирует несколько доменов — если логика в рамках одного домена, использовать Command/Query в домене
- Может содержать сложную бизнес-логику если она требует координации нескольких доменов
- Получает зависимости через конструктор (domain Repository/CacheRepository напрямую, не через сервис)
- Может объявлять собственные контракты репозитория (`repository.go`) если workflow требует специфических запросов
- Не знает о HTTP, entrypoint и инфраструктуре
- Один workflow — один сценарий использования; несколько операций не совмещать
- Точка входа всегда называется `Exec`
- Зависимости только через интерфейсы — не принимать конкретные реализации (`*PostgresRepository`)
- Бизнес-ошибки через `AppException` (см. `exceptions.md`)
- Тест рядом с файлом: `{конкретное_действие}_workflow_test.go`
- **Транзакционен по умолчанию** — `TxManager` в `tx_manager.go` рядом с workflow; правило — `approaches/patterns/transactions.md` (O-8), механика — `infra/postgres.md`, пример — `examples/tariff/internal/workflows/billing/tariff/`

## Именование файлов

| Что | Файл |
|-----|------|
| Оркестратор | `{конкретное_действие}_workflow.go` |
| Входные/выходные данные | `dto.go` |
| Интерфейс транзакции | `tx_manager.go` |

Имя файла — конкретное действие + суффикс `_workflow`: `calculate_session_debt_workflow.go`, не `workflow.go` и не `calculate_session_debt.go` (регламент `BACKEND_ARCHITECT-GO`, решение O-1 в `core/decisions.md`).

Суффикс `_command`/`_query` в слое workflow не используется (решение G-1): междоменный сценарий — `*_workflow.go` (`sync_outbox_event_command.go` → `sync_outbox_event_workflow.go`), однодоменная большая операция — это Command/Query домена (`go-sdk/domain.md`), её место не в `workflows/`.

Собирается в `internal/app/bootstrap/{module}/workflows_factory.go`, не в `services_factory.go`.

## dto.go

```go
package tariff

type CalculateSessionDebtParams struct {
    ParkingId    uint
    LicensePlate string
}
```

## Шаблон workflow

```go
package tariff

type CalculateSessionDebt struct {
    tariffRepository       tariffdomain.Repository
    sessionRepository      sessiondomain.Repository
    tariffCacheRepository  tariffdomain.CacheRepository
    sessionCacheRepository sessiondomain.CacheRepository
}

func NewCalculateSessionDebt(
    tariffRepository tariffdomain.Repository,
    sessionRepository sessiondomain.Repository,
    tariffCacheRepository tariffdomain.CacheRepository,
    sessionCacheRepository sessiondomain.CacheRepository,
) *CalculateSessionDebt {
    return &CalculateSessionDebt{
        tariffRepository:       tariffRepository,
        sessionRepository:      sessionRepository,
        tariffCacheRepository:  tariffCacheRepository,
        sessionCacheRepository: sessionCacheRepository,
    }
}

func (w *CalculateSessionDebt) Exec(ctx context.Context, params *CalculateSessionDebtParams) (*int, error) {
    // координация между доменами
}
```

## Отличие от Command/Query

| | Command/Query | Workflow |
|---|---|---|
| Расположение | `internal/domains/{domain}/` | `workflows/{domain}/{module}/` |
| Область | один домен | несколько доменов |
| Метод | `Exec` | `Exec` |
