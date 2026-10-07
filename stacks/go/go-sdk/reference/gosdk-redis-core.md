# git.mpinnovations.kz/mps/go-packages/gosdk-redis-core v1.0.4

## `git.mpinnovations.kz/mps/go-packages/gosdk-redis-core/pkg/app`

- Константы: `RedisKernelName`
- `func NewRedisOptions(cfg *config.RedisConfig) *redis.Options` — NewRedisOptions собирает redis.Options из конфига. Нулевые значения не передаются, чтобы действовали значения go-redis по умолчанию.
- `type RedisKernel struct`
  - `func (m *RedisKernel) Init(a *app.App) error`
  - `func (m *RedisKernel) Name() string`
  - `func (m *RedisKernel) Start(a *app.App) error`
  - `func (m *RedisKernel) Stop(ctx context.Context) error`

## `git.mpinnovations.kz/mps/go-packages/gosdk-redis-core/pkg/config`

- `type RedisConfig struct` — RedisConfig — настройки подключения к Redis из env.
  - `func (c RedisConfig) Masked() RedisConfig` — Masked возвращает копию конфига со скрытым паролем — для вывода в лог.

## `git.mpinnovations.kz/mps/go-packages/gosdk-redis-core/pkg/di`

- `func GetRedisClient(c *di.Container) (*redis.Client, error)` — GetRedisClient возвращает клиент Redis

## `git.mpinnovations.kz/mps/go-packages/gosdk-redis-core/pkg/redis`

- `type Helper struct` — Helper - Хелпер для работы с редисом
  - `func NewHelper[E any](ctx context.Context, redisClient *redis.Client) *Helper[E]` — NewHelper - Хелпер для работы с редисом
  - `func (h *Helper[E]) GetArray(key string) ([]E, error)` — GetArray Возвращает массив по ключу
  - `func (h *Helper[E]) GetString(key string) (string, error)` — GetString Возвращает значение по ключу
  - `func (h *Helper[E]) GetStruct(key string) (*E, error)` — GetStruct Возвращает значение по ключу
  - `func (h *Helper[E]) MGetStruct(keys []string) (map[string]E, error)` — MGetStruct - Возвращает несколько структур по ключам из Redis
  - `func (h *Helper[E]) MSetStruct(data map[string]E, exp time.Duration) error` — MSetStruct - сохраняет несколько структур в Redis
  - `func (h *Helper[E]) SetArray(key string, models []E, ttl time.Duration) error` — SetArray Записывает массив по ключу
  - `func (h *Helper[E]) SetString(key string, value string, ttl time.Duration) error` — SetString Записывает значение по ключу
  - `func (h *Helper[E]) SetStruct(key string, model E, ttl time.Duration) error` — SetStruct Записывает значение по ключу
- `type RedisQueries struct`
  - `func NewRedisQueries() RedisQueries`
- `type RedisStatement struct`

