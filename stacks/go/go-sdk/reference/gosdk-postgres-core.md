# git.mpinnovations.kz/mps/go-packages/gosdk-postgres-core v1.0.6

## `git.mpinnovations.kz/mps/go-packages/gosdk-postgres-core/pkg/app`

- Константы: `DbKernelName`
- Константы: `DefaultPostgresConnectionKey`
- `type GormFactory …`
- `type Migrator struct` — Migrator управляет накатом/откатом миграций схемы БД поверх переданного db. Не открывает и не закрывает соединение сам — какое именно подключение использовать (в проекте их может быть несколько), решает вызывающая сторон…
  - `func NewMigrator(db *gorm.DB, migrations ...*migration.Migration) *Migrator` — NewMigrator создаёт Migrator для db (уже открытое соединение — например, database.InitPostgresGormConnection(dbConfig)) со списком миграций проекта (обычно — результат <project>/internal/migrations.All()).
  - `func (m *Migrator) RollbackLast() error` — RollbackLast откатывает последнюю применённую миграцию.
  - `func (m *Migrator) RollbackTo(migrationID string) error` — RollbackTo откатывает все применённые миграции после migrationID (саму migrationID не откатывает — семантика gormigrate.RollbackTo).
  - `func (m *Migrator) Up() error` — Up применяет все ещё не применённые миграции.
- `type PostgresGormRegistry struct`
  - `func NewPostgresGormRegistry(factory GormFactory) *PostgresGormRegistry`
  - `func (r *PostgresGormRegistry) Add(name string, cfg *config.PostgresDbConfig)` — Add registers/overrides config. You can choose stricter behavior: forbid overriding when client already exists.
  - `func (r *PostgresGormRegistry) AddDefaultConnection(cfg *config.PostgresDbConfig)`
  - `func (r *PostgresGormRegistry) CloseAll() error`
  - `func (r *PostgresGormRegistry) Get(name string) (*gorm.DB, error)`
  - `func (r *PostgresGormRegistry) GetDefaultConnection() (*gorm.DB, error)`
  - `func (r *PostgresGormRegistry) IsClosing() bool` — IsClosing indicates CloseAll() was called.
- `type PostgresKernel struct`
  - `func (m *PostgresKernel) Init(a *app.App) error`
  - `func (m *PostgresKernel) Name() string`
  - `func (m *PostgresKernel) Start(a *app.App) error`
  - `func (m *PostgresKernel) Stop(ctx context.Context) error`

## `git.mpinnovations.kz/mps/go-packages/gosdk-postgres-core/pkg/config`

- `type PostgresDbConfig struct` — PostgresDbConfig Данные для соединения с БД

## `git.mpinnovations.kz/mps/go-packages/gosdk-postgres-core/pkg/di`

- `func AddPostgresConnection(c *di.Container, name string, config *config.PostgresDbConfig) error` — AddPostgresConnection добавить соединение с БД
- `func GetDefaultPostgresConnection(c *di.Container) (*gorm.DB, error)` — GetDefaultPostgresConnection возвращает основной connection postgres.
- `func GetPostgresConnection(c *di.Container, name string) (*gorm.DB, error)` — GetPostgresConnection возвращает connection postgres.

## `git.mpinnovations.kz/mps/go-packages/gosdk-postgres-core/pkg/helpers`

- `func InitPostgresDbConfig() (*config.PostgresDbConfig, error)` — InitPostgresDbConfig Инициализация конфига БД c переменок окружения
- `func InitPostgresGormConnection(dbConfig *config.PostgresDbConfig) (*gorm.DB, error)` — InitPostgresGormConnection инициализирует клиент для postgres

