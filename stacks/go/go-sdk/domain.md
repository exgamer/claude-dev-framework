# Domain слой

Путь: `internal/domains/{domain}/{module}/`

Примеры: `internal/domains/billing/tariff/`, `internal/domains/billing/session/`

## Правила

- Никаких внешних зависимостей — без GORM тегов, без JSON тегов, без HTTP
- Ошибки только пробрасываются вверх (`return nil, err`); HTTP-ответы формирует только entrypoint слой
- Нет вложенных пакетов с потерей контекста (например `services/dto`)
- Все DTO домена в одном `dto.go`; разбивать по файлам (`create_dto.go`, `update_dto.go`) только если файл вырастает до ~100+ строк

## Именование файлов

| Что | Файл |
|-----|------|
| Доменная сущность | `entity.go` |
| Интерфейсы репозиториев | `repository.go` |
| Сервис | `service.go` |
| Константы (используются в нескольких местах) | `{name}_enum.go` |
| DTO операций | `dto.go` |
| Сложная бизнес-логика изменения | `{конкретное_действие}_command.go` |
| Сложная бизнес-логика чтения | `{конкретное_действие}_query.go` |

## entity.go

```go
package product

type Product struct {
    ID         uint
    Name       string
    Price      float64
    CategoryID uint
    Status     int
}
```

## dto.go

```go
package product

type Search struct {
    ID         uint
    Name       string
    CategoryID uint
    Page       uint
    PerPage    uint
}
```

## repository.go — только интерфейсы

Все контракты репозитория лежат в одном файле `repository.go`. Если домен использует несколько хранилищ (БД, кеш, внешний API) — каждый контракт отдельным интерфейсом в том же файле:

```go
package product

import (
    "context"
    "github.com/exgamer/gosdk-db-core/pkg/query/pagination"
)

type Repository interface {
    Paginated(ctx context.Context, s *Search) (*pagination.Paginated[Product], error)
    GetById(ctx context.Context, id uint) (*Product, error)
    Create(ctx context.Context, m *Product) (*Product, error)
    Update(ctx context.Context, m *Product) error
    Delete(ctx context.Context, id uint) error
}

type CacheRepository interface {
    Set(ctx context.Context, m *Product) error
    GetById(ctx context.Context, id uint) (*Product, error)
    Invalidate(ctx context.Context, id uint) error
}
```

Методы репозитория именуются произвольно — названия в примере условные.

Метод возвращает `nil, nil` если запись не найдена — не ошибку.

## service.go

```go
package product

import (
    "context"
    "github.com/exgamer/gosdk-db-core/pkg/query/pagination"
)

type Service struct {
    repository Repository
}

func NewService(repository Repository) *Service {
    return &Service{repository: repository}
}

func (s *Service) GetById(ctx context.Context, id uint) (*Product, error) {
    return s.repository.GetById(ctx, id)
}

func (s *Service) Paginated(ctx context.Context, search *Search) (*pagination.Paginated[Product], error) {
    return s.repository.Paginated(ctx, search)
}

func (s *Service) Create(ctx context.Context, m *Product) (*Product, error) {
    return s.repository.Create(ctx, m)
}

func (s *Service) Update(ctx context.Context, m *Product) (*Product, error) {
    if err := s.repository.Update(ctx, m); err != nil {
        return nil, err
    }
    return m, nil
}
```

## status_enum.go — константы

Константы, которые используются в нескольких местах (хендлер, сервис, репозиторий, тесты), выносятся в отдельный файл `{name}_enum.go`:

```go
package product

const (
    StatusActive   = 1
    StatusInactive = 2
    StatusDeleted  = 3
)
```

Все константы одного контекста в одном файле. Разбивать по файлам только если контексты разные: `status_enum.go`, `type_enum.go`.

Локальные константы, нужные только внутри одного файла, можно объявить там же.

## Command / Query — сложная бизнес-логика

Если метод сервиса вырастает в сложную логику — выносить в отдельный файл.

Имя файла отражает конкретное действие, не общее:
- `recalculate_debt_command.go` (не `create_command.go`)
- `get_active_session_query.go` (не `get_query.go`)

**Command** — изменение данных:

```go
// recalculate_debt_command.go
type RecalculateDebtCommand struct {
    repository      Repository
    cacheRepository CacheRepository
}

func NewRecalculateDebtCommand(repository Repository, cacheRepository CacheRepository) *RecalculateDebtCommand {
    return &RecalculateDebtCommand{repository: repository, cacheRepository: cacheRepository}
}

func (c *RecalculateDebtCommand) Exec(ctx context.Context, dto RecalculateDebtDTO) (*Tariff, error) {
    // бизнес-логика
}
```

**Query** — чтение данных:

```go
// get_active_session_query.go
type GetActiveSessionQuery struct {
    repository      Repository
    cacheRepository CacheRepository
}

func NewGetActiveSessionQuery(repository Repository, cacheRepository CacheRepository) *GetActiveSessionQuery {
    return &GetActiveSessionQuery{repository: repository, cacheRepository: cacheRepository}
}

func (q *GetActiveSessionQuery) Exec(ctx context.Context, dto GetActiveSessionDTO) (*Session, error) {
    // логика с кешированием, фолбеком и тп
}
```

Разница с workflow: Command/Query живут внутри одного домена, workflow оркестрирует несколько доменов.

Command/Query берёт только репозитории **своего** домена; репозиторий или сервис чужого домена в конструкторе — **[ОШИБКА]**, сценарий переносится в workflow (он — агрегат и берёт чужие репозитории напрямую, O-7).

Нужны несколько модулей своего домена — Command/Query лежит в модуле-владельце результата, остальные модули получает через интерфейсы, объявленные в этом модуле (решение O-6).

Если `Command` выполняет несколько операций записи, которые должны выполниться атомарно — оборачивать в транзакцию (см. `../conventions.md`, правило 17, и `infra/postgres.md`, раздел "Транзакции").

## Cross-domain зависимость

Интерфейс объявляется в домене-потребителе, не в провайдере:

```go
// internal/domains/order/order/service.go
type CityServiceInterface interface {
    GetById(ctx context.Context, id uint) (*citydomain.City, error)
}

type Service struct {
    repository           Repository
    cityServiceInterface CityServiceInterface
}
```

