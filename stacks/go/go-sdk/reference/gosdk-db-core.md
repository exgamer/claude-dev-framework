# git.mpinnovations.kz/mps/go-packages/gosdk-db-core v1.0.7

## `git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/debug`

- `type SqlStatement struct`

## `git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/helpers`

- `func GetGormConnection(dbConfig *DbConfig) (*gorm.DB, error)` — GetGormConnection Возвращает клиент для работы с БД
- `type DbConfig struct` — DbConfig Модель данных для описания соединения с БД

## `git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/middleware`

- `func NewGormDebugMiddleware() gorm.Plugin`
- `func SlowSqlSentryMiddleware(threshold time.Duration, serviceName string) gorm.Plugin`
- `type GormDebugMiddleware struct` — GormDebugMiddleware мидлвар для вывода запросов в дебаг инфу
  - `func (p *GormDebugMiddleware) Initialize(db *gorm.DB) error`
  - `func (p *GormDebugMiddleware) Name() string`
- `type SlowSqlSentry struct`
  - `func (p *SlowSqlSentry) Initialize(db *gorm.DB) error`
  - `func (g *SlowSqlSentry) Name() string`

## `git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/migration`

Package migration — тонкая обёртка над gormigrate для прогона версионированных миграций схемы через *gorm.DB. Полностью dialect-agnostic (как и остальной db-core) — ничего не знает про конкретную СУБД. В частности, здесь…

- `func RollbackLast(db *gorm.DB, migrations []*Migration) error` — RollbackLast откатывает последнюю применённую миграцию из migrations, вызывая её Rollback. Пустой список — не ошибка: пишется лог, ничего не откатывается. Как и Run, не защищена от параллельного вызова из нескольких инст…
- `func RollbackTo(db *gorm.DB, migrations []*Migration, migrationID string) error` — RollbackTo откатывает все применённые миграции из migrations, идущие после migrationID, в обратном порядке — саму migrationID не откатывает (семантика gormigrate.RollbackTo). Как и RollbackLast — ручная операция, без защ…
- `func Run(db *gorm.DB, migrations []*Migration) error` — Run применяет все ещё не применённые migrations к db, по одной, в порядке объявления. Пустой список — не ошибка: пишется лог, Migrate не вызывается. Никакой защиты от параллельного вызова из нескольких инстансов сервиса …
- `type Migration …` — Migration — ре-экспорт gormigrate.Migration, чтобы вызывающему коду не нужно было импортировать gormigrate напрямую.

## `git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/query/helpers`

- `type GormPaginatedHelper struct` — GormPaginatedHelper - Вспомогательный хелпер для постраничного чтения данных
  - `func NewGormPaginatedHelper[E interface{}](ctx context.Context, client *gorm.DB) *GormPaginatedHelper[E]`
  - `func (h *GormPaginatedHelper[E]) Paginated(page uint, callback func(client *gorm.DB) *gorm.DB) (*pagination.Paginated[E], error)`
  - `func (h *GormPaginatedHelper[E]) SetContext(ctx context.Context) *GormPaginatedHelper[E]`
  - `func (h *GormPaginatedHelper[E]) SetPerPage(perPage uint) *GormPaginatedHelper[E]`
  - `func (h *GormPaginatedHelper[E]) SetTimeout(timeout time.Duration) *GormPaginatedHelper[E]`

## `git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/query/pagination`

- `type Paginated struct` — Paginated постраничный список
- `type Pagination struct`
  - `func Pages(p *Param, result interface{}) (paginator *Pagination, err error)` — Endpoint for pagination
  - `func (p Pagination) IsEmpty() bool`
- `type Paging struct`
- `type Param struct`

## `git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/transaction`

Package transactionmanager — Manager/Manager2/Manager3/Manager4/Manager5: по типу на число репозиториев в одной Postgres-транзакции. В Go нет variadic generics, поэтому один Manager[R...] невозможен — тот же case, что у …

- `type Manager struct` — Manager runs fn inside a single Postgres transaction. build rebuilds a repository bound to that transaction's *gorm.DB, using the exact same constructor the repository already has — no changes are required in any reposit…
  - `func NewManager[R any](client *gorm.DB, build func(tx *gorm.DB) R) *Manager[R]`
  - `func (m *Manager[R]) Default() R` — Default returns a plain, non-transactional instance bound to the regular connection pool — the same thing build(client) would give you directly.
  - `func (m *Manager[R]) Exec(ctx context.Context, fn func(ctx context.Context, repo R) error) error` — Exec commits everything fn does through repo if fn returns nil, and rolls back everything if fn returns an error (or panics). The transaction is bounded by m.timeout regardless of ctx's own deadline (the earlier of the t…
  - `func (m *Manager[R]) WithTimeout(d time.Duration) *Manager[R]` — WithTimeout переопределяет дефолтный таймаут транзакции (10s). Вызывать сразу после NewManager, до начала обработки запросов.
- `type Manager2 struct` — Manager2 is Manager for the case where one atomic operation needs two different repositories at once. There is no bundle/struct type involved — both repositories are passed to fn as separate, explicit parameters, so a do…
  - `func NewManager2[R1, R2 any](client *gorm.DB, build1 func(tx *gorm.DB) R1, build2 func(tx *gorm.DB) R2) *Manager2[R1, R2]`
  - `func (m *Manager2[R1, R2]) Default() (R1, R2)` — Default returns plain, non-transactional instances of both repositories.
  - `func (m *Manager2[R1, R2]) Exec(ctx context.Context, fn func(ctx context.Context, repo1 R1, repo2 R2) error) error` — Exec commits everything fn does through repo1/repo2 if fn returns nil, and rolls back everything (across both repositories) if fn returns an error. The transaction is bounded by m.timeout regardless of ctx's own deadline…
  - `func (m *Manager2[R1, R2]) WithTimeout(d time.Duration) *Manager2[R1, R2]` — WithTimeout переопределяет дефолтный таймаут транзакции (10s). Вызывать сразу после NewManager2, до начала обработки запросов.
- `type Manager3 struct` — Manager3 is Manager for the case where one atomic operation needs three different repositories at once. Same shape as Manager2 — no bundle/struct type, all three repositories passed to fn as separate, explicit params.
  - `func NewManager3[R1, R2, R3 any]( client *gorm.DB, build1 func(tx *gorm.DB) R1, build2 func(tx *gorm.DB) R2, build3 func(tx *gorm.DB) R3, ) *Manager3[R1, R2, R3]`
  - `func (m *Manager3[R1, R2, R3]) Default() (R1, R2, R3)` — Default returns plain, non-transactional instances of all three repositories.
  - `func (m *Manager3[R1, R2, R3]) Exec(ctx context.Context, fn func(ctx context.Context, repo1 R1, repo2 R2, repo3 R3) error) error` — Exec commits everything fn does through repo1/repo2/repo3 if fn returns nil, and rolls back everything (across all three repositories) if fn returns an error. The transaction is bounded by m.timeout regardless of ctx's o…
  - `func (m *Manager3[R1, R2, R3]) WithTimeout(d time.Duration) *Manager3[R1, R2, R3]` — WithTimeout переопределяет дефолтный таймаут транзакции (10s). Вызывать сразу после NewManager3, до начала обработки запросов.
- `type Manager4 struct` — Manager4 is Manager for the case where one atomic operation needs four different repositories at once. Same shape as Manager2/Manager3.
  - `func NewManager4[R1, R2, R3, R4 any]( client *gorm.DB, build1 func(tx *gorm.DB) R1, build2 func(tx *gorm.DB) R2, build3 func(tx *gorm.DB) R3, build4 func(tx *gorm.DB) R4, ) *Manager4[R1, R2, R3, R4]`
  - `func (m *Manager4[R1, R2, R3, R4]) Default() (R1, R2, R3, R4)` — Default returns plain, non-transactional instances of all four repositories.
  - `func (m *Manager4[R1, R2, R3, R4]) Exec(ctx context.Context, fn func(ctx context.Context, repo1 R1, repo2 R2, repo3 R3, repo4 R4) error) error` — Exec commits everything fn does through repo1..repo4 if fn returns nil, and rolls back everything (across all four repositories) if fn returns an error. The transaction is bounded by m.timeout regardless of ctx's own dea…
  - `func (m *Manager4[R1, R2, R3, R4]) WithTimeout(d time.Duration) *Manager4[R1, R2, R3, R4]` — WithTimeout переопределяет дефолтный таймаут транзакции (10s). Вызывать сразу после NewManager4, до начала обработки запросов.
- `type Manager5 struct` — Manager5 is Manager for the case where one atomic operation needs five different repositories at once. Same shape as Manager2/Manager3/Manager4.
  - `func NewManager5[R1, R2, R3, R4, R5 any]( client *gorm.DB, build1 func(tx *gorm.DB) R1, build2 func(tx *gorm.DB) R2, build3 func(tx *gorm.DB) R3, build4 func(tx *gorm.DB) R4, build5 func(tx *gorm.DB) R5, ) *Manager5[R1, R2, R3, R4, R5]`
  - `func (m *Manager5[R1, R2, R3, R4, R5]) Default() (R1, R2, R3, R4, R5)` — Default returns plain, non-transactional instances of all five repositories.
  - `func (m *Manager5[R1, R2, R3, R4, R5]) Exec(ctx context.Context, fn func(ctx context.Context, repo1 R1, repo2 R2, repo3 R3, repo4 R4, repo5 R5) error) error` — Exec commits everything fn does through repo1..repo5 if fn returns nil, and rolls back everything (across all five repositories) if fn returns an error. The transaction is bounded by m.timeout regardless of ctx's own dea…
  - `func (m *Manager5[R1, R2, R3, R4, R5]) WithTimeout(d time.Duration) *Manager5[R1, R2, R3, R4, R5]` — WithTimeout переопределяет дефолтный таймаут транзакции (10s). Вызывать сразу после NewManager5, до начала обработки запросов.

