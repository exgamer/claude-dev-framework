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

func (r *PostgresRepository) GetByID(ctx context.Context, id uint) (*domain.Product, error) {
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

Workflow, Command, Service — одинаково: когда сценарий делает больше одного модифицирующего запроса (в разные таблицы или в одну); один модифицирующий запрос — без транзакции, кроме транзакции ради блокировки `FOR UPDATE`/`FOR SHARE` — основание «Конкурентность» `design.md` (O-8, `approaches/patterns/transactions.md`, `../../conventions.md` п. 17). Менеджер транзакций SDK `gosdk-db-core/pkg/transaction` (`Manager`, `Manager2` … `Manager5` — по числу репозиториев в одной транзакции).

Как это устроено:
- **Домен/workflow** объявляет узкий интерфейс `TxManager` в отдельном файле `tx_manager.go` — только сигнатура `Exec`, без gorm.
- **Bootstrap** (`repositories_factory.go`) собирает реализацию через `dbtransaction.NewManager*`, передавая конструкторы репозиториев, построенные на `tx`.
- Репозитории менять не нужно: тот же `NewPostgresRepository(db)` получает `tx` вместо пула.
- Таймаут транзакции по умолчанию 10s, переопределяется `.WithTimeout(...)`.

Эталон — `../../examples/tariff/`.

```go
// internal/domains/billing/tariff/tx_manager.go — один репозиторий, два UPDATE
// DefaultTariffTxManager Смена тарифа по умолчанию: снять флаг со старого и поставить новому — атомарно
type DefaultTariffTxManager interface {
    Exec(ctx context.Context, fn func(ctx context.Context, repository Repository) error) error
}
```

```go
// internal/workflows/billing/tariff/tx_manager.go — две сущности атомарно (тариф + outbox)
// CreateTariffTxManager Создание тарифа и событие tariff.created в outbox — в одной транзакции (два модифицирующих запроса)
type CreateTariffTxManager interface {
    Exec(ctx context.Context, fn func(
        ctx context.Context,
        tariffRepository tariffdomain.Repository,
        outboxRepository outboxdomain.Repository,
    ) error) error
}
```

```go
// internal/app/bootstrap/billing/tariff/repositories_factory.go
import dbtransaction "git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/transaction"

DefaultTariffTxManager: dbtransaction.NewManager[tariffdomain.Repository](
    postgresClient,
    func(tx *gorm.DB) tariffdomain.Repository { return tariffpostgres.NewPostgresRepository(tx) },
),
CreateTariffTxManager: dbtransaction.NewManager2[tariffdomain.Repository, outboxdomain.Repository](
    postgresClient,
    func(tx *gorm.DB) tariffdomain.Repository { return tariffpostgres.NewPostgresRepository(tx) },
    func(tx *gorm.DB) outboxdomain.Repository { return outboxpostgres.NewPostgresRepository(tx) },
),
```

```go
// использование — SetDefaultTariffCommand.Exec (internal/domains/billing/tariff/set_default_tariff_command.go, сокращено)
err := c.txManager.Exec(ctx, func(ctx context.Context, repository Repository) error {
    tariff, err := repository.GetByID(ctx, id, scope)
    if err != nil {
        return err
    }

    if tariff == nil {
        return exception.NewNotFoundException(errors.New("тариф не найден"), false)
    }

    previous, err := repository.GetDefaultByParkingID(ctx, tariff.ParkingID)
    if err != nil {
        return err
    }

    if previous != nil {
        if err := repository.SetDefault(ctx, previous.ID, false); err != nil {
            return err
        }
    }

    return repository.SetDefault(ctx, tariff.ID, true)
})
if errors.Is(err, ErrDefaultTariffTaken) { // параллельную смену закрывает уникальный индекс — 422
    ...
}
```

Правила:
- Сценарий (workflow, Command, Service) делает больше одного модифицирующего запроса без транзакции — **[ОШИБКА]** (`multi-write-without-transaction`); транзакция на один модифицирующий запрос без блокировки — **[ВНИМАНИЕ]** (`needless-transaction`).
- Внутри `fn` — только репозитории, полученные аргументами (на `tx`). Репозиторий из поля структуры, **сервис или Command домена** внутри `fn` пишут мимо транзакции (менеджер пересобирает только репозитории) — **[ОШИБКА]**, тег `tx-bypassed-by-service`. Workflow пишет через репозитории (O-7).
- Чтение внутри `fn` допустимо (как `GetByID`/`GetDefaultByParkingID` в `SetDefaultTariffCommand`) — через репозитории из аргументов. Гонку «проверил — записал» закрывает не сама транзакция, а `approaches/patterns/concurrency.md`: уникальный индекс, условие в `UPDATE` или блокировка (`FOR UPDATE`/`FOR SHARE`) до коммита; проверки перед записью ради этого в `fn` не переносятся.
- Тесты: фейк менеджера вызывает `fn` с in-memory репозиториями — `examples/tariff/internal/testsupport/txfakes`.
- Внутри `fn` — никаких HTTP-вызовов, публикаций в очередь и `time.Sleep`: транзакция держит блокировки. Побочные эффекты — через outbox (см. `approaches/patterns/outbox.md`).
- Имя интерфейса отражает операцию (`DefaultTariffTxManager`, `CreateTariffTxManager`), а не придуманную механику (`EnqueueTxManager` без очереди — **[ОШИБКА]**, см. `../../conventions.md`, п. 20).
- Ручной `client.Transaction(func(tx *gorm.DB) error {...})` в репозитории не используется: метод репозитория выполняет один запрос (`../../conventions.md`, п. 15b), а несколько запросов в одной транзакции — это сервис, Command или workflow через `txManager.Exec`.

## Пагинация

```go
func (r *PostgresRepository) Paginated(ctx context.Context, search *tariffdomain.Search) (*pagination.Paginated[tariffdomain.Tariff], error) {
    return helpers.NewGormPaginatedHelper[tariffdomain.Tariff](ctx, r.client).
        SetTimeout(10*time.Second).
        SetPerPage(search.PerPage).
        Paginated(search.Page, func(db *gorm.DB) *gorm.DB {
            query := db.Model(&tariff{}).Where("deleted_at IS NULL")

            // скоуп — поле фильтра типа ParkingScope: без All — только свои, пустой IDs — ничего (../../conventions.md п. 7a)
            if !search.Scope.All {
                query = query.Where("parking_id IN ?", search.Scope.IDs)
            }

            if search.ParkingID != 0 {
                query = query.Where("parking_id = ?", search.ParkingID)
            }

            return query.Order("id DESC")
        })
}
```
