# Postgres / GORM

Пакеты:
- `github.com/exgamer/gosdk-postgres-core`
- `github.com/exgamer/gosdk-db-core/pkg/query/helpers`
- `github.com/exgamer/gosdk-db-core/pkg/query/pagination`

## Правила

- Каждый DB-запрос оборачивается в timeout: `ctx, cancel := context.WithTimeout(ctx, 10*time.Second); defer cancel()`; исключение — `NewGormPaginatedHelper` (timeout уже встроен внутри хелпера, оборачивать не нужно)
- `ErrRecordNotFound` → возвращать `nil, nil`, не ошибку
- модель всегда с маленькой буквы ибо используется только в пакете
- стандартные методы Create/Update всегда через model
- для пагинации использовать NewGormPaginatedHelper
- отсутствие `TableName()` у GORM-модели — **не ошибка**: GORM сам выводит имя таблицы по соглашению (snake_case + plural, например `City` → `cities`). Если `TableName()` отсутствует — сообщать уровнем **[ИНФО]** (уточнить, что GORM использует автоматическое имя таблицы), а не помечать как нарушение

## Model

```go
type product struct {
    ID         uint    `gorm:"column:id;primaryKey;autoIncrement"`
    Name       string  `gorm:"column:name"`
    CategoryID uint    `gorm:"column:category_id"`
    Status     int     `gorm:"column:status"`
}

func (product) TableName() string { return "schema.products" }
```

## Mapper

Маппер обязателен. Имена функций, если модель одна —  `modelToEntity`/`entityToModel`. если моделей несколько можно по смыслу

```go
func modelToEntity(m *product) *domain.Product {
    if m == nil { return nil }
	
    return &domain.Product{ID: m.ID, Name: m.Name, CategoryID: m.CategoryID, Status: m.Status}
}

func entityToModel(e *domain.Product) *product {
    if e == nil { return nil }
	
    return &product{ID: e.ID, Name: e.Name, CategoryID: e.CategoryID, Status: e.Status}
}
```

## Repository

```go
func NewPostgresRepository(client *gorm.DB) *PostgresRepository {
    return &PostgresRepository{
        client: client,
    }
}

type PostgresRepository struct {
    client *gorm.DB
}

func (r *PostgresRepository) GetById(ctx context.Context, id uint) (*domain.Product, error) {
    ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
    defer cancel()

    var m product
    result := r.client.WithContext(ctx).
        Where("id = ?", id).
        Where("deleted_at IS NULL").
        First(&m)

    if errors.Is(result.Error, gorm.ErrRecordNotFound) {
        return nil, nil
    }
    if result.Error != nil {
        return nil, result.Error
    }

    return modelToEntity(&m), nil
}

func (r *PostgresRepository) Create(ctx context.Context, e *domain.Product) (*domain.Product, error) {
    ctx, cancel := context.WithTimeout(ctx, 10*time.Second)
    defer cancel()

    m := entityToModel(e)
    if err := r.client.WithContext(ctx).Create(m).Error; err != nil {
        return nil, err
    }

    return modelToEntity(m), nil
}
```

## Транзакции

Workflow — всегда, если пишет (O-8); Command/Service — когда несколько операций записи обязаны выполниться атомарно (см. `../conventions.md`, правило 17, `approaches/patterns/transactions.md`). Менеджер транзакций SDK `gosdk-db-core/pkg/transaction` (`Manager`, `Manager2` … `Manager5` — по числу репозиториев в одной транзакции).

Как это устроено:
- **Домен/workflow** объявляет узкий интерфейс `TxManager` в отдельном файле `tx_manager.go` — только сигнатура `Exec`, без gorm.
- **Bootstrap** (`repositories_factory.go`) собирает реализацию через `dbtransaction.NewManager*`, передавая конструкторы репозиториев, построенные на `tx`.
- Репозитории менять не нужно: тот же `NewPostgresRepository(db)` получает `tx` вместо пула.
- Таймаут транзакции по умолчанию 10s, переопределяется `.WithTimeout(...)`.

```go
// internal/domains/billing/payment_transactions/tx_manager.go
// TxManager — атомарная операция над Repository внутри одной Postgres-транзакции.
type TxManager interface {
    Exec(ctx context.Context, fn func(ctx context.Context, repo Repository) error) error
}
```

```go
// internal/workflows/session/manage_event/tx_manager.go — две сущности атомарно
// SessionEntryTxManager атомарно создаёт Session + SessionEvent при въезде.
type SessionEntryTxManager interface {
    Exec(ctx context.Context, fn func(ctx context.Context, sessionRepo sessiondomain.Repository, sessionEventRepo sessioneventdomain.Repository) error) error
}
```

```go
// internal/app/bootstrap/{module}/repositories_factory.go
import dbtransaction "git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/transaction"

PaymentTxManager: dbtransaction.NewManager[paymenttxdomain.Repository](
    postgresClient,
    func(tx *gorm.DB) paymenttxdomain.Repository { return paymenttxinfrapostgre.NewPostgresRepository(tx) },
).WithTimeout(20 * time.Second),

PaymentEventTxManager: dbtransaction.NewManager2[sessioneventdomain.Repository, outboxdomain.Repository](
    postgresClient,
    func(tx *gorm.DB) sessioneventdomain.Repository { return sessioneventinfrapostgre.NewPostgresRepository(tx) },
    func(tx *gorm.DB) outboxdomain.Repository { return outboxinfrapostgre.NewPostgresRepository(tx) },
),
```

```go
// использование в сервисе/workflow
err := w.txManager.Exec(ctx, func(ctx context.Context, sessionRepo sessiondomain.Repository, eventRepo sessioneventdomain.Repository) error {
    session, err := sessionRepo.Create(ctx, entity)
    if err != nil {
        return err
    }

    return eventRepo.Create(ctx, event(session.ID))
})
```

Правила:
- Workflow с записью без транзакции (и без причины в `design.md`) или Command/Service с несколькими взаимосвязанными записями без транзакции — **[ОШИБКА]**.
- Внутри `fn` — только репозитории, полученные аргументами (на `tx`). Репозиторий из поля структуры, **сервис или Command домена** внутри `fn` пишут мимо транзакции (менеджер пересобирает только репозитории) — **[ОШИБКА]**, тег `tx-bypassed-by-service`. Workflow пишет через репозитории (O-7).
- Проверки, от которых зависит запись, читаются внутри `fn`, через репозитории из аргументов.
- Тесты: фейк менеджера вызывает `fn` с in-memory репозиториями — `examples/tariff/internal/testsupport/txfakes`.
- Внутри `fn` — никаких HTTP-вызовов, публикаций в очередь и `time.Sleep`: транзакция держит блокировки. Побочные эффекты — через outbox (см. `approaches/patterns/outbox.md`).
- Имя интерфейса отражает операцию (`SessionEntryTxManager`), а не придуманную механику (`EnqueueTxManager` без очереди — **[ОШИБКА]**, см. `../conventions.md`, п. 20).
- Ручной `client.Transaction(func(tx *gorm.DB) error {...})` в репозитории допустим только если транзакция целиком внутри одного метода одного репозитория.

## Пагинация

```go
func (r *PostgresRepository) Paginated(ctx context.Context, s *domain.Search) (*pagination.Paginated[domain.Product], error) {
    helper := helpers.NewGormPaginatedHelper[domain.Product](ctx, r.client).SetPerPage(s.PerPage)
    return helper.Paginated(s.Page, func(db *gorm.DB) *gorm.DB {
        return db.WithContext(ctx).Select("*").Where("status = ?", 1).Order("id DESC")
    })
}
```
